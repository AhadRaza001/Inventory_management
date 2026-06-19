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
        // Packing Slip Header
        Schema::create('packing_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ps_no')->unique();
            $table->enum('status', ['draft', 'dispatched', 'cancelled'])->default('draft');
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('dispatch_date')->nullable();
            $table->timestamps();
        });

        // Packing Slip Detail Lines
        Schema::create('packing_slip_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_slip_id')->constrained()->onDelete('cascade');
            $table->foreignId('so_detail_id')->constrained('so_details')->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->decimal('ordered_qty', 12, 3);
            $table->decimal('packed_qty', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_slip_details');
        Schema::dropIfExists('packing_slips');
    }
};
