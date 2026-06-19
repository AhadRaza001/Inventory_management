<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('purchase_orders')->insert([
            [
                'store_id' => 1,
                'supplier_id' => 1,
                'user_id' => 1,
                'po_no' => 'PO-0001',
                'status' => 'open',
                'sub_total' => 50000,
                'discount_amount' => 2000,
                'tax_amount' => 5000,
                'grand_total' => 53000,
                'paid_amount' => 30000,
                'due_amount' => 23000,
                'customer_requisitions' => 'REQ-001',
                'customer_reference' => 'REF-ABC',
            ],
        ]);
    }
}
