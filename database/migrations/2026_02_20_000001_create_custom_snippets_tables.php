<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_snippets', function (Blueprint $table) {
            $table->id();

            // css | script
            $table->string('type', 20);

            $table->string('name', 255);

            // head | body | footer (scripts only). CSS always renders late in <head>.
            $table->string('position', 20)->default('head');

            $table->boolean('is_enabled')->default(true);

            // global | only | except
            $table->string('target_mode', 20)->default('global');

            $table->longText('content')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_enabled']);
            $table->index(['type', 'position']);
        });

        Schema::create('custom_snippet_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_snippet_id')->constrained('custom_snippets')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();

            $table->unique(['custom_snippet_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_snippet_pages');
        Schema::dropIfExists('custom_snippets');
    }
};
