<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_service', function (Blueprint $table): void {
            $table->foreignId('specialist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['specialist_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialist_service');
    }
};
