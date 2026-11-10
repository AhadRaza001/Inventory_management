<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    { {
            DB::table('item_ledgers')->insert([
                [
                    'company_id' => 1,
                    'item_id' => 1,
                    'store_id' => 1,

                    'transaction_type' => 'IN',
                    'reference_type' => 'purchase',
                    'reference_id' => 1,

                    'quantity' => 100,
                    'unit_cost' => 120,

                    'order_no' => 'PO-000001',

                    'transaction_date' => Carbon::now(),
                    'created_by' => 1,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'company_id' => 1,
                    'item_id' => 1,
                    'store_id' => 1,

                    'transaction_type' => 'OUT',
                    'reference_type' => 'sale',
                    'reference_id' => 1,

                    'quantity' => 10,
                    'unit_cost' => 120,

                    'order_no' => 'SO-000001',

                    'transaction_date' => Carbon::now(),
                    'created_by' => 1,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
