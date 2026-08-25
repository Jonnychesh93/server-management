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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ip_address');
            $table->unsignedSmallInteger('ssh_port')->default(22);
            $table->string('ssh_user');
            $table->string('os');
            $table->text('ssh_private_key')->nullable();
            $table->text('ssh_public_key')->nullable();
            $table->text('bootstrap_credential')->nullable();
            $table->string('bootstrap_credential_type')->nullable();
            $table->string('provisioning_status')->default('pending');
            $table->string('provisioning_failed_step')->nullable();
            $table->longText('provisioning_output')->nullable();
            $table->string('connection_status')->default('unknown');
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->unsignedTinyInteger('cpu_usage')->nullable();
            $table->unsignedTinyInteger('memory_usage')->nullable();
            $table->unsignedTinyInteger('disk_usage')->nullable();
            $table->json('installed_php_versions')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
