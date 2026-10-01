<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id')->index();
            $table->unsignedBigInteger('inv_snapshot_id')->nullable();
            $table->string('section', 40);
            $table->string('part_key', 191);
            $table->string('change_type', 10); // added | removed | changed
            $table->string('field', 60)->nullable();
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->string('severity', 10)->default('warning'); // info | warning | critical
            $table->string('state', 10)->default('pending')->index(); // auto | pending | approved | rejected
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['asset_id', 'section', 'state']);
        });

        Schema::create('inv_removed_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('asset_id')->index();
            $table->string('part_type', 40);
            $table->string('name')->nullable();
            $table->string('serial')->nullable();
            $table->json('specs')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->string('condition', 20)->default('hong'); // hong | cho_kiem_tra | tot_thu_hoi
            $table->unsignedBigInteger('maintenance_id')->nullable();
            $table->string('disposal_state', 20)->default('luu_kho'); // luu_kho | da_thanh_ly | da_bao_hanh
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_tag_locations', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 191)->unique();
            $table->unsignedInteger('location_id')->nullable();
            $table->string('state', 10)->default('pending')->index(); // pending | approved | ignored
            $table->timestamp('first_seen_at')->nullable();
            $table->unsignedInteger('agent_count')->default(0);
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_tag_locations');
        Schema::dropIfExists('inv_removed_parts');
        Schema::dropIfExists('inv_changes');
    }
};
