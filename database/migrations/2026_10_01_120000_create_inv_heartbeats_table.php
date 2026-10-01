<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_agent_id');
            $table->timestamp('received_at')->index();
            $table->decimal('cpu_percent', 5, 1)->nullable();
            $table->decimal('ram_percent', 5, 1)->nullable();
            $table->json('disks')->nullable();
            $table->string('logged_user')->nullable();
            $table->string('ip', 64)->nullable();
            $table->boolean('rustdesk_running')->nullable();
            $table->unsignedBigInteger('uptime_sec')->nullable();
            $table->index(['inv_agent_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_heartbeats');
    }
};
