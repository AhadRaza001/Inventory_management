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
        Schema::create('po_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity',10,2);
            $table->decimal('delivered_qty',10,2)->default(0);
            $table->decimal('unit_cost',10,2)->nullable();
            $table->decimal('discount_amount',10,2)->nullable();
            $table->decimal('tax_amount',10,2)->nullable();
            $table->enum('status',['open','delivered'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_details');
    }
};
