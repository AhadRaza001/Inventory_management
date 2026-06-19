<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');

            $table->enum('transaction_type', [
                'IN',
                'OUT',
                'ADJUSTMENT',
                'TRANSFER'
            ]);

            $table->enum('reference_type', [
                'purchase_order',
                'sale_order',
                'return',
                'adjustment',
                'transfer'
            ])->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();

            $table->dateTime('transaction_date');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // 🔥 indexes for performance
            $table->index(['item_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_ledgers');
    }
};
