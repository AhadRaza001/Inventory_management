<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            StoreSeeder::class,
            SupplierSeeder::class,
            ItemSeeder::class,
            CustomerSeeder::class,
            PurchaseOrderSeeder::class,
            PoDetailSeeder::class,
            SaleOrderSeeder::class,
            SoDetailSeeder::class,
            InvoiceSeeder::class,
            InvoiceDetailSeeder::class,
            PaymentSeeder::class,
            ItemLedgerSeeder::class,
            CustomerLedgerSeeder::class,
            PackingSlipSeeder::class,
            SaleReturnSeeder::class,
            SaleReturnDetailSeeder::class,
            ]);
    }
}
