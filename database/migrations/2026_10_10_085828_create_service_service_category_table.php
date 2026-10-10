<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_service_category', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();

            $table->primary(['service_id', 'service_category_id']);
        });

        DB::table('services')
            ->select(['id', 'service_category_id'])
            ->orderBy('id')
            ->chunk(500, function ($services): void {
                DB::table('service_service_category')->insert($services->map(fn (object $service): array => [
                    'service_id' => $service->id,
                    'service_category_id' => $service->service_category_id,
                ])->all());
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_service_category');
    }
};
