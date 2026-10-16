<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ResponseController;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupplierController extends ResponseController
{
    // columns the table is allowed to sort/filter/search on
    private $sortable = ['id', 'sku', 'name', 'phone', 'city', 'email', 'credit_limit', 'created_at'];
    private $searchable = ['sku', 'name', 'phone', 'city', 'email'];

    // GET /suppliers  (paginated, searchable, sortable, filterable)
    public function index(Request $request)
    {
        try {
            $perPage   = (int) $request->input('per_page', 10);
            $search    = $request->input('search');
            $sortField = $request->input('sortField', 'id');
            $sortOrder = $request->input('sortOrder', 'desc');
            $filters   = json_decode($request->input('filters', '[]'), true);

            if (!in_array($sortField, $this->sortable)) {
                $sortField = 'id';
            }
            if ($sortOrder !== 'asc' && $sortOrder !== 'desc') {
                $sortOrder = 'desc';
            }

            $query = Supplier::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    foreach ($this->searchable as $column) {
                        $q->orWhere($column, 'like', '%' . $search . '%');
                    }
                });
            }

            // expects filters like: [{"field": "city", "value": "Lahore"}]
            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    $field = $filter['field'] ?? null;
                    $value = $filter['value'] ?? null;

                    if ($field && $value !== null && $value !== '' && in_array($field, $this->sortable)) {
                        $query->where($field, 'like', '%' . $value . '%');
                    }
                }
            }

            $suppliers = $query->orderBy($sortField, $sortOrder)->paginate($perPage);

            return $this->sendResponse($suppliers, 'Suppliers retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Failed to fetch suppliers.', [$e->getMessage()], 500);
        }
    }

    // GET /suppliers/all  (unpaginated, for dropdowns)
    public function all()
    {
        try {
            $suppliers = Supplier::orderBy('name')->get(['id', 'sku', 'name']);

            return $this->sendResponse($suppliers, 'Suppliers retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Failed to fetch suppliers.', [$e->getMessage()], 500);
        }
    }

    // GET /suppliers/{id}
    public function single_supplier($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            return $this->sendResponse($supplier, 'Supplier retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Supplier not found.', [], 404);
        } catch (\Throwable $e) {
            return $this->sendError('Failed to fetch supplier.', [$e->getMessage()], 500);
        }
    }

    // POST /suppliers
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validator = Validator::make($request->all(), [
            'sku'          => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('suppliers', 'sku')->where('company_id', $companyId),
            ],
            'name'         => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->where('company_id', $companyId),
            ],
            'phone'        => 'required|string|max:255',
            'city'         => 'required|string|max:255',
            'address'      => 'required|string',
            'email'        => [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->where('company_id', $companyId),
            ],
            'credit_limit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $data = $validator->validated();

            // auto-generate SUP-000001 style number when sku is not given
            if (empty($data['sku'])) {
                $nextId = Supplier::max('id') + 1;
                $data['sku'] = 'SUP-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            }

            $supplier = Supplier::create($data);

            DB::commit();

            return $this->sendResponse($supplier, 'Supplier created successfully.', 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->sendError('Failed to create supplier.', [$e->getMessage()], 500);
        }
    }

    // POST /suppliers/{id}
    public function update(Request $request, $id)
    {
        $companyId = Auth::user()->company_id;

        $validator = Validator::make($request->all(), [
            'sku'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'sku')->where('company_id', $companyId)->ignore($id),
            ],
            'name'         => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->where('company_id', $companyId)->ignore($id),
            ],
            'phone'        => 'required|string|max:255',
            'city'         => 'required|string|max:255',
            'address'      => 'required|string',
            'email'        => [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->where('company_id', $companyId)->ignore($id),
            ],
            'credit_limit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->update($validator->validated());

            DB::commit();

            return $this->sendResponse($supplier, 'Supplier updated successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return $this->sendError('Supplier not found.', [], 404);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->sendError('Failed to update supplier.', [$e->getMessage()], 500);
        }
    }

    // DELETE /suppliers/{id}
    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            DB::commit();

            return $this->sendResponse([], 'Supplier deleted successfully.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return $this->sendError('Supplier not found.', [], 404);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->sendError('Failed to delete supplier.', [$e->getMessage()], 500);
        }
    }

    // DELETE /suppliers/bulk-delete   body: { "ids": [1, 2, 3] }
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            // company global scope makes sure only this company's rows are deleted
            $deleted = Supplier::whereIn('id', $request->ids)->delete();

            DB::commit();

            return $this->sendResponse(['deleted' => $deleted], 'Suppliers deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->sendError('Failed to delete suppliers.', [$e->getMessage()], 500);
        }
    }
}
