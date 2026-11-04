<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ResponseController;
use App\Models\Po_detail;
use App\Models\Purchase_order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends ResponseController
{
    // Columns that can be sorted / filtered from the table (whitelist)
    private const PO_COLUMNS = [
        'id',
        'po_no',
        'status',
        'store_id',
        'supplier_id',
        'sub_total',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'due_amount',
        'customer_reference',
        'customer_requisitions',
        'created_at',
        'updated_at',
    ];

    // Related columns that can be filtered, e.g. field "supplier.name"
    private const RELATION_COLUMNS = [
        'supplier' => ['name', 'sku'],
        'store'    => ['name'],
        'user'     => ['name'],
    ];

    public function index(Request $request)
    {
        try {

            $query = Purchase_order::with(['supplier', 'store', 'user']);

            // Optional exact filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            // Global Search
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {

                    $q->where('po_no', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('customer_reference', 'like', "%{$search}%")
                        ->orWhere('customer_requisitions', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplier) use ($search) {
                            $supplier->where('name', 'like', "%{$search}%");
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

                    if ($filter['field'] === 'supplier.name') {
                        $query->whereHas('supplier', function ($q) use ($filter) {
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

                        case 'dateIs':

                            $date = Carbon::parse($filter['value'])
                                ->setTimezone('Asia/Karachi')
                                ->toDateString();

                            $query->whereDate($filter['field'], '=', $date);
                            break;

                        case 'dateIsNot':
                            $timezone = $request->input('timezone', 'UTC');

                            $date = Carbon::parse($filter['value'])
                                ->setTimezone('Asia/Karachi')
                                ->toDateString();

                            $query->whereDate($filter['field'], '!=', $date);
                            break;

                        case 'dateBefore':

                            $date = Carbon::parse($filter['value'])
                                ->setTimezone('Asia/Karachi')
                                ->toDateString();

                            $query->whereDate($filter['field'], '<', $date);
                            break;

                        case 'dateAfter':

                            $date = Carbon::parse($filter['value'])
                                ->setTimezone('Asia/Karachi')
                                ->toDateString();

                            $query->whereDate($filter['field'], '>', $date);
                            break;
                    }
                }
            }

            // Pagination
            $purchaseOrders = $query->paginate(
                $request->input('per_page', 10)
            );

            return $this->sendPaginatedResponse(
                $purchaseOrders,
                'Purchase orders fetched successfully.'
            );
        } catch (Exception $e) {

            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }

    private function applyColumnFilter($query, string $field, string $operator, $value): void
    {
        // operator => [sql operator, value pattern]
        $map = [
            'contains'    => ['like', "%$value%"],
            'notContains' => ['not like', "%$value%"],
            'startsWith'  => ['like', "$value%"],
            'endsWith'    => ['like', "%$value"],
            'equals'      => ['=', $value],
            'notEquals'   => ['!=', $value],
            'lt'          => ['<', $value],
            'lte'         => ['<=', $value],
            'gt'          => ['>', $value],
            'gte'         => ['>=', $value],
        ];

        if (!isset($map[$operator])) {
            return;
        }

        [$sqlOperator, $sqlValue] = $map[$operator];

        // Related column, e.g. "supplier.name"
        if (str_contains($field, '.')) {
            [$relation, $column] = explode('.', $field, 2);

            if (in_array($column, self::RELATION_COLUMNS[$relation] ?? [], true)) {
                $query->whereHas($relation, fn($q) => $q->where($column, $sqlOperator, $sqlValue));
            }
            return;
        }

        // Own column
        if (in_array($field, self::PO_COLUMNS, true)) {
            $query->where($field, $sqlOperator, $sqlValue);
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
                'po_no' => 'required|unique:purchase_orders,po_no',
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
            } else {
                $deleted_purchase_order = $purchaseOrder;
                $purchaseOrder->delete();
                Po_detail::where('purchase_order_id', $id)->delete();
                return $this->sendResponse($deleted_purchase_order, 'Purchase order deleted successfully.');
            }
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Purchase order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:purchase_orders,id',
        ]);

        Purchase_order::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Purchase Orders deleted successfully.'
        ]);
    }
}
