<?php

namespace App\Http\Controllers\api;


use App\Http\Controllers\api\ResponseController;
use App\Models\Customer;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends ResponseController
{
    public function index(Request $request)
    {
        try {
            $query = Customer::query();

            // Optional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('city')) {
                $query->where('city', $request->city);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('customer_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $customers = $query->latest()->paginate(10);

            return $this->sendPaginatedResponse($customers, 'Customers fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_customer($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            return $this->sendResponse($customer, 'Customer fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Customer not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'         => 'required|string|unique:customers,name',
                'phone'        => 'required|string|unique:customers,phone',
                'email'        => 'nullable|email|unique:customers,email',
                'city'         => 'required|string',
                'address'      => 'nullable|string',
                'credit_limit' => 'nullable|numeric|min:0',
                'status'       => 'sometimes|in:active,inactive,blocked',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data = $validated->validated();

            // Generate unique customer_no
            $lastCustomer        = Customer::latest('id')->first();
            $nextId              = $lastCustomer ? $lastCustomer->id + 1 : 1;
            $data['customer_no'] = 'CUST-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            $customer = Customer::create($data);

            return $this->sendResponse($customer, 'Customer created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'name'         => 'required|string|unique:customers,name,' . $id,
                'phone'        => 'required|string|unique:customers,phone,' . $id,
                'email'        => 'nullable|email|unique:customers,email,' . $id,
                'city'         => 'required|string',
                'address'      => 'nullable|string',
                'credit_limit' => 'nullable|numeric|min:0',
                'status'       => 'sometimes|in:active,inactive,blocked',
            ]
        );

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $customer = Customer::findOrFail($id);
            $customer->update($validated->validated());

            return $this->sendResponse($customer, 'Customer updated successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Customer not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            if ($customer->status === 'active') {
                return $this->sendError(
                    'Cannot delete customer.',
                    'Only inactive or blocked customers can be deleted.',
                    422
                );
            }

            $deleted_customer = $customer;
            $customer->delete();

            return $this->sendResponse($deleted_customer, 'Customer deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Customer not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
