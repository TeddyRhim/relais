<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('signing_secret');
            $table->string('signature_header')->default('X-Relais-Signature');
            $table->boolean('require_signature')->default(false);
            $table->text('target_url')->nullable();
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('method', 10);
            $table->json('headers');
            $table->longText('body');
            $table->string('content_type')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('signature_status', 10);
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['source_id', 'created_at']);
        });

        Schema::create('replays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('webhook_event_id')->constrained()->cascadeOnDelete();
            $table->text('target_url');
            $table->string('status', 10)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index('webhook_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replays');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('sources');
    }
};
