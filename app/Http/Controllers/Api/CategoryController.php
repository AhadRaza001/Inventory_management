<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CategoryController extends ResponseController
{
    public function index()
    {
        try {
            $categories = Category::paginate(10);
            return $this->sendPaginatedResponse($categories, 'Categories fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function single_category($id)
    {
        try {
            $category  = Category::findOrFail($id);
            return $this->sendResponse($category, 'Category fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Products not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:32',
                'description' => 'required|string',
            ]
        );
        if ($validated->fails()) {
            return $this->validationError(
                $validated->errors()
            );
        }
        try {
            $category = Category::create($validated);
            return $this->sendResponse($category, 'Category created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:32',
                'description' => 'required|string',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError(
                $validated->errors()
            );
        }

        try {
            $category = Category::findOrFail($id);

            $category->update($validated->validated());

            return $this->sendResponse(
                $category,
                'Category updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Category not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $category = Category::findOrFail($id);
            $deleted_category = $category;
            $category->delete();
            return $this->sendResponse($deleted_category, 'Category deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Category not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
