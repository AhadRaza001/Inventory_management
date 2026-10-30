<?php

use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\GenerateNumberController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemLedgerController;
use App\Http\Controllers\Api\PackingslipController;
use App\Http\Controllers\Api\PackingSlipDetailController;
use App\Http\Controllers\Api\SaleOrderController;
use App\Http\Controllers\Api\SoDetailController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\PoDetailController;
use App\Http\Controllers\Api\SupplierController;
use App\Models\Packing_slip_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [UserController::class, 'login']);
Route::post('signup', [UserController::class, 'signup'])->middleware('auth:sanctum');
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
// ->middleware('auth:sanctum')

Route::prefix('categories')->name('categories.')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/all', [CategoryController::class, 'all']);

    Route::get('/{id}', [CategoryController::class, 'single_category'])->name('show');

    Route::post('/', [CategoryController::class, 'store'])->name('store');

    Route::post('/{id}', [CategoryController::class, 'update'])->name('update');

    Route::delete('/bulk-delete', [CategoryController::class, 'bulkDelete']);
    Route::delete('/{id}', [CategoryController::class, 'delete'])->name('delete');
});

Route::prefix('items')->name('items.')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [ItemController::class, 'index'])->name('index');

    Route::get('/{id}', [ItemController::class, 'single_item'])->name('show');
    Route::get('/sku/{id}', [ItemController::class, 'getBySku'])->name('sku');

    Route::post('/', [ItemController::class, 'store'])->name('store');

    Route::post('/{id}', [ItemController::class, 'update'])->name('update');

    Route::delete('/bulk-delete', [ItemController::class, 'bulkDelete']);

    Route::delete('/{id}', [ItemController::class, 'delete'])->name('delete');
});

Route::prefix('units')->name('units.')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [UnitController::class, 'index'])->name('index');

    Route::get('/{id}', [UnitController::class, 'single_unit'])->name('show');

    Route::post('/', [UnitController::class, 'store'])->name('store');

    Route::post('/{id}', [UnitController::class, 'update'])->name('update');

    Route::delete('/bulk-delete', [UnitController::class, 'bulkDelete']);

    Route::delete('/{id}', [UnitController::class, 'delete'])->name('delete');
});

Route::prefix('stores')->name('stores.')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [StoreController::class, 'index'])
        ->name('index');

    Route::get('/{id}', [StoreController::class, 'single_store'])
        ->name('show');

    Route::post('/', [StoreController::class, 'store'])
        ->name('store');

    Route::put('/{id}', [StoreController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [StoreController::class, 'delete'])
        ->name('delete');
});

Route::middleware('auth:sanctum')->prefix('saleorders')->name('saleorders.')->group(function () {

    Route::get('/', [SaleOrderController::class, 'index'])
        ->name('index');

    Route::get('/{id}', [SaleOrderController::class, 'single_sale_order'])
        ->name('show');

    Route::post('/', [SaleOrderController::class, 'store'])
        ->name('store');

    Route::put('/{id}', [SaleOrderController::class, 'update'])
        ->name('update');

    Route::delete('/bulk-delete', [SaleOrderController::class, 'bulkDelete']);

    Route::delete('/{id}', [SaleOrderController::class, 'delete'])
        ->name('delete');
});

Route::middleware('auth:sanctum')->prefix('sodetails')->name('sodetails.')->group(function () {

    Route::get('/{id}', [SoDetailController::class, 'index'])
        ->name('index');

    Route::get('/{id}', [SoDetailController::class, 'single_so_detail'])
        ->name('show');

    Route::post('/', [SoDetailController::class, 'store'])
        ->name('store');

    Route::post('/{id}', [SoDetailController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [SoDetailController::class, 'delete'])
        ->name('delete');
});

Route::middleware('auth:sanctum')->prefix('purchaseorders')->name('purchaseorders.')->group(function () {

    Route::get('/', [PurchaseOrderController::class, 'index'])
        ->name('index');

    Route::get('/{id}', [PurchaseOrderController::class, 'single_purchase_order'])
        ->name('show');

    Route::post('/', [PurchaseOrderController::class, 'store'])
        ->name('store');

    Route::post('/{id}', [PurchaseOrderController::class, 'update'])
        ->name('update');

    Route::delete('/bulk-delete', [PurchaseOrderController::class, 'bulkDelete']);

    Route::delete('/{id}', [PurchaseOrderController::class, 'delete'])
        ->name('delete');
});


Route::middleware('auth:sanctum')->prefix('podetails')->name('podetails.')->group(function () {

    Route::get('index', [PoDetailController::class, 'index'])
        ->name('index');

    Route::get('/{id}', [PoDetailController::class, 'single_po_detail'])
        ->name('show');

    Route::post('/', [PoDetailController::class, 'store'])
        ->name('store');

    Route::post('/{id}', [PoDetailController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [PoDetailController::class, 'delete'])
        ->name('delete');
});


Route::middleware('auth:sanctum')->prefix('customers')->name('customers.')->group(function () {

    Route::get('/', [CustomerController::class, 'index'])
        ->name('index');

    Route::get('/{id}', [CustomerController::class, 'single_customer'])
        ->name('show');

    Route::post('/', [CustomerController::class, 'store'])
        ->name('store');

    Route::post('/{id}', [CustomerController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [CustomerController::class, 'delete'])
        ->name('delete');
});


Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('packing-slips')->controller(PackingslipController::class)->group(function () {

        Route::post('/', 'store');

        Route::get('/by-sale-order', 'getBySaleOrder');
        Route::get('/by-purchase-order', 'getByPurchaseOrder');

        Route::get('/sale-order/{id}', 'index');
        Route::get('/purchase-order/{id}', 'indexByPurchaseOrder');

        Route::get('/{id}', 'single_packing_slip');
        
        Route::post('/{id}/dispatch', 'dispatch');
    });
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('packing-slips-detail')->controller(PackingSlipDetailController::class)->group(function () {

        Route::get('/{id}', 'index');
    });
});

Route::middleware('auth:sanctum')->prefix('itemledgers')->group(function () {
    Route::get('/',              [ItemLedgerController::class, 'index']);
    Route::get('/stock-balance', [ItemLedgerController::class, 'stockBalance']);
    Route::post('/store-stock',   [ItemLedgerController::class, 'storeStock']);
    Route::post('/by-item',    [ItemLedgerController::class, 'getByItemAndStore']);
    Route::get('/{id}',          [ItemLedgerController::class, 'show']);
    Route::post('/summary', [ItemLedgerController::class, 'inOutSummary']);
});

//Supplier
Route::middleware('auth:sanctum')->prefix('suppliers')->name('suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/all', [SupplierController::class, 'all'])->name('all');
    Route::delete('/bulk-delete', [SupplierController::class, 'bulkDelete']);
    Route::get('/{id}', [SupplierController::class, 'single_supplier'])->name('show');
    Route::post('/', [SupplierController::class, 'store'])->name('store');
    Route::post('/{id}', [SupplierController::class, 'update'])->name('update');
    Route::delete('/{id}', [SupplierController::class, 'delete'])->name('delete');
});
// numberGenerate
Route::middleware('auth:sanctum')->prefix('generate')->group(function () {
    Route::get('/saleOrder', [GenerateNumberController::class, 'saleOrder']);
    Route::get('/purchaseOrder', [GenerateNumberController::class, 'purchaseOrder']);
    Route::get('/item', [GenerateNumberController::class, 'item']);
    Route::get('/supplier', [GenerateNumberController::class, 'supplier']);
});
