<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\api\ResponseController;
use App\Models\Item_ledger;
use App\Models\Item;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Validator;

class ItemLedgerController extends ResponseController
{

    // -------------------------------------------------------
    // GET ALL LEDGER ENTRIES (with filters)
    // GET /api/item-ledgers
    // -------------------------------------------------------
    public function index(Request $request)
    {
        try {

            $query = Item_ledger::with(['item', 'store', 'created_by']);

            if ($request->filled('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->filled('reference_type')) {
                $query->where('reference_type', $request->reference_type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $ledgers = $query->orderBy('transaction_date', 'desc')->paginate(100);

            return $this->sendResponse($ledgers, 'Item ledgers retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // GET LEDGER ENTRIES BY ITEM AND STORE
    // GET /api/item-ledgers/by-item?item_id=1&store_id=1
    // -------------------------------------------------------
    public function getByItemAndStore(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'item_id'   => 'required|exists:items,id',
                'store_id'  => 'required|exists:stores,id',
                'date_from' => 'nullable|date',
                'date_to'   => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed.', $validator->errors(), 422);
            }

            $query = Item_ledger::with(['item', 'store', 'created_by'])
                ->where('item_id', $request->item_id)
                ->where('store_id', $request->store_id);

            $openingBalance = 0;
            $openingBalance = 0;

            if ($request->filled('date_from')) {

                $previousLedgers = Item_ledger::where('item_id', $request->item_id)->where('store_id', $request->store_id)->whereDate('transaction_date', '<', $request->date_from)->get();


                $openingIn = $previousLedgers->whereIn('transaction_type', ['IN', 'ADJUSTMENT'])
                    ->sum('quantity');


                $openingOut = $previousLedgers->whereIn('transaction_type', ['OUT', 'TRANSFER'])
                    ->sum('quantity');


                $openingBalance = $openingIn - $openingOut;
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $ledgers = $query->orderBy('transaction_date', 'desc')->get();

            if ($ledgers->isEmpty()) {
                return $this->sendError('No ledger entries found.', 'No records found for this item and store.', 404);
            }

            $InEntries = $ledgers->whereIn('transaction_type', ['IN', 'ADJUSTMENT']);
            $totalIn  = $InEntries->sum('quantity');

            $OutEntries = $ledgers->whereIn('transaction_type', ['OUT', 'TRANSFER']);
            $totalOut = $OutEntries->sum('quantity');

            $closingBalance  = $openingBalance + $totalIn - $totalOut;


            return $this->sendResponse([
                'item'      => $ledgers->first()->item,
                'store'     => $ledgers->first()->store,
                'date_from' => $request->date_from ?? 'All',
                'date_to'   => $request->date_to   ?? 'All',
                'in_entries' => $InEntries,
                'out_entries' => $OutEntries,
                'total_in'  => $totalIn,
                'total_out' => $totalOut,
                'opening_balance' => $openingBalance,
                'closing_balance'   => $closingBalance,
                'ledgers'   => $ledgers,
            ], 'Item ledger retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }
    // -------------------------------------------------------
    // GET SINGLE LEDGER ENTRY
    // GET /api/item-ledgers/{id}
    // -------------------------------------------------------
    public function show($id)
    {
        try {

            $ledger = Item_ledger::with(['item', 'store', 'createdBy'])
                ->findOrFail($id);

            return $this->sendResponse($ledger, 'Item ledger retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Ledger entry not found.', $e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // GET STOCK BALANCE FOR SPECIFIC ITEM IN A STORE
    // GET /api/item-ledgers/stock-balance?item_id=1&store_id=1
    // -------------------------------------------------------
    public function stockBalance(Request $request)
    {
        try {

            $balance = $this->getStockBalance($request->item_id, $request->store_id);

            return $this->sendResponse([
                'item_id'  => $request->item_id,
                'store_id' => $request->store_id,
                'balance'  => $balance,
            ], 'Stock balance retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // GET STOCK BALANCE FOR ALL ITEMS IN A STORE
    // GET /api/item-ledgers/store-stock?store_id=1
    // -------------------------------------------------------
    public function storeStock(Request $request)
    {
        try {

            // GET ALL LEDGER ENTRIES FOR THIS STORE
            $ledgers = Item_ledger::with('item')
                ->where('store_id', $request->store_id)
                ->get();

            // GROUP BY ITEM AND CALCULATE BALANCE
            $stock = $ledgers->groupBy('item_id')->map(function ($entries) {

                $totalIn  = $entries->whereIn('transaction_type', ['IN', 'ADJUSTMENT'])->sum('quantity');
                $totalOut = $entries->whereIn('transaction_type', ['OUT', 'TRANSFER'])->sum('quantity');
                $balance  = $totalIn - $totalOut;

                return [
                    'item_id'   => $entries->first()->item_id,
                    'item'      => $entries->first()->item,
                    'total_in'  => $totalIn,
                    'total_out' => $totalOut,
                    'balance'   => $balance,
                ];
            })->values();

            return $this->sendResponse($stock, 'Store stock retrieved successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', $e->getMessage(), 500);
        }
    }

public function inOutSummary(Request $request)
{
    $validator = Validator::make($request->all(), [
        'store_id'   => 'required|exists:stores,id',
        'item_from'  => 'required|integer',
        'item_to'    => 'required|integer|gte:item_from',
        'date_from'  => 'required|date',
        'date_to'    => 'required|date|after_or_equal:date_from',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation failed.', $validator->errors(), 422);
    }

    $storeId   = $request->store_id;
    $itemFrom  = $request->item_from;
    $itemTo    = $request->item_to;
    $dateFrom  = $request->date_from;
    $dateTo    = $request->date_to;

    // All items in range (so items with zero activity still show up)
    $items = Item::whereBetween('id', [$itemFrom, $itemTo])->get()->keyBy('id');

    $ledgers = Item_ledger::where('store_id', $storeId)
        ->whereBetween('item_id', [$itemFrom, $itemTo])
        ->whereDate('transaction_date', '>=', $dateFrom)
        ->whereDate('transaction_date', '<=', $dateTo)
        ->get()
        ->groupBy('item_id');

    $openingLedgers = Item_ledger::where('store_id', $storeId)
        ->whereBetween('item_id', [$itemFrom, $itemTo])
        ->whereDate('transaction_date', '<', $dateFrom)
        ->get()
        ->groupBy('item_id');

    $openingBalances = $openingLedgers->map(function ($records) {
        $in  = $records->whereIn('transaction_type', ['IN', 'ADJUSTMENT'])->sum('quantity');
        $out = $records->whereIn('transaction_type', ['OUT', 'TRANSFER'])->sum('quantity');
        return $in - $out;
    });

    $summary = $items->map(function ($item) use ($ledgers, $openingBalances) {
        $records = $ledgers->get($item->id, collect());

        $in  = $records->whereIn('transaction_type', ['IN', 'ADJUSTMENT'])->sum('quantity');
        $out = $records->whereIn('transaction_type', ['OUT', 'TRANSFER'])->sum('quantity');

        $openingBalance = $openingBalances->get($item->id, 0);

        return [
            'item'             => $item,
            'opening_balance'  => $openingBalance,
            'total_in'         => $in,
            'total_out'        => $out,
            'closing_balance'  => $openingBalance + $in - $out,
        ];
    })->values();

    return $this->sendResponse($summary, 'In Out summary fetched successfully.');
}


    // -------------------------------------------------------
    // PRIVATE HELPER — STOCK BALANCE CALCULATOR
    // -------------------------------------------------------

    private function getStockBalance($itemId, $storeId): float
    {
        $ledgers = Item_ledger::where('item_id', $itemId)
            ->where('store_id', $storeId)
            ->get();

        $totalIn  = $ledgers->whereIn('transaction_type', ['IN', 'ADJUSTMENT'])->sum('quantity');
        $totalOut = $ledgers->whereIn('transaction_type', ['OUT', 'TRANSFER'])->sum('quantity');

        return (float) $totalIn - $totalOut;
    }
}
