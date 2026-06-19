<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          DB::table('customer_ledgers')->insert([
            [
                'customer_id' => 1,
                'store_id' => 1,

                'entry_date' => Carbon::today(),
                'entry_type' => 'invoice',
                'reference_type' => 'invoice',
                'reference_id' => 1,

                'description' => 'Invoice generated',

                'debit' => 53000,
                'credit' => 0,
                'balance' => 53000,

                'created_by' => 1,
            ],
            [
                'customer_id' => 1,
                'store_id' => 1,

                'entry_date' => Carbon::today(),
                'entry_type' => 'payment',
                'reference_type' => 'payment',
                'reference_id' => 1,

                'description' => 'Payment received',

                'debit' => 0,
                'credit' => 20000,
                'balance' => 33000,

                'created_by' => 1,
            ],
        ]);
    }
}
