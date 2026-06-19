<?php

namespace App\Http\Controllers\api;

use App\Models\Item;
use App\Models\Sale_order;
use App\Models\So_detail;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SoDetailController extends ResponseController
{
    private function recalculateSaleOrderTotals(Sale_order $saleOrder): void
    {
        $lines = So_detail::where('sale_order_id', $saleOrder->id)->get();

        $subTotal       = $lines->sum(fn($l) => $l->unit_price * $l->quantity);
        $discountPercent = $saleOrder->discount_percentage ?? 0; // e.g. 10 means 10%
        $discountAmount  = $subTotal * ($discountPercent / 100);
        $taxAmount      = $lines->sum('tax_amount');
        $grandTotal     = ($subTotal - $discountPercent) + $taxAmount;
        $dueAmount      = $grandTotal - $saleOrder->paid_amount;

        $saleOrder->update([
            'sub_total'       => $subTotal,
            'discount_amount' => $discountAmount,
            'tax_amount'      => $taxAmount,
            'grand_total'     => $grandTotal,
            'due_amount'      => max(0, $dueAmount),
        ]);
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function index($saleOrderId)
    {
        try {
            $saleOrder = Sale_order::findOrFail($saleOrderId);

            $details = So_detail::with('item', 'store')
                ->where('sale_order_id', $saleOrder->id)
                ->paginate(10);

            return $this->sendPaginatedResponse($details, 'Sale order details fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_so_detail($id)
    {
        try {
            $detail = So_detail::with('item', 'store', 'sale_order')->findOrFail($id);

            return $this->sendResponse($detail, 'Sale order detail fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order detail not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'sale_order_id'   => 'required|exists:sale_orders,id',
                'item_id'         => 'required|exists:items,id',
                // 'user_id'         => 'required|exists:users,id',
                // 'store_id'        => 'required|exists:stores,id',
                'quantity'        => 'required|numeric|min:0.01',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_amount'      => 'nullable|numeric|min:0',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $detail = DB::transaction(function () use ($validated) {
                $data = $validated->validated();

                // Fetch unit price from Item
                $item             = Item::findOrFail($data['item_id']);
                $data['unit_price'] = $item->sale_price;

                // Backend-calculated fields
                $data['delivered_qty']  = 0;
                $data['remaining_qty']  = $data['quantity'];
                $data['status']         = 'open';
                $data['user_id']        = auth()->id();

                $saleOrder = Sale_order::findOrFail($data['sale_order_id']);

                $data['store_id'] = $saleOrder->store_id;
                $detail = So_detail::create($data);

                // Recalculate parent SaleOrder totals
                $this->recalculateSaleOrderTotals(
                    Sale_order::findOrFail($data['sale_order_id'])
                );

                return $detail;
            });

            return $this->sendResponse(
                $detail->load('item', 'store'),
                'Sale order detail created successfully.'
            );
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'item_id'         => 'required|exists:items,id',
                'store_id'        => 'required|exists:stores,id',
                'quantity'        => 'required|numeric|min:0.01',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_amount'      => 'nullable|numeric|min:0',
                'delivered_now'   => 'nullable|numeric|min:0',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $detail = DB::transaction(function () use ($validated, $id) {
                $data = $validated->validated();

                $detail = So_detail::findOrFail($id);

                // ✅ Cast all to float for safe math
                $newQuantity    = (float) $data['quantity'];
                $deliveredNow   = (float) ($data['delivered_now'] ?? 0);
                $totalDelivered = (float) $detail->delivered_qty + $deliveredNow;

                // ✅ Block update if delivered UNLESS quantity is being increased
                if ($detail->status === 'delivered') {
                    if ($newQuantity > (float) $detail->delivered_qty) {
                        // Allow — quantity increased, reopen the line
                    } else {
                        throw new Exception('Cannot update a delivered detail line.');
                    }
                }

                // ✅ Update unit price if item changed
                if ((int) $data['item_id'] !== (int) $detail->item_id) {
                    $item               = Item::findOrFail($data['item_id']);
                    $data['unit_price'] = $item->sale_price;
                }

                // ✅ Prevent delivering more than ordered
                if ($totalDelivered > $newQuantity) {
                    throw new Exception(
                        'Delivered quantity (' . $totalDelivered . ') cannot exceed ordered quantity (' . $newQuantity . ').'
                    );
                }

                // ✅ Calculate remaining correctly
                $remaining = $newQuantity - $totalDelivered;

                // ✅ Save all calculated values into $data
                $data['delivered_now'] = $deliveredNow;
                $data['delivered_qty'] = $totalDelivered;  // accumulated total
                $data['remaining_qty'] = $remaining;        // fresh remaining

                // ✅ Auto status based on fresh $remaining
                $data['status'] = $remaining == 0 ? 'delivered' : 'open';

                $detail->update($data);

                $this->recalculateSaleOrderTotals(
                    Sale_order::findOrFail($detail->sale_order_id)
                );

                return $detail->fresh();
            });

            return $this->sendResponse(
                $detail->load('item', 'store'),
                'Sale order detail updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order detail not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 422);
        }
    }

    public function delete($id)
    {
        try {
            DB::transaction(function () use ($id) {
                // ✅ Bug 1 fixed — fetch So_detail not Sale_order
                $detail = So_detail::findOrFail($id);

                // ✅ Block if delivered
                if ($detail->status === 'delivered') {
                    throw new Exception('Cannot delete a delivered detail line.');
                }

                // ✅ Bug 3 fixed — throw exception instead of sendError
                if ($detail->delivered_qty > 0) {
                    throw new Exception('Cannot delete a detail line which has some delivered quantity.');
                }

                // ✅ Bug 2 fixed — $saleOrder defined before delete
                $saleOrder = Sale_order::findOrFail($detail->sale_order_id);

                $detail->delete();

                // ✅ Recalculate after delete
                $this->recalculateSaleOrderTotals($saleOrder);
            });

            // ✅ Bug 4 fixed — don't return deleted model
            return $this->sendResponse(null, 'Sale order detail deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order detail not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 422);
        }
    }
}
