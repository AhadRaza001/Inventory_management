<?php

namespace App\Http\Controllers\api;

use App\Models\Sale_order;
use App\Models\So_detail;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SaleOrderController extends ResponseController
{
    public function index(Request $request)
    {
        try {
            $query = Sale_order::with('customer', 'store', 'user');

            // Optional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('amount_status')) {
                $query->where('amount_status', $request->amount_status);
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $saleOrders = $query->latest()->paginate(10);

            return $this->sendPaginatedResponse($saleOrders, 'Sale orders fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_sale_order($id)
    {
        try {
            $saleOrder = Sale_order::with('customer', 'store', 'user', 'so_detail')->findOrFail($id);

            return $this->sendResponse($saleOrder, 'Sale order fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'customer_id'           => 'required|exists:customers,id',
                'store_id'              => 'required|exists:stores,id',
                'status'                => 'sometimes|in:open,delivered,cancelled,invoiced',
                'amount_status'         => 'sometimes|in:paid,unpaid,partial',
                'customer_requisitions' => 'nullable|string',
                'customer_reference'    => 'nullable|string',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data = $validated->validated();
            
            $lastOrder      = Sale_order::latest('id')->first();
            $nextId         = $lastOrder ? $lastOrder->id + 1 : 1;
            $data['so_no']  = 'SO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            
            // TODO: Calculate these from line items when sale_order_items is implemented
            $data['sub_total']       = 0;
            $data['discount_amount'] = 0;
            $data['tax_amount']      = 0;
            $data['grand_total']     = 0;
            $data['paid_amount']     = 0;
            $data['due_amount']      = 0;

            $data['user_id'] = auth()->id();

            $saleOrder = Sale_order::create($data);

            return $this->sendResponse(
                $saleOrder->load('customer', 'store', 'user'),
                'Sale order created successfully.'
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
                'customer_id'           => 'required|exists:customers,id',
                'store_id'              => 'required|exists:stores,id',
                'status'                => 'sometimes|in:open,delivered,cancelled,invoiced',
                'amount_status'         => 'sometimes|in:paid,unpaid,partial',
                'customer_requisitions' => 'nullable|string',
                'customer_reference'    => 'nullable|string',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $saleOrder = Sale_order::findOrFail($id);
            $saleOrder->update($validated->validated());

            return $this->sendResponse(
                $saleOrder->load('customer', 'store', 'user'),
                'Sale order updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $saleOrder = Sale_order::findOrFail($id);
            
            if (!in_array($saleOrder->status, ['open', 'cancelled'])) {
                return $this->sendError(
                    'Cannot delete sale order.',
                    'Only orders with status "open" or "cancelled" can be deleted.',
                    422
                    );
                    }
                    
                    $deleted_sale_order = $saleOrder;
                    $saleOrder->delete();
                    So_detail::where('sale_order_id',$id)->delete();

            return $this->sendResponse($deleted_sale_order, 'Sale order deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
