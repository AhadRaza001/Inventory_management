<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\api\ResponseController;
use App\Models\Po_detail;
use App\Models\Purchase_order;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends ResponseController
{
    public function index(Request $request)
    {
        try {
            $query = Purchase_order::with('supplier', 'store', 'user');

            // Optional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            $purchaseOrders = $query->latest()->paginate(10);

            return $this->sendPaginatedResponse($purchaseOrders, 'Purchase orders fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_purchase_order($id)
    {
        try {
            $purchaseOrder = Purchase_order::with('supplier', 'store', 'user', 'po_detail')->findOrFail($id);

            return $this->sendResponse($purchaseOrder, 'Purchase order fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Purchase order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'supplier_id'           => 'required|exists:suppliers,id',
                'store_id'              => 'required|exists:stores,id',
                'status'                => 'sometimes|in:open,delivered,cancelled',
                'customer_requisitions' => 'nullable|string',
                'customer_reference'    => 'nullable|string',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data = $validated->validated();

            // $lastOrder     = Purchase_order::latest('id')->first();
            // $nextId        = $lastOrder ? $lastOrder->id + 1 : 1;
            // $data['po_no'] = 'PO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // TODO: Calculate these from line items when po_details is implemented
            $data['sub_total']       = 0;
            $data['discount_amount'] = 0;
            $data['tax_amount']      = 0;
            $data['grand_total']     = 0;
            $data['paid_amount']     = 0;
            $data['due_amount']      = 0;

            $data['user_id'] = auth()->id();

            $purchaseOrder = Purchase_order::create($data);

            return $this->sendResponse(
                $purchaseOrder->load('supplier', 'store', 'user'),
                'Purchase order created successfully.'
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
                'supplier_id'           => 'required|exists:suppliers,id',
                'store_id'              => 'required|exists:stores,id',
                'status'                => 'sometimes|in:open,delivered,cancelled',
                'customer_requisitions' => 'nullable|string',
                'customer_reference'    => 'nullable|string',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $purchaseOrder = Purchase_order::findOrFail($id);
            $purchaseOrder->update($validated->validated());

            return $this->sendResponse(
                $purchaseOrder->load('supplier', 'store', 'user'),
                'Purchase order updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Purchase order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $purchaseOrder = Purchase_order::findOrFail($id);

            if (!in_array($purchaseOrder->status, ['open', 'cancelled'])) {
                return $this->sendError(
                    'Cannot delete purchase order.',
                    'Only orders with status "open" or "cancelled" can be deleted.',
                    422
                );
            }

            $deleted_purchase_order = $purchaseOrder;
            $purchaseOrder->delete();
            Po_detail::where('purchase_order_id', $id)->delete();

            return $this->sendResponse($deleted_purchase_order, 'Purchase order deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Purchase order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
