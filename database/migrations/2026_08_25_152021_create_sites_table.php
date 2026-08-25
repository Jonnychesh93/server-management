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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('document_root')->default('/public');
            $table->string('php_version');
            $table->longText('deploy_script');
            $table->text('env_encrypted')->nullable();
            $table->string('status')->default('provisioning');
            $table->string('provisioning_failed_step')->nullable();
            $table->longText('provisioning_output')->nullable();
            $table->string('ssl_status')->default('none');
            $table->timestamp('ssl_certificate_expires_at')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->foreignId('last_deployment_id')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
