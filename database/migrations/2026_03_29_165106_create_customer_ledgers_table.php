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
        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');

            $table->date('entry_date');

            $table->enum('entry_type', [
                'invoice',
                'payment',
                'adjustment',
                'opening_balance'
            ]);

            $table->enum('reference_type', [
                'invoice',
                'payment',
                'manual'
            ])->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('description')->nullable();

            $table->decimal('debit', 12, 2)->default(0);   // customer owes
            $table->decimal('credit', 12, 2)->default(0);  // customer paid

            $table->decimal('balance', 12, 2)->default(0); // running balance

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->index(['customer_id', 'store_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledgers');
    }
};
