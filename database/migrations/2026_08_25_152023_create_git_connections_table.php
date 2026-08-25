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
        Schema::create('git_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->default('manual');
            $table->string('repository');
            $table->string('branch')->default('main');
            $table->foreignId('deploy_key_id')->nullable()->constrained('ssh_keys')->nullOnDelete();
            $table->string('installation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_connections');
    }
};
