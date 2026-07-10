<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Purchase_order;
use App\Models\Sale_order;
use Illuminate\Http\Request;

class GenerateNumberController extends ResponseController
{
    public function item()
    {
        $lastOrder = Item::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
         $sku = 'I-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        return $this->sendResponse($sku,'Item sku is delivered.');
    }
    public function saleOrder()
    {
        $lastOrder      = Sale_order::latest('id')->first();
        $nextId         = $lastOrder ? $lastOrder->id + 1 : 1;
        $soNo = 'SO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        return $this->sendResponse($soNo,'Sale Order number is delivered.');
    }
     public function purchaseOrder()
    {
        $lastOrder      = Purchase_order::latest('id')->first();
        $nextId         = $lastOrder ? $lastOrder->id + 1 : 1;
        $poNo = 'PO-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        return $this->sendResponse($poNo,'Purchase Order number is delivered.');
    }
}
