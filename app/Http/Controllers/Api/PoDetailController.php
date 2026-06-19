<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\api\ResponseController;
use App\Models\Item;
use App\Models\Po_detail;
use App\Models\Purchase_order;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoDetailController extends ResponseController
{

    public function index(Request $request, $id)
    {
        try {
            $query = Po_detail::with('purchase_order', 'item', 'user')->where('purchase_order_id', $id);

            // Optional filters
            if ($request->filled('purchase_order_id')) {
                $query->where('purchase_order_id', $request->purchase_order_id);
            }

            if ($request->filled('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $poDetails = $query->latest()->paginate(10);

            return $this->sendPaginatedResponse($poDetails, 'PO details fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_po_detail($id)
    {
        try {
            $poDetail = Po_detail::with('purchase_order', 'item', 'user')->findOrFail($id);

            return $this->sendResponse($poDetail, 'PO detail fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('PO detail not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'purchase_order_id' => 'required|exists:purchase_orders,id',
                'item_id'           => 'required|exists:items,id',
                'quantity'          => 'required|numeric|min:0',
                'unit_cost'         => 'nullable|numeric|min:0',
                'discount_amount'   => 'nullable|numeric|min:0',
                'tax_amount'        => 'nullable|numeric|min:0',
                'status'            => 'sometimes|in:open,delivered',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data                  = $validated->validated();
            $item_price = Item::where('id', $data['item_id'])->value('purchase_price');
            $data['unit_cost'] = $item_price;
            $data['user_id']       = auth()->id();
            $data['delivered_qty'] = 0;

            $poDetail = Po_detail::create($data);

            // Recalculate parent purchase order totals
            $this->recalculatePurchaseOrderTotals($data['purchase_order_id']);

            return $this->sendResponse(
                $poDetail->load('purchase_order', 'item', 'user'),
                'PO detail created successfully.'
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
                'quantity'        => 'required|numeric|min:0',
                'unit_cost'       => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_amount'      => 'nullable|numeric|min:0',
                'status'          => 'sometimes|in:open,delivered',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $poDetail = Po_detail::findOrFail($id);
            $poDetail->update($validated->validated());

            // Recalculate parent purchase order totals
            $this->recalculatePurchaseOrderTotals($poDetail->purchase_order_id);

            return $this->sendResponse(
                $poDetail->load('purchase_order', 'item', 'user'),
                'PO detail updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('PO detail not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $poDetail = Po_detail::findOrFail($id);

            $purchase_order_id = $poDetail->purchase_order_id;
            $deleted_po_detail = $poDetail;

            $poDetail->delete();

            // Recalculate parent purchase order totals
            $this->recalculatePurchaseOrderTotals($purchase_order_id);

            return $this->sendResponse($deleted_po_detail, 'PO detail deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('PO detail not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    private function recalculatePurchaseOrderTotals($purchase_order_id): void
    {
        $purchaseOrder = Purchase_order::findOrFail($purchase_order_id);
        $details       = Po_detail::where('purchase_order_id', $purchase_order_id)->get();

        $sub_total       = 0;
        $discount_amount = 0;
        $tax_amount      = 0;

        foreach ($details as $detail) {
            $sub_total       += $detail->quantity * ($detail->unit_cost ?? 0);
            $discount_amount += $detail->discount_amount ?? 0;
            $tax_amount      += $detail->tax_amount ?? 0;
        }

        $grand_total = ($sub_total - $discount_amount) + $tax_amount;
        $due_amount  = $grand_total - $purchaseOrder->paid_amount;

        $purchaseOrder->update([
            'sub_total'       => $sub_total,
            'discount_amount' => $discount_amount,
            'tax_amount'      => $tax_amount,   
            'grand_total'     => $grand_total,
            'due_amount'      => $due_amount,
        ]);
    }
}
