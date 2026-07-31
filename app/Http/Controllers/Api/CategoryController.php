<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CategoryController extends ResponseController
{
    // CategoryController
    public function all()
    {
        try {
            $categories = Category::select('id', 'name')->orderBy('name')->get();
            return $this->sendResponse($categories, 'Categories fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $query = Category::query();
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%");
                });
            }
            $query->orderBy($request->input('sortField', 'id'), $request->input('sortOrder', 'desc'));

            //column filter
            if ($request->filters) {

                $filters = json_decode($request->filters, true);
                foreach ($filters as $filter) {

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


            $categories = $query->paginate(
                $request->input('per_page', 10)
            );
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
            $category = Category::create($validated->validated());
            return $this->sendResponse($category, 'Category created successfully.');
        } catch (QueryException $e) {

            // Duplicate entry error
            if ($e->errorInfo[1] == 1062) {
                return $this->sendError(
                    'Category already exists.',
                    null,
                    422
                );
            }

            return $this->sendError(
                'Database error occurred.',
                null,
                500
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
