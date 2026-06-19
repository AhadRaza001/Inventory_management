<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers\api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Exception;
use Illuminate\Support\Facades\Validator;

class ItemController extends ResponseController
{
    public function index()
    {
        try {
            $items = Item::with('category', 'unit')->paginate(10);

            return $this->sendPaginatedResponse($items, 'Items fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
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
            $item = Item::create($validated->validated());

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
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $item = Item::findOrFail($id);
            $item->update($validated->validated());

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
}
