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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('short_description', 300);
            $table->text('description')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->text('instructions')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('pricing_method', 30)->default('custom_quote');
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->unsignedBigInteger('hourly_rate_cents')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->boolean('allows_recurring')->default(false);
            $table->json('chat_script')->nullable();
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
