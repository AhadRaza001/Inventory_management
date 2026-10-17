<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupplierController extends ResponseController
{
    // columns the table is allowed to sort/filter on
    private $columns = ['id', 'sku', 'name', 'phone', 'city', 'email', 'address', 'credit_limit', 'created_at'];

    public function index(Request $request)
    {
        try {
            $query = Supplier::query();

            // Global Search
            if ($request->input('search')) {

                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');

            if (!in_array($sortField, $this->columns)) {
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

                    // Skip global filter entries — handled separately via $request->input('search')
                    if ($filter['field'] === 'global') {
                        continue;
                    }

                    if (!in_array($filter['field'], $this->columns)) {
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
            $suppliers = $query->paginate(
                $request->input('per_page', 10)
            );

            return $this->sendPaginatedResponse(
                $suppliers,
                'Suppliers fetched successfully.'
            );
        } catch (Exception $e) {
            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }

    // unpaginated, for dropdowns
    public function all()
    {
        try {
            $suppliers = Supplier::orderBy('name')->get(['id', 'sku', 'name']);

            return $this->sendResponse($suppliers, 'Suppliers fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_supplier($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            return $this->sendResponse($supplier, 'Supplier fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Supplier not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = Validator::make(
            $request->all(),
            [
                'sku' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('suppliers', 'sku')->where('company_id', $companyId),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('suppliers', 'name')->where('company_id', $companyId),
                ],
                'phone'   => 'required|string|max:255',
                'city'    => 'required|string|max:255',
                'address' => 'required|string',
                'email'   => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('suppliers', 'email')->where('company_id', $companyId),
                ],
                'credit_limit' => 'required|numeric|min:0',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data = $validated->validated();

            // auto-generate SUP-000001 style number when sku is not given
            if (empty($data['sku'])) {
                $nextId = Supplier::max('id') + 1;
                $data['sku'] = 'SUP-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }

            $supplier = Supplier::create($data);

            return $this->sendResponse($supplier, 'Supplier created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $companyId = Auth::user()->company_id;

        $validated = Validator::make(
            $request->all(),
            [
                'sku' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('suppliers', 'sku')->where('company_id', $companyId)->ignore($id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('suppliers', 'name')->where('company_id', $companyId)->ignore($id),
                ],
                'phone'   => 'required|string|max:255',
                'city'    => 'required|string|max:255',
                'address' => 'required|string',
                'email'   => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('suppliers', 'email')->where('company_id', $companyId)->ignore($id),
                ],
                'credit_limit' => 'required|numeric|min:0',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->update($validated->validated());

            return $this->sendResponse($supplier, 'Supplier updated successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Supplier not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $deleted_supplier = $supplier;
            $supplier->delete();

            return $this->sendResponse($deleted_supplier, 'Supplier deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Supplier not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:suppliers,id',
        ]);

        // company global scope makes sure only this company's rows are deleted
        Supplier::whereIn('id', $request->ids)->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Suppliers deleted successfully.'
        ]);
    }
}
