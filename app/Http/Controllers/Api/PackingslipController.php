<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Item_ledger;
use App\Models\Packing_slip;
use App\Models\Packing_slip_detail;
use App\Models\Sale_order;
use App\Models\So_detail;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PackingslipController  extends ResponseController
{
    public function index($id)
    {
        try {
            $packing_slips = Packing_slip::with('sale_order', 'store', 'user')->where('sale_order_id', $id);

            $packingSlips = $packing_slips->latest()->paginate(20);

            return $this->sendPaginatedResponse($packingSlips, 'Packing slips fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_packing_slip($id)
    {
        try {
            $packingSlip = Packing_slip::with(
                'sale_order',
                'store',
                'user',
                'packing_slip_details.item',
                'packing_slip_details.so_detail'
            )->findOrFail($id);

            return $this->sendResponse($packingSlip, 'Packing slip fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Packing slip not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function getBySaleOrder(Request $request)
    {
        try {
            $id = $request->sale_order_id;
            $packingSlip = Packing_slip::with(
                'sale_order',
                'store',
                'user',
                'packing_slip_details.item',
                'packing_slip_details.so_detail'
            )->where('sale_order_id', $id)->get();

            return $this->sendResponse($packingSlip, 'Packing slip fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Packing slip not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'sale_order_id'               => 'required|exists:sale_orders,id',
                'store_id'                    => 'required|exists:stores,id',
                'vehicle_no'                  => 'nullable|string',
                'driver_name'                 => 'nullable|string',
                'driver_phone'                => 'nullable|string',
                'remarks'                     => 'nullable|string',
                'details'                     => 'required|array|min:1',
                'details.*.so_detail_id'      => 'required|exists:so_details,id',
                'details.*.item_id'           => 'required|exists:items,id',
                'details.*.sku'               => 'nullable',
                'details.*.ordered_qty'       => 'required|numeric|min:0',
                'details.*.packed_qty'        => 'required|numeric|min:0',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            // Sale order must be open to create a packing slip
            $saleOrder = Sale_order::findOrFail($request->sale_order_id);

            if ($saleOrder->status !== 'open') {
                return $this->sendError(
                    'Cannot create packing slip.',
                    'Packing slips can only be created for open sale orders.',
                    422
                );
            }

            DB::beginTransaction();

            $data = $validated->validated();

            // Generate unique PS number
            $lastSlip      = Packing_slip::latest('id')->first();
            $nextId        = $lastSlip ? $lastSlip->id + 1 : 1;
            $data['ps_no'] = 'PS-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            $data['user_id'] = auth()->id();
            $data['status']  = 'draft';

            $packingSlip = Packing_slip::create([
                'sale_order_id' => $data['sale_order_id'],
                'store_id'      => $data['store_id'],
                'user_id'       => $data['user_id'],
                'ps_no'         => $data['ps_no'],
                'status'        => $data['status'],
                'vehicle_no'    => $data['vehicle_no'] ?? null,
                'driver_name'   => $data['driver_name'] ?? null,
                'driver_phone'  => $data['driver_phone'] ?? null,
                'remarks'       => $data['remarks'] ?? null,
            ]);

            // Create packing slip detail lines
            foreach ($data['details'] as $detail) {
                $inStock = Item_ledger::where('item_id', $detail['item_id'])
                    ->where('store_id', $data['store_id'])
                    ->where('transaction_type', 'IN')
                    ->sum('quantity');

                $outStock = Item_ledger::where('item_id', $detail['item_id'])
                    ->where('store_id', $data['store_id'])
                    ->where('transaction_type', 'OUT')
                    ->sum('quantity');

                $currentStock = $inStock - $outStock;

                $item = Item::find($detail['item_id']);
                if ($currentStock < $detail['packed_qty']) {
                    DB::rollBack();
                    return $this->sendError(
                        'Insufficient stock.',
                        'Not enough stock available for Sku: ' . ($item->sku . ' ' . $item->name),
                        422
                    );
                }


                Packing_slip_detail::create([
                    'packing_slip_id' => $packingSlip->id,
                    'so_detail_id'    => $detail['so_detail_id'],
                    'item_id'         => $detail['item_id'],
                    'ordered_qty'     => $detail['ordered_qty'],
                    'packed_qty'      => $detail['packed_qty'],
                ]);
            }

            DB::commit();

            return $this->sendResponse(
                $packingSlip->load('sale_order', 'store', 'user', 'packing_slip_details.item'),
                'Packing slip created successfully.'
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->sendError('Sale order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    /**
     * POST /packing-slips/{id}/dispatch
     * Dispatches the packing slip:
     *   1. Creates Item Ledger OUT entries for each detail line
     *   2. Updates Sale Order status to 'delivered'
     *   3. Marks Packing Slip as 'dispatched'
     */
    public function dispatch($id)
    {
        try {

            $packingSlip = Packing_slip::with(['packing_slip_details.so_detail'])
                ->findOrFail($id);

            if ($packingSlip->status !== 'draft') {
                return $this->sendError(
                    'Cannot dispatch packing slip.',
                    'Only draft packing slips can be dispatched.',
                    422
                );
            }

            // 1. PRE-CHECK STOCK AVAILABILITY BEFORE TRANSACTION
            foreach ($packingSlip->packing_slip_details as $detail) {

                if (!$detail->packed_qty || $detail->packed_qty <= 0) {
                    continue;
                }

                $stock = Item_ledger::where('item_id', $detail->item_id)
                    ->where('store_id', $packingSlip->store_id)
                    ->selectRaw('SUM(CASE WHEN transaction_type = "IN" THEN quantity ELSE -quantity END) as balance')
                    ->value('balance') ?? 0;

                if ($stock < $detail->packed_qty) {
                    return $this->sendError(
                        'Insufficient stock.',
                        "Item ID {$detail->item_id} has only {$stock} units available but {$detail->packed_qty} required.",
                        422
                    );
                }
            }

            DB::beginTransaction();

            foreach ($packingSlip->packing_slip_details as $detail) {

                // SKIP ZERO OR NULL QUANTITY LINES
                if (!$detail->packed_qty || $detail->packed_qty <= 0) {
                    continue;
                }

                $item_id = $detail->item_id;
                $item =  Item::findOrFail($item_id);

                // 2. STOCK OUT (Ledger)
                Item_ledger::create([
                    'item_id'          => $detail->item_id,
                    'store_id'         => $packingSlip->store_id,
                    'transaction_type' => 'OUT',
                    'reference_type'   => 'sale_order',
                    'reference_id'     => $packingSlip->id,
                    'quantity'         => $detail->packed_qty,
                    'unit_cost'        => $item->sale_price,
                    'transaction_date' => now(),
                    'created_by'       => auth()->id(),
                ]);

                $delivered_qty = $detail->so_detail->delivered_qty;
                $pked_qty = $detail->packed_qty;
                $check_qty = $delivered_qty + $pked_qty;

                // 3. UPDATE SO DETAIL
                if ($detail->so_detail) {
                    $detail->so_detail->delivered_qty += $detail->packed_qty;
                    $detail->so_detail->remaining_qty  = max(0, $detail->so_detail->remaining_qty - $detail->packed_qty);
                    //Check if total qty is delivered than it make its status delivered
                    if ($check_qty == $detail->so_detail->quantity) {
                        $detail->so_detail->status = 'delivered';
                    }
                }
                // If user dispatch more qty than the order quantity it return the  error
                if ($check_qty > $detail->so_detail->quantity) {
                    DB::rollBack();

                    return $this->sendError(
                        'Dispatch quantity exceeds ordered quantity.',
                        'You cannot deliver more than the ordered quantity.',
                        422
                    );
                }
                // now save this detail
                $detail->so_detail->save();
            }

            // 4. UPDATE SALE ORDER STATUS
            $saleOrder = Sale_order::find($packingSlip->sale_order_id);

            if ($saleOrder) {

                $allDetails     = $saleOrder->so_detail()->get();
                $totalOrdered   = $allDetails->sum('quantity');
                $totalDelivered = $allDetails->sum('delivered_qty');

                if ($totalDelivered >= $totalOrdered) {
                    $status = 'delivered';
                } elseif ($totalDelivered > 0) {
                    $status = 'open';
                } else {
                    $status = $saleOrder->status;
                }

                $saleOrder->update(['status' => $status]);
            }

            // 5. MARK PACKING SLIP DISPATCHED
            $packingSlip->update([
                'status'        => 'dispatched',
                'dispatch_date' => now(),
            ]);

            DB::commit();

            return $this->sendResponse(
                $packingSlip->load('sale_order', 'store', 'user', 'packing_slip_details.item'),
                'Packing slip dispatched successfully.'
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->sendError('Packing slip not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
