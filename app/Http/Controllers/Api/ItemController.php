<?php

// namespace App\Http\Controllers;

namespace App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\NumberGenerator;
use Exception;
use Illuminate\Support\Facades\Validator;

class ItemController extends ResponseController
{

    public function index(Request $request)
    {
        try {
            $query = Item::with(['category', 'unit']);

            // Global Search
            if ($request->filled('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->input('sortField', 'id');
            $sortOrder = strtolower($request->input('sortOrder', 'desc'));

            // Allowed sorting columns
            $allowedSortFields = [
                'id' => 'items.id',
                'name' => 'items.name',
                'sku' => 'items.sku',
                'category.name' => 'categories.name',
                'unit.name' => 'units.name',
            ];

            if (isset($allowedSortFields[$sortField])) {

                $column = $allowedSortFields[$sortField];

                if ($sortField === 'category.name') {
                    $query->leftJoin(
                        'categories',
                        'items.category_id',
                        '=',
                        'categories.id'
                    );
                }

                if ($sortField === 'unit.name') {
                    $query->leftJoin(
                        'units',
                        'items.unit_id',
                        '=',
                        'units.id'
                    );
                }

                $query->orderBy(
                    $column,
                    $sortOrder === 'asc' || $sortOrder === '1'
                        ? 'asc'
                        : 'desc'
                );
            }

            // Column Filters
            $filters = json_decode($request->input('filters', '[]'), true);

            if ($filters) {

                foreach ($filters as $filter) {

                    if (
                        empty($filter['field']) ||
                        !isset($filter['operator']) ||
                        !array_key_exists('value', $filter)
                    ) {
                        continue;
                    }

                    // Skip global filter
                    if ($filter['field'] === 'global') {
                        continue;
                    }

                    if (str_contains($filter['field'], '.')) {

                        [$relation, $column] = explode('.', $filter['field'], 2);

                        // Only allow known relationships and columns
                        $allowedRelations = [
                            'category' => ['name'],
                            'unit' => ['name'],
                        ];

                        if (
                            !isset($allowedRelations[$relation]) ||
                            !in_array($column, $allowedRelations[$relation])
                        ) {
                            continue;
                        }

                        $query->whereHas($relation, function ($q) use ($column, $filter) {

                            match ($filter['operator']) {
                                'startsWith' => $q->where($column, 'like', $filter['value'] . '%'),
                                'contains'   => $q->where($column, 'like', '%' . $filter['value'] . '%'),
                                'endsWith'   => $q->where($column, 'like', '%' . $filter['value']),
                                'equals'     => $q->where($column, '=', $filter['value']),
                                'notEquals'  => $q->where($column, '!=', $filter['value']),
                                default      => null,
                            };
                        });
                    } else {

                        // Allowed item columns
                        $allowedColumns = [
                            'name',
                            'sku',
                            'purchase_price',
                            'sale_price',
                        ];

                        if (!in_array($filter['field'], $allowedColumns)) {
                            continue;
                        }

                        $field = 'items.' . $filter['field'];

                        switch ($filter['operator']) {

                            case 'contains':
                                $query->where($field, 'like', '%' . $filter['value'] . '%');
                                break;

                            case 'notContains':
                                $query->where($field, 'not like', '%' . $filter['value'] . '%');
                                break;

                            case 'startsWith':
                                $query->where($field, 'like', $filter['value'] . '%');
                                break;

                            case 'endsWith':
                                $query->where($field, 'like', '%' . $filter['value']);
                                break;

                            case 'equals':
                                $query->where($field, '=', $filter['value']);
                                break;

                            case 'notEquals':
                                $query->where($field, '!=', $filter['value']);
                                break;

                            case 'lt':
                                $query->where($field, '<', $filter['value']);
                                break;

                            case 'lte':
                                $query->where($field, '<=', $filter['value']);
                                break;

                            case 'gt':
                                $query->where($field, '>', $filter['value']);
                                break;

                            case 'gte':
                                $query->where($field, '>=', $filter['value']);
                                break;
                        }
                    }
                }
            }

            // Pagination
            $items = $query->paginate(
                $request->input('per_page', 10)
            );

            return $this->sendPaginatedResponse(
                $items,
                'Items fetched successfully.'
            );
        } catch (Exception $e) {

            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }


    public function single_item($id)
    {
        try {
            $item = Item::with('category')->findOrFail($id);

            return $this->sendResponse($item, 'Item fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Item not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'           => 'required|string|max:100',
                'sku'            => 'required|string|unique:items,sku',
                'description'    => 'required|string',
                'purchase_price' => 'required|numeric',
                'sale_price'     => 'required|numeric',
                'status'         => 'required',
                'barcode'        => 'required',
                'category_id'    => 'required|exists:categories,id',
                'unit_id'        => 'required|exists:units,id',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data = $validated->validated();
            $item = Item::create($data);

            return $this->sendResponse($item, 'Item created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'           => 'required|string|max:100',
                'description'    => 'required|string',
                'purchase_price' => 'required|numeric',
                'sale_price'     => 'required|numeric',
                'status'         => 'required',
                'barcode'        => 'required',
                'unit_id'        => 'required|exists:units,id',
                'category_id'        => 'required|exists:categories,id',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $item = Item::findOrFail($id);
            $data = $validated->validated();
            $data['sku']  =  str_pad($request->sku, 6, '0', STR_PAD_LEFT);
            $item->update($data);

            return $this->sendResponse($item, 'Item updated successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Item not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function delete($id)
    {
        try {
            $item = Item::findOrFail($id);
            $deleted = $item;
            $item->delete();

            return $this->sendResponse($deleted, 'Item deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Item not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function getBySku($sku)
    {
        $Esku = str_pad($sku, 6, '0', STR_PAD_LEFT);

        $item = Item::where('sku', $Esku)->first();

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => 'Item not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $item
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:items,id',
        ]);

        Item::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Items deleted successfully.'
        ]);
    }
}
