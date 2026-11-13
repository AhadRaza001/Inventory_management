<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Store;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreController extends ResponseController
{
    public function index(Request $request)
    {
        try {
            $query = Store::query();

            // columns the table is allowed to sort and filter on
            $allowedFields = ['id', 'name', 'phone', 'address', 'description', 'created_at'];

            // Global Search
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');

            if (!in_array($sortField, $allowedFields)) {
                $sortField = 'id';
            }

            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            $query->orderBy($sortField, $sortOrder);

            // Column Filters
            $filters = json_decode($request->filters, true);

            if ($filters) {
                foreach ($filters as $filter) {

                    // Skip global filter entries, handled separately via $request->input('search')
                    if ($filter['field'] === 'global') {
                        continue;
                    }

                    // Skip unknown columns
                    if (!in_array($filter['field'], $allowedFields)) {
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

                        // Date filters (created_at)
                        case 'dateIs':
                            $query->whereDate($filter['field'], '=', $filter['value']);
                            break;

                        case 'dateIsNot':
                            $query->whereDate($filter['field'], '!=', $filter['value']);
                            break;

                        case 'dateBefore':
                            $query->whereDate($filter['field'], '<', $filter['value']);
                            break;

                        case 'dateAfter':
                            $query->whereDate($filter['field'], '>', $filter['value']);
                            break;
                    }
                }
            }

            // Pagination
            $stores = $query->paginate(
                $request->input('per_page', 10)
            );

            return $this->sendPaginatedResponse(
                $stores,
                'Stores fetched successfully.'
            );
        } catch (Exception $e) {
            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }

    public function single_store($id)
    {
        try {

            $store = Store::findOrFail($id);

            return $this->sendResponse(
                $store,
                'Store fetched successfully.'
            );
        } catch (ModelNotFoundException $e) {

            return $this->sendError(
                'Store not found.',
                $e->getMessage(),
                404
            );
        } catch (Exception $e) {

            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'        => 'required|string|unique:stores,name|max:100',
                'description' => 'nullable|string',
                'address'     => 'required|string',
                'phone'       => 'nullable|string|max:20',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $store = Store::create($validated->validated());

            return $this->sendResponse($store, 'Store created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'        => 'required|string|max:100|unique:stores,name,' . $id,
                'description' => 'nullable|string',
                'address'     => 'required|string',
                'phone'       => 'nullable|string|max:20',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $store = Store::findOrFail($id);
            $store->update($validated->validated());

            return $this->sendResponse($store, 'Store updated successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Store not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function delete($id)
    {
        try {

            $store = Store::findOrFail($id);

            $deleted_store = $store;

            $store->delete();

            return $this->sendResponse(
                $deleted_store,
                'Store deleted successfully.'
            );
        } catch (ModelNotFoundException $e) {

            return $this->sendError(
                'Store not found.',
                $e->getMessage(),
                404
            );
        } catch (Exception $e) {

            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }
}
