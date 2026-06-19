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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();

            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->date('payment_date');
            $table->decimal('amount', 12, 2);

            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'cheque',
                'card',
                'online'
            ]);

            $table->string('reference_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();

            $table->enum('status', [
                'pending',
                'verified',
                'bounced',
                'cancelled'
            ])->default('pending');

            $table->text('notes')->nullable();

            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
