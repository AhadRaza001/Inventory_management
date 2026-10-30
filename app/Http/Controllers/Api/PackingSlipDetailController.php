<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Packing_slip;
use App\Models\Packing_slip_detail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PackingSlipDetailController extends ResponseController
{
    public function index($id)
    {
        try {
            $packingSlip = Packing_slip::where('sale_order_id', $id)->orwhere('purchase_order_id', $id)->firstOrFail();

            $details = Packing_slip_detail::with('item')
                ->where('packing_slip_id', $packingSlip->id)
                ->get();

            return $this->sendResponse($details, 'Packing slip details retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Packing slip not found.', [], 404);
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
}
