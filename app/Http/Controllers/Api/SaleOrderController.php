<?php

namespace App\Http\Controllers\api;

use App\Models\Sale_order;
use App\Models\So_detail;
use App\Services\NumberGenerator;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SaleOrderController extends ResponseController
{
    public function index(Request $request)
    {
        try {

            $query = Sale_order::with(['customer', 'store', 'user']);

            // Global Search
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {

                    $q->where('so_no', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('amount_status', 'like', "%{$search}%")
                        ->orWhere('customer_requisitions', 'like', "%{$search}%")
                        ->orWhere('customer_reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('store', function ($store) use ($search) {
                            $store->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $query->orderBy(
                $request->input('sortField', 'id'),
                $request->input('sortOrder', 'desc')
            );

            // Column Filters
            $filters = json_decode($request->filters, true);

            if ($filters) {

                foreach ($filters as $filter) {

                    // Skip global filter
                    if ($filter['field'] === 'global') {
                        continue;
                    }
                    if ($filter['field'] === 'customer.name') {
                        $query->whereHas('customer', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter['value'] . '%');
                        });
                        continue;
                    }

                    if ($filter['field'] === 'store.name') {
                        $query->whereHas('store', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter['value'] . '%');
                        });
                        continue;
                    }

                    if ($filter['field'] === 'user.name') {
                        $query->whereHas('user', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter['value'] . '%');
                        });
                        continue;
                    }

                    switch ($filter['operator']) {

                        case 'contains':
                            $query->where($filter['field'], 'like', '%' . $filter['value'] . '%');
                            break;

                        case 'notContains':
                            $query->where($filter['field'], 'not like', '%' . $filter['value'] . '%');
                            break;

                        case 'startsWith':
                            $query->where($filter['field'], 'like', $filter['value'] . '%');
                            break;

                        case 'endsWith':
                            $query->where($filter['field'], 'like', '%' . $filter['value']);
                            break;

                        case 'equals':
                            $query->where($filter['field'], '=', $filter['value']);
                            break;

                        case 'notEquals':
                            $query->where($filter['field'], '!=', $filter['value']);
                            break;

                        case 'lt':
                            $query->where($filter['field'], '<', $filter['value']);
                            break;

                        case 'lte':
                            $query->where($filter['field'], '<=', $filter['value']);
                            break;

                        case 'gt':
                            $query->where($filter['field'], '>', $filter['value']);
                            break;

                        case 'gte':
                            $query->where($filter['field'], '>=', $filter['value']);
                            break;
                    }
                }
            }

            // Pagination
            $saleOrders = $query->paginate(
                $request->input('per_page', 10)
            );

            return $this->sendPaginatedResponse(
                $saleOrders,
                'Sale orders fetched successfully.'
            );
        } catch (Exception $e) {

            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
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
            
            // $lastOrder      = Sale_order::latest('id')->first();
            // $nextId         = $lastOrder ? $lastOrder->id + 1 : 1;
            // $data['so_no']  = 'SO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

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
                // 'customer_id'           => 'required|exists:customers,id',
                // 'store_id'              => 'required|exists:stores,id',
                // 'status'                => 'sometimes|in:open,delivered,cancelled,invoiced',
                // 'amount_status'         => 'sometimes|in:paid,unpaid,partial',
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
            So_detail::where('sale_order_id', $id)->delete();

            return $this->sendResponse($deleted_sale_order, 'Sale order deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Sale order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
