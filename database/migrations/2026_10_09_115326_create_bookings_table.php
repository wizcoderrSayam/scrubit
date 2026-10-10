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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->char('access_token_hash', 64)->nullable();
            $table->char('draft_token_hash', 64)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('repeated_from_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();

            $table->string('service_name', 120);
            $table->string('pricing_method', 30);
            $table->unsignedBigInteger('unit_price_cents')->nullable();
            $table->unsignedBigInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->json('pricing_terms')->nullable();

            $table->date('preferred_date')->nullable()->index();
            $table->string('time_window', 20)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('frequency', 20)->nullable();

            $table->string('contact_name', 120)->nullable();
            $table->string('contact_email')->nullable()->index();
            $table->string('contact_phone', 30)->nullable();

            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->json('tax_breakdown')->nullable();
            $table->char('currency', 3)->default('CAD');
            $table->boolean('quote_required')->default(true);

            $table->json('chat_state')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
