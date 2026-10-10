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
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('provider_ref', 191)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3);
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedBigInteger('refunded_cents')->default(0);
            $table->string('failure_reason', 500)->nullable();
            $table->string('checkout_url', 2048)->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->boolean('needs_attention')->default(false)->index();
            $table->unsignedBigInteger('success_guard')->nullable()->unique();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_ref']);
            $table->index(['booking_id', 'status']);
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
