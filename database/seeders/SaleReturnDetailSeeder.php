<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleReturnDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
     // Get required parent records
        $saleReturn = DB::table('sale_returns')->first();

        if (!$saleReturn) {
            return;
        }

        // Get packing slip details (source of return items)
        $packingSlipDetails = DB::table('packing_slip_details')
            ->limit(3)
            ->get();

        foreach ($packingSlipDetails as $detail) {

            DB::table('sale_return_details')->insert([
                'sale_return_id'           => $saleReturn->id,
                'packing_slip_detail_id'   => $detail->id,
                'item_id'                  => $detail->item_id,
                'dispatched_qty'          => $detail->packed_qty,
                'return_qty'              => rand(1, (int)$detail->packed_qty),
                'remarks'                 => 'Test return item entry',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }
    }
    }

