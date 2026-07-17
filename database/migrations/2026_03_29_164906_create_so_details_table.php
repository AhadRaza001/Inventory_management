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
        Schema::create('so_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade')->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity',10,2)->default(1);
            $table->decimal('discount_amount',10,2)->default(0);
            $table->decimal('tax_amount',10,2)->default(0);
            $table->decimal('delivered_qty',10,2)->default(0);
            $table->decimal('deliver_now',10,2)->default(0);
            $table->decimal('remaining_qty',10,2)->default(0);
            $table->enum('status',['open','delivered',])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_details');
    }
};
