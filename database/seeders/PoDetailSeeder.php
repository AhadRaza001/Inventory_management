<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PoDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('po_details')->insert([
            [
                'purchase_order_id' => 1,
                'item_id' => 1,
                'user_id' => 1,
                'quantity' => 100,
                'delivered_qty' => 50,
                'unit_cost' => 120,
                'discount_amount' => 500,
                'tax_amount' => 1000,
                'status' => 'open',
            ],
            [
                'purchase_order_id' => 1,
                'item_id' => 2,
                'user_id' => 1,
                'quantity' => 200,
                'delivered_qty' => 200,
                'unit_cost' => 180,
                'discount_amount' => 0,
                'tax_amount' => 2000,
                'status' => 'delivered',
            ],
        ]);
    }
}
