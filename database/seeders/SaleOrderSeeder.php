<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('sale_orders')->insert([
            [
                'customer_id' => 1,
                'store_id' => 1,
                'user_id' => 1,
                'so_no' => 'SO-0001',
                'status' => 'open',
                'amount_status' => 'unpaid',
                'sub_total' => 50000,
                'discount_amount' => 2000,
                'tax_amount' => 5000,
                'grand_total' => 53000,
                'paid_amount' => 10000,
                'due_amount' => 43000,
                'customer_requisitions' => 'REQ-SO-001',
                'customer_reference' => 'REF-CUST-001',
            ],
        ]);
    }
}
