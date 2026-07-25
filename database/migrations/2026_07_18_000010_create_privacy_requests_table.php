<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_code')->unique();
            $table->string('request_type', 40);
            $table->string('full_name', 180);
            $table->string('email', 190);
            $table->string('relationship', 40)->nullable();
            $table->string('school_name', 190)->nullable();
            $table->string('account_reference', 190)->nullable();
            $table->text('description');
            $table->string('status', 30)->default('pending');
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
    }
};
