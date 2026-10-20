<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ResponseController;
use App\Models\Po_detail;
use App\Models\Purchase_order;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends ResponseController
{
    // public function index(Request $request)
    // {
    //     try {
    //         $query = Purchase_order::with('supplier', 'store', 'user');

    //         // Optional filters
    //         if ($request->filled('status')) {
    //             $query->where('status', $request->status);
    //         }

    //         if ($request->filled('store_id')) {
    //             $query->where('store_id', $request->store_id);
    //         }

    //         if ($request->filled('supplier_id')) {
    //             $query->where('supplier_id', $request->supplier_id);
    //         }

    //         $purchaseOrders = $query->latest()->paginate(10);

    //         return $this->sendPaginatedResponse($purchaseOrders, 'Purchase orders fetched successfully.');
    //     } catch (Exception $e) {
    //         return $this->sendError('Something went wrong.', $e->getMessage(), 500);
    //     }
    // }




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
            $query = Purchase_order::with('supplier', 'store', 'user');

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

            // Global search
            if ($request->filled('search')) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('po_no', 'like', "%$search%")
                        ->orWhere('customer_reference', 'like', "%$search%")
                        ->orWhere('customer_requisitions', 'like', "%$search%")
                        ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%$search%"))
                        ->orWhereHas('store', fn($s) => $s->where('name', 'like', "%$search%"));
                });
            }

            // Column filters
            if ($request->filled('filters')) {
                $filters = json_decode($request->filters, true);

                if (is_array($filters)) {
                    foreach ($filters as $filter) {
                        if (
                            !isset($filter['field'], $filter['operator'], $filter['value']) ||
                            $filter['field'] === 'global'
                        ) {
                            continue;
                        }

                        $this->applyColumnFilter(
                            $query,
                            $filter['field'],
                            $filter['operator'],
                            $filter['value']
                        );
                    }
                }
            }

            // Sorting (only whitelisted columns, otherwise fall back to id desc)
            $sortField = $request->input('sortField', 'id');
            if (!in_array($sortField, self::PO_COLUMNS, true)) {
                $sortField = 'id';
            }
            $sortOrder = strtolower($request->input('sortOrder', 'desc')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortOrder);

            $perPage = (int) $request->input('per_page', 10);
            $purchaseOrders = $query->paginate($perPage > 0 ? $perPage : 10);

            return $this->sendPaginatedResponse($purchaseOrders, 'Purchase orders fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
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
