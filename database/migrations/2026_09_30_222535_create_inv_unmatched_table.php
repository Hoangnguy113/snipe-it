<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_unmatched', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_agent_id')->index();
            $table->string('hostname')->nullable();
            $table->string('serial')->nullable();
            $table->string('machine_uuid')->nullable();
            $table->string('reason', 64);
            $table->unsignedInteger('resolved_asset_id')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_unmatched');
    }
};
