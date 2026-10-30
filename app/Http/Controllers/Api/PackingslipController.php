<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Item_ledger;
use App\Models\Packing_slip;
use App\Models\Packing_slip_detail;
use App\Models\Purchase_order;
use App\Models\Sale_order;
use App\Models\So_detail;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PackingslipController  extends ResponseController
{
    private const SLIP_RELATIONS = [
        'sale_order',
        'purchase_order',
        'customer',
        'store',
        'user',
        'packing_slip_details.item',
        'packing_slip_details.so_detail',
        'packing_slip_details.po_detail',
    ];

    /* -----------------------------------------------------------------
     |  Listing / fetching
     | ----------------------------------------------------------------- */

    // Slips of one sale order (existing route keeps working)
    public function index($id)
    {
        return $this->listByColumn('sale_order_id', $id);
    }

    // Slips of one purchase order (new route)
    public function indexByPurchaseOrder($id)
    {
        return $this->listByColumn('purchase_order_id', $id);
    }

    private function listByColumn(string $column, $id)
    {
        try {
            $slips = Packing_slip::with('sale_order', 'purchase_order', 'store', 'user')
                ->where($column, $id)
                ->latest()
                ->paginate(20);

            return $this->sendPaginatedResponse($slips, 'Packing slips fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function single_packing_slip($id)
    {
        try {
            $packingSlip = Packing_slip::with(self::SLIP_RELATIONS)->findOrFail($id);

            return $this->sendResponse($packingSlip, 'Packing slip fetched successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Packing slip not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function getBySaleOrder(Request $request)
    {
        try {
            $packingSlip = Packing_slip::with(self::SLIP_RELATIONS)
                ->where('type', 'sale')
                ->where('sale_order_id', $request->sale_order_id)
                ->get();

            return $this->sendResponse($packingSlip, 'Packing slip fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    public function getByPurchaseOrder(Request $request)
    {
        try {
            $packingSlip = Packing_slip::with(self::SLIP_RELATIONS)
                ->where('type', 'purchase')
                ->where('purchase_order_id', $request->purchase_order_id)
                ->get();

            return $this->sendResponse($packingSlip, 'Packing slip fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    /* -----------------------------------------------------------------
     |  Store
     | ----------------------------------------------------------------- */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'type'                   => 'required|in:sale,purchase',
            'sale_order_id'          => 'required_if:type,sale|nullable|exists:sale_orders,id',
            'purchase_order_id'      => 'required_if:type,purchase|nullable|exists:purchase_orders,id',
            'store_id'               => 'required|exists:stores,id',
            'vehicle_no'             => 'nullable|string',
            'driver_name'            => 'nullable|string',
            'driver_phone'           => 'nullable|string',
            'remarks'                => 'nullable|string',
            'details'                => 'required|array|min:1',
            'details.*.so_detail_id' => 'required_if:type,sale|nullable|exists:so_details,id',
            'details.*.po_detail_id' => 'required_if:type,purchase|nullable|exists:po_details,id',
            'details.*.item_id'      => 'required|exists:items,id',
            'details.*.ordered_qty'  => 'required|numeric|min:0',
            'details.*.packed_qty'   => 'required|numeric|min:0',
        ]);

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        try {
            $data   = $validated->validated();
            $isSale = $data['type'] === 'sale';

            // The source order must be open
            $order = $isSale
                ? Sale_order::findOrFail($data['sale_order_id'])
                : Purchase_order::findOrFail($data['purchase_order_id']);

            $orderNo = $isSale
                ? $order->so_no
                : $order->po_no;

            if ($order->status !== 'open') {
                return $this->sendError(
                    'Cannot create packing slip.',
                    'Packing slips can only be created for open orders.',
                    422
                );
            }

            DB::beginTransaction();

            // Unique slip number: PS-000001 for sale, GRN-000001 for purchase
            $lastId = Packing_slip::lockForUpdate()->max('id') ?? 0;
            $psNo   = ($isSale ? 'PS-' : 'GRN-') . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);

            $packingSlip = Packing_slip::create([
                'type'              => $data['type'],
                'sale_order_id'     => $isSale ? $data['sale_order_id'] : null,
                'purchase_order_id' => $isSale ? null : $data['purchase_order_id'],
                'store_id'          => $data['store_id'],
                'user_id'           => auth()->id(),
                'ps_no'             => $psNo,
                'status'            => 'draft',
                'vehicle_no'        => $data['vehicle_no'] ?? null,
                'driver_name'       => $data['driver_name'] ?? null,
                'driver_phone'      => $data['driver_phone'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
            ]);




            foreach ($data['details'] as $detail) {
                // Sale takes stock out, so it must be available. Purchase adds stock.
                if ($isSale) {
                    $stock = $this->stockBalance($detail['item_id'], $data['store_id']);

                    if ($stock < $detail['packed_qty']) {
                        DB::rollBack();
                        $item = Item::find($detail['item_id']);

                        return $this->sendError(
                            'Insufficient stock.',
                            'Not enough stock available for Sku: ' . $item->sku . ' ' . $item->name,
                            422
                        );
                    }
                }

                Packing_slip_detail::create([
                    'packing_slip_id' => $packingSlip->id,
                    'so_detail_id'    => $isSale ? $detail['so_detail_id'] : null,
                    'po_detail_id'    => $isSale ? null : $detail['po_detail_id'],
                    'item_id'         => $detail['item_id'],
                    'ordered_qty'     => $detail['ordered_qty'],
                    'packed_qty'      => $detail['packed_qty'],
                ]);
            }

            DB::commit();

            return $this->sendResponse(
                $packingSlip->load(self::SLIP_RELATIONS),
                'Packing slip created successfully.'
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->sendError('Order not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    /* -----------------------------------------------------------------
     |  Dispatch
     |  sale     -> ledger OUT, stock decreases, order becomes 'delivered'
     |  purchase -> ledger IN,  stock increases, order becomes 'received'
     | ----------------------------------------------------------------- */

    public function dispatch($id)
    {
        try {
            $slip = Packing_slip::with([
                'packing_slip_details.so_detail',
                'packing_slip_details.po_detail',
            ])->findOrFail($id);

            if ($slip->status !== 'draft') {
                return $this->sendError(
                    'Cannot dispatch packing slip.',
                    'Only draft packing slips can be dispatched.',
                    422
                );
            }

            $isSale = $slip->type === 'sale';

            /*
         * Sale and purchase both need to track the quantity
         * already processed on the order line.
         *
         * For PO details, the actual column is delivered_qty.
         */
            $doneField = 'delivered_qty';
            $ledgerType = $isSale ? 'OUT' : 'IN';
            $doneStatus = 'delivered';

            DB::beginTransaction();

            foreach ($slip->packing_slip_details as $detail) {

                if (!$detail->packed_qty || $detail->packed_qty <= 0) {
                    continue;
                }

                /*
             * Get the corresponding order detail.
             */
                $line = $isSale
                    ? $detail->so_detail
                    : $detail->po_detail;

                if (!$line) {
                    DB::rollBack();

                    return $this->sendError(
                        'Order line missing.',
                        'A packing slip line has no linked order line.',
                        422
                    );
                }

                /*
             * Current quantity already processed.
             */
                $currentDone = (float) ($line->$doneField ?? 0);

                /*
             * Quantity after this packing slip.
             */
                $newDone = $currentDone + (float) $detail->packed_qty;

                /*
             * Never allow more than ordered quantity.
             */
                if ($newDone > (float) $line->quantity) {
                    DB::rollBack();

                    return $this->sendError(
                        'Quantity exceeds ordered quantity.',
                        'You cannot process more than the ordered quantity.',
                        422
                    );
                }

                /*
             * Sale order:
             * Check available stock before creating the OUT ledger.
             */
                if ($isSale) {
                    $stock = $this->stockBalance(
                        $detail->item_id,
                        $slip->store_id
                    );

                    if ($stock < $detail->packed_qty) {
                        DB::rollBack();

                        return $this->sendError(
                            'Insufficient stock.',
                            "Item ID {$detail->item_id} has only {$stock} units available but {$detail->packed_qty} required.",
                            422
                        );
                    }
                }

                $item = Item::findOrFail($detail->item_id);

                // Get source order
                $order = $isSale
                    ? Sale_order::findOrFail($slip->sale_order_id)
                    : Purchase_order::findOrFail($slip->purchase_order_id);

                // Get order number
                $orderNo = $isSale
                    ? $order->so_no
                    : $order->po_no;

                /*
             * Create inventory ledger entry.
             */
                Item_ledger::create([
                    'item_id'          => $detail->item_id,
                    'store_id'         => $slip->store_id,
                    'transaction_type' => $ledgerType,
                    'reference_type'   => $slip->type,
                    'reference_id'     => $slip->id,
                    'quantity'         => $detail->packed_qty,
                    'unit_cost'        => $isSale
                        ? $item->sale_price
                        : ($line->unit_cost ?? $item->purchase_price),
                    'order_no'             => $orderNo,

                    'transaction_date' => now(),
                    'created_by'       => auth()->id(),
                ]);

                /*
             * Update processed quantity.
             */
                $line->$doneField = $newDone;

                /*
             * Calculate remaining quantity.
             * Do NOT save it because remaining_qty does not
             * exist in your current PO schema.
             */
                $remainingQty = max(
                    0,
                    (float) $line->quantity - $newDone
                );

                /*
             * Current schema only supports:
             * open / delivered
             */
                $line->status = $remainingQty <= 0
                    ? 'delivered'
                    : 'open';

                $line->save();
            }

            /*
         * Update parent order status.
         */
            $order = $isSale
                ? $slip->sale_order
                : $slip->purchase_order;

            if ($order) {
                $lines = $isSale
                    ? $order->so_detail()->get()
                    : $order->po_detail()->get();

                $totalOrdered = $lines->sum('quantity');
                $totalProcessed = $lines->sum($doneField);

                if ($totalProcessed >= $totalOrdered) {
                    $order->update([
                        'status' => 'delivered',
                    ]);
                }
            }

            /*
         * Mark packing slip as dispatched.
         */
            $slip->update([
                'status'        => 'dispatched',
                'dispatch_date' => now(),
            ]);

            DB::commit();

            return $this->sendResponse(
                $slip->load(self::SLIP_RELATIONS),
                'Packing slip dispatched successfully.'
            );
        } catch (ModelNotFoundException $e) {

            DB::rollBack();

            return $this->sendError(
                'Packing slip not found.',
                $e->getMessage(),
                404
            );
        } catch (Exception $e) {

            DB::rollBack();

            return $this->sendError(
                'Something went wrong.',
                $e->getMessage(),
                500
            );
        }
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | ----------------------------------------------------------------- */

    private function stockBalance($itemId, $storeId): float
    {
        return (float) (Item_ledger::where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->selectRaw("SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END) as balance")
            ->value('balance') ?? 0);
    }
}
