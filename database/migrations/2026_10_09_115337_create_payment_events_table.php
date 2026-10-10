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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 191);
            $table->string('type', 60);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
