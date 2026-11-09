<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class StoreStock extends Model
{
    use BelongsToCompany; // same trait your other models use, so company_id is handled the same way

    protected $fillable = ['company_id', 'store_id', 'item_id', 'qty'];
    public static function change($storeId, $itemId, $qty)
    {
        $row = self::where('store_id', $storeId)->where('item_id', $itemId)->lockForUpdate()->first();

        if (!$row) {
            $row = self::create(['store_id' => $storeId, 'item_id' => $itemId, 'qty' => 0]);
        }

        if ($row->qty + $qty < 0) {
            throw new \Exception('Not enough stock for item ' . $itemId);
        }

        $row->qty = $row->qty + $qty;
        $row->save();
    }
}
