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
    public function index()
    {
        try {
            $stores = Store::paginate(10);

            return $this->sendPaginatedResponse($stores, 'Stores fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
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
