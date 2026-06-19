<?php

namespace App\Http\Controllers\api;

use App\Models\Item_ledger;
use App\Models\Packing_slip;
use App\Models\Sale_order;
use App\Models\Sale_return;
use App\Models\Sale_return_detail;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleReturnController extends ResponseController
{
    public function index(Request $request)
    {
        try {
            $query = Sale_return::with('sale_order', 'packing_slip', 'store', 'user');
 
            // Optional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
 
            if ($request->filled('return_type')) {
                $query->where('return_type', $request->return_type);
            }
 
            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }
 
            if ($request->filled('sale_order_id')) {
                $query->where('sale_order_id', $request->sale_order_id);
            }
 
            if ($request->filled('packing_slip_id')) {
                $query->where('packing_slip_id', $request->packing_slip_id);
            }
 
            if ($request->filled('from_date')) {
                $query->whereDate('return_date', '>=', $request->from_date);
            }
 
            if ($request->filled('to_date')) {
                $query->whereDate('return_date', '<=', $request->to_date);
            }
 
            $saleReturns = $query->latest()->paginate(10);
 
            return $this->sendPaginatedResponse($saleReturns, 'Sale returns fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
 
    public function single_sale_return($id)
    {
        try {
            $saleReturn = Sale_return::with(
                'sale_order',
                'packing_slip',
                'store',
                'user',
                'sale_return_details.item',
                'sale_return_details.packing_slip_detail'
            )->findOrFail($id);
 
            return $this->sendResponse($saleReturn, 'Sale return fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale return not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
 
    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'sale_order_id'                          => 'required|exists:sale_orders,id',
                'packing_slip_id'                        => 'required|exists:packing_slips,id',
                'store_id'                               => 'required|exists:stores,id',
                'reason'                                 => 'nullable|string',
                'details'                                => 'required|array|min:1',
                'details.*.packing_slip_detail_id'       => 'required|exists:packing_slip_details,id',
                'details.*.item_id'                      => 'required|exists:items,id',
                'details.*.dispatched_qty'               => 'required|numeric|min:0',
                'details.*.return_qty'                   => 'required|numeric|min:0',
                'details.*.remarks'                      => 'nullable|string',
            ]
        );
 
        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }
 
        try {
            // Packing slip must be dispatched to create a return
            $packingSlip = Packing_slip::findOrFail($request->packing_slip_id);
 
            if ($packingSlip->status !== 'dispatched') {
                return $this->sendError(
                    'Cannot create sale return.',
                    'Sale returns can only be created for dispatched packing slips.',
                    422
                );
            }
 
            // Validate return_qty does not exceed dispatched_qty per line
            foreach ($request->details as $detail) {
                if ($detail['return_qty'] > $detail['dispatched_qty']) {
                    return $this->sendError(
                        'Invalid return quantity.',
                        'Return quantity cannot exceed dispatched quantity for item ID ' . $detail['item_id'] . '.',
                        422
                    );
                }
            }
 
            DB::beginTransaction();
 
            $data = $validated->validated();
 
            // Generate unique SR number
            $lastReturn    = Sale_return::latest('id')->first();
            $nextId        = $lastReturn ? $lastReturn->id + 1 : 1;
            $data['sr_no'] = 'SR-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
 
            $data['user_id'] = auth()->id();
            $data['status']  = 'draft';
 
            // Determine return type: full if all lines return full dispatched qty
            $isFullReturn = collect($data['details'])->every(function ($detail) {
                return $detail['return_qty'] == $detail['dispatched_qty'];
            });
 
            $data['return_type'] = $isFullReturn ? 'full' : 'partial';
 
            $saleReturn = Sale_return::create([
                'sale_order_id'   => $data['sale_order_id'],
                'packing_slip_id' => $data['packing_slip_id'],
                'store_id'        => $data['store_id'],
                'user_id'         => $data['user_id'],
                'sr_no'           => $data['sr_no'],
                'status'          => $data['status'],
                'return_type'     => $data['return_type'],
                'reason'          => $data['reason'] ?? null,
            ]);
 
            // Create sale return detail lines
            foreach ($data['details'] as $detail) {
                Sale_return_detail::create([
                    'sale_return_id'          => $saleReturn->id,
                    'packing_slip_detail_id'  => $detail['packing_slip_detail_id'],
                    'item_id'                 => $detail['item_id'],
                    'dispatched_qty'          => $detail['dispatched_qty'],
                    'return_qty'              => $detail['return_qty'],
                    'remarks'                 => $detail['remarks'] ?? null,
                ]);
            }
 
            DB::commit();
 
            return $this->sendResponse(
                $saleReturn->load('sale_order', 'packing_slip', 'store', 'user', 'sale_return_details.item'),
                'Sale return created successfully.'
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->sendError('Record not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
 
    /**
     * POST /sale-returns/{id}/post
     * Posts the sale return:
     *   1. Creates Item Ledger IN entries for each return line
     *   2. Updates Sale Order status:
     *      - full return  → back to 'open'
     *      - partial return → 'partial'
     *   3. Marks Sale Return as 'posted'
     */
    public function post($id)
    {
        try {
            $saleReturn = Sale_return::with('sale_return_details')->findOrFail($id);
 
            if ($saleReturn->status !== 'draft') {
                return $this->sendError(
                    'Cannot post sale return.',
                    'Only draft sale returns can be posted.',
                    422
                );
            }
 
            DB::beginTransaction();
 
            // 1. Create Item Ledger IN entry for each return line
            foreach ($saleReturn->sale_return_details as $detail) {
                Item_ledger::create([
                    'item_id'          => $detail->item_id,
                    'store_id'         => $saleReturn->store_id,
                    'transaction_type' => 'IN',
                    'reference_type'   => 'return',
                    'reference_id'     => $saleReturn->id,
                    'quantity'         => $detail->return_qty,
                    'unit_cost'        => null,
                    'transaction_date' => now(),
                    'created_by'       => auth()->id(),
                ]);
            }
 
            // 2. Update Sale Order status based on return type
            $newSoStatus = $saleReturn->return_type === 'full' ? 'open' : 'partial';
 
            Sale_order::where('id', $saleReturn->sale_order_id)
                ->update(['status' => $newSoStatus]);
 
            // 3. Mark Sale Return as posted
            $saleReturn->update([
                'status'      => 'posted',
                'return_date' => now(),
            ]);
 
            DB::commit();
 
            return $this->sendResponse(
                $saleReturn->load('sale_order', 'packing_slip', 'store', 'user', 'sale_return_details.item'),
                'Sale return posted successfully. Item ledger updated and sale order status updated to ' . $newSoStatus . '.'
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->sendError('Sale return not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
 
    /**
     * POST /sale-returns/{id}/cancel
     * Only allowed while still in draft (before posting)
     */
    public function cancel($id)
    {
        try {
            $saleReturn = Sale_return::findOrFail($id);
 
            if ($saleReturn->status !== 'draft') {
                return $this->sendError(
                    'Cannot cancel sale return.',
                    'Only draft sale returns can be cancelled.',
                    422
                );
            }
 
            $saleReturn->update(['status' => 'cancelled']);
 
            return $this->sendResponse($saleReturn, 'Sale return cancelled successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale return not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}