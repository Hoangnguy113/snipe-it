<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_agents', function (Blueprint $table) {
            $table->id();
            $table->string('deviceid')->unique();
            $table->string('agent_uuid', 36)->nullable()->unique();
            $table->string('hostname')->nullable()->index();
            $table->string('tag')->nullable()->index();
            $table->unsignedInteger('asset_id')->nullable()->index();
            $table->string('agent_version', 32)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('rustdesk_id', 32)->nullable()->index();
            $table->string('rustdesk_version', 32)->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('last_inventory_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->string('state', 16)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_agents');
    }
};
