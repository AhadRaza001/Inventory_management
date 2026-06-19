<?php

namespace App\Http\Controllers\api;

use App\Models\Unit;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends ResponseController
{
    public function index()
    {
        try {
            $units = Unit::paginate(10);

            return $this->sendPaginatedResponse($units, 'Units fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
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
