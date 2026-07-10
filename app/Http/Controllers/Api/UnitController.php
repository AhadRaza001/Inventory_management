<?php

namespace App\Http\Controllers\api;

use App\Models\Unit;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends ResponseController
{
    public function index(Request $request)
    {
        try {
            $query = Unit::query();

            // Global Search
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
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
            $units = $query->paginate(
                $request->input('per_page', 10)
            );

            return $this->sendPaginatedResponse(
                $units,
                'Units fetched successfully.'
            );
        } catch (Exception $e) {
            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }

    public function single_unit($id)
    {
        try {
            $unit = Unit::findOrFail($id);

            return $this->sendResponse($unit, 'Unit fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Unit not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'   => 'required|string|max:50',
                'symbol' => 'required|string|max:10',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            
            $unit = Unit::create($validated->validated());

            return $this->sendResponse($unit, 'Unit created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'   => 'required|string|max:50',
                'symbol' => 'required|string|max:10',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $unit = Unit::findOrFail($id);
            $unit->update($validated->validated());

            return $this->sendResponse($unit, 'Unit updated successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Unit not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            $deleted_unit = $unit;
            $unit->delete();

            return $this->sendResponse($deleted_unit, 'Unit deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Unit not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
