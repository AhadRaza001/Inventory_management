<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleReturnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        // Get required records
        $saleOrder    = DB::table('sale_orders')->first();
        $packingSlip  = DB::table('packing_slips')->first();
        $store        = DB::table('stores')->first();
        $user         = DB::table('users')->first();

        // Safety check
        if (!$saleOrder || !$packingSlip || !$store || !$user) {
            return;
        }

        // 1. Create Sale Return (Header)
        $saleReturnId = DB::table('sale_returns')->insertGetId([
            'sale_order_id'    => $saleOrder->id,
            'packing_slip_id'  => $packingSlip->id,
            'store_id'         => $store->id,
            'user_id'          => $user->id,
            'sr_no'            => 'SR-' . rand(1000, 9999),
            'status'           => 'draft',
            'return_type'      => 'partial',
            'reason'           => 'Customer returned damaged items (test data)',
            'return_date'      => Carbon::now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
