<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_remote_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('asset_id')->index();
            $table->unsignedBigInteger('inv_agent_id')->nullable();
            $table->string('rustdesk_id', 64);
            $table->timestamp('started_at')->index();
            $table->string('operator_ip', 64)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_remote_sessions');
    }
};
