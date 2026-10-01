<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('version', 60)->nullable();
            $table->string('publisher', 100)->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('sha512', 128)->index();
            $table->unsignedBigInteger('filesize');
            $table->text('install_cmd');
            $table->text('uninstall_cmd')->nullable();
            $table->boolean('needs_reboot')->default(false);
            $table->boolean('ask_user')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_package_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_package_id')->index();
            $table->string('phase', 10)->default('before'); // before | after
            $table->string('check_type', 40);
            $table->string('path')->nullable();
            $table->string('value')->nullable();
        });

        Schema::create('inv_deploy_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('inv_package_id')->index();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('scope_type', 20); // asset | location
            $table->json('scope_ids');
            $table->string('state', 20)->default('active'); // active | cancelled
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_deploy_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_deploy_job_id')->index();
            $table->unsignedBigInteger('inv_agent_id')->index();
            $table->string('state', 10)->default('pending'); // pending | running | ok | failed
            $table->text('log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['inv_deploy_job_id', 'inv_agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_deploy_targets');
        Schema::dropIfExists('inv_deploy_jobs');
        Schema::dropIfExists('inv_package_checks');
        Schema::dropIfExists('inv_packages');
    }
};
