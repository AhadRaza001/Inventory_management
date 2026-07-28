<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers\api;

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
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
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

                    // Skip global filter entries — handled separately via $request->input('search')
                    if ($filter['field'] === 'global') {
                        continue;
                    }

   if (str_contains($filter['field'], '.')) {

        [$relation, $column] = explode('.', $filter['field']);

        $query->whereHas($relation, function ($q) use ($column, $filter) {

            match ($filter['operator']) {
                'startsWith' => $q->where($column, 'like', $filter['value'].'%'),
                'contains'   => $q->where($column, 'like', '%'.$filter['value'].'%'),
                'endsWith'   => $q->where($column, 'like', '%'.$filter['value']),
                'equals'     => $q->where($column, $filter['value']),
                default      => null,
            };

        });

    } else {


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
            // $data['sku']  = 'I-' . str_pad($request->sku, 6, '0', STR_PAD_LEFT);

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
}
