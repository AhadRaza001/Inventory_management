<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('so_details')->insert([
            [
                'sale_order_id' => 1,
                'item_id' => 1,
                'store_id' => 1,
                'user_id' => 1,
                'quantity' => 10,
                'discount_amount' => 500,
                'tax_amount' => 1000,
                'delivered_qty' => 5,
                'remaining_qty' => 5,
                'status' => 'open',
            ],
            [
                'sale_order_id' => 1,
                'item_id' => 2,
                'store_id' => 1,
                'user_id' => 1,
                'quantity' => 20,
                'discount_amount' => 0,
                'tax_amount' => 2000,
                'delivered_qty' => 20,
                'remaining_qty' => 0,
                'status' => 'delivered',
            ],
        ]);
    }
}
