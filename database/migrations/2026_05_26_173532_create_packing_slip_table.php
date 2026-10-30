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

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // sale / purchase
            $table->enum('type', ['sale', 'purchase'])
                ->default('sale');

            // Either sale order OR purchase order
            $table->foreignId('sale_order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('store_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('ps_no');

            $table->unique(['company_id', 'ps_no']);

            $table->enum('status', [
                'draft',
                'dispatched',
                'cancelled'
            ])->default('draft');

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

            $table->foreignId('packing_slip_id')
                ->constrained()
                ->onDelete('cascade');
            

            // Either SO detail OR PO detail
            $table->foreignId('so_detail_id')
                ->nullable()
                ->constrained('so_details')
                ->nullOnDelete();

            $table->foreignId('po_detail_id')
                ->nullable()
                ->constrained('po_details')
                ->nullOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->onDelete('cascade');

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
