<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackingSlipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
           // Get sample data
        $saleOrder = DB::table('sale_orders')->first();
        $store     = DB::table('stores')->first();
        $user      = DB::table('users')->first();

        if (!$saleOrder || !$store || !$user) {
            return; // safety check
        }

        // 1. Create Packing Slip (Header)
        $packingSlipId = DB::table('packing_slips')->insertGetId([
            'sale_order_id' => $saleOrder->id,
            'store_id'      => $store->id,
            'user_id'       => $user->id,
            'ps_no'         => 'PS-000001',
            'status'        => 'draft',
            'vehicle_no'    => 'LEA-1234',
            'driver_name'   => 'Ali Ahmed',
            'driver_phone'  => '03001234567',
            'remarks'       => 'Test packing slip generated via seeder',
            'dispatch_date' => Carbon::now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // 2. Get SO Details
        $soDetails = DB::table('so_details')
            ->where('sale_order_id', $saleOrder->id)
            ->limit(3)
            ->get();

        foreach ($soDetails as $detail) {

            DB::table('packing_slip_details')->insert([
                'packing_slip_id' => $packingSlipId,
                'so_detail_id'    => $detail->id,
                'item_id'         => $detail->item_id,
                'ordered_qty'     => $detail->quantity ?? 10,
                'packed_qty'      => rand(1, 10),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    
    }
}
