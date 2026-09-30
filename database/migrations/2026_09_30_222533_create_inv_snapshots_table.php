<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_agent_id')->index();
            $table->unsignedInteger('asset_id')->nullable()->index();
            $table->binary('payload');
            $table->string('content_hash', 64)->index();
            $table->string('protocol', 8);
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Laravel's binary() produces a BLOB (max 64KB). A real inventory
        // report weighs 1-3MB, so widen it to LONGBLOB.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inv_snapshots MODIFY payload LONGBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_snapshots');
    }
};
