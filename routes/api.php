<?php

use App\Http\Controllers\api\StoreController;
use App\Http\Controllers\api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\api\GenerateNumberController;
use App\Http\Controllers\api\ItemController;
use App\Http\Controllers\api\ItemLedgerController;
use App\Http\Controllers\api\PackingslipController;
use App\Http\Controllers\api\SaleOrderController;
use App\Http\Controllers\api\SoDetailController;
use App\Http\Controllers\api\PurchaseOrderController;
use App\Http\Controllers\api\PoDetailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [UserController::class, 'login']);
Route::post('signup', [UserController::class, 'signup']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
// ->middleware('auth:sanctum')

Route::prefix('categories')->name('categories.')->group(function () {

    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/all', [CategoryController::class, 'all']);

    Route::get('/{id}', [CategoryController::class, 'single_category'])->name('show');

    Route::post('/', [CategoryController::class, 'store'])->name('store');

    Route::post('/{id}', [CategoryController::class, 'update'])->name('update');

    Route::delete('/{id}', [CategoryController::class, 'delete'])->name('delete');
});

Route::prefix('items')->name('items.')->group(function () {

    Route::get('/', [ItemController::class, 'index'])->name('index');

    Route::get('/{id}', [ItemController::class, 'single_item'])->name('show');

    Route::post('/', [ItemController::class, 'store'])->name('store');

    Route::post('/{id}', [ItemController::class, 'update'])->name('update');

    Route::delete('/{id}', [ItemController::class, 'delete'])->name('delete');
});

Route::prefix('units')->name('units.')->group(function () {

    Route::get('/', [UnitController::class, 'index'])->name('index');

    Route::get('/{id}', [UnitController::class, 'single_unit'])->name('show');

    Route::post('/', [UnitController::class, 'store'])->name('store');

    Route::post('/{id}', [UnitController::class, 'update'])->name('update');

    Route::delete('/{id}', [UnitController::class, 'delete'])->name('delete');
});

Route::prefix('stores')->name('stores.')->group(function () {

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

    Route::delete('/{id}', [PurchaseOrderController::class, 'delete'])
        ->name('delete');
});


Route::middleware('auth:sanctum')->prefix('podetails')->name('podetails.')->group(function () {

    Route::get('/{id}', [PoDetailController::class, 'index'])
        ->name('index');

    Route::get('single/{id}', [PoDetailController::class, 'single_po_detail'])
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

    Route::get('single/{id}', [CustomerController::class, 'single_customer'])
        ->name('show');

    Route::post('/', [CustomerController::class, 'store'])
        ->name('store');

    Route::post('/{id}', [CustomerController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [CustomerController::class, 'delete'])
        ->name('delete');
});


Route::middleware('auth:sanctum')->prefix('packingslips')->name('packingslips.')->group(function () {

    Route::get('/{id}', [PackingslipController::class, 'index'])
        ->name('index');

    Route::get('/single/{id}', [PackingslipController::class, 'single_packing_slip'])
        ->name('show');

    Route::post('/', [PackingslipController::class, 'store'])
        ->name('store');

    Route::post('/{id}', [PackingslipController::class, 'update'])
        ->name('update');

    Route::delete('/{id}', [PackingslipController::class, 'delete'])
        ->name('delete');

    Route::post('/dispatch/{id}', [PackingslipController::class, 'dispatch'])
        ->name('dispatch');
});

Route::middleware('auth:sanctum')->prefix('itemledgers')->group(function () {
    Route::get('/',              [ItemLedgerController::class, 'index']);
    Route::get('/stock-balance', [ItemLedgerController::class, 'stockBalance']);
    Route::post('/store-stock',   [ItemLedgerController::class, 'storeStock']);
    Route::post('/by-item',    [ItemLedgerController::class, 'getByItemAndStore']);
    Route::get('/{id}',          [ItemLedgerController::class, 'show']);
    Route::post('/summary', [ItemLedgerController::class, 'inOutSummary']);
});

// numberGenerate
Route::middleware('auth:sanctum')->prefix('generate')->group(function () {
    Route::get('/sale-order', [GenerateNumberController::class, 'saleOrder']);
    Route::get('/purchase-order', [GenerateNumberController::class, 'purchaseOrder']);
    Route::get('/item', [GenerateNumberController::class, 'item']);
});
