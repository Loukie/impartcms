<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layout_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // header | footer
            $table->string('name', 120);
            $table->boolean('is_enabled')->default(true);
            $table->string('target_mode', 20)->default('global'); // global | only | except
            $table->unsignedInteger('priority')->default(100); // lower wins
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_enabled']);
            $table->index(['priority']);
        });

        Schema::create('layout_block_pages', function (Blueprint $table) {
            $table->unsignedBigInteger('layout_block_id');
            $table->unsignedBigInteger('page_id');

            $table->primary(['layout_block_id', 'page_id']);

            $table->foreign('layout_block_id')->references('id')->on('layout_blocks')->onDelete('cascade');
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');

            $table->index('page_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            if (!Schema::hasColumn('pages', 'header_block_id')) {
                $table->unsignedBigInteger('header_block_id')->nullable()->after('template');
                $table->foreign('header_block_id')->references('id')->on('layout_blocks')->nullOnDelete();
            }
            if (!Schema::hasColumn('pages', 'footer_block_id')) {
                $table->unsignedBigInteger('footer_block_id')->nullable()->after('header_block_id');
                $table->foreign('footer_block_id')->references('id')->on('layout_blocks')->nullOnDelete();
            }
        });

        // --- Optional one-time import from legacy Settings header/footer editor ---
        // If you previously used Settings -> Header/Footer, we create Layout Blocks so nothing is lost.
        if (Schema::hasTable('settings')) {
            $headerHtml = (string) (DB::table('settings')->where('key', 'front_header_html')->value('value') ?? '');
            $footerHtml = (string) (DB::table('settings')->where('key', 'front_footer_html')->value('value') ?? '');

            $headerEnabled = ((string) (DB::table('settings')->where('key', 'front_header_enabled')->value('value') ?? '0')) === '1';
            $footerEnabled = ((string) (DB::table('settings')->where('key', 'front_footer_enabled')->value('value') ?? '0')) === '1';

            $hasAny = (int) (DB::table('layout_blocks')->count() ?? 0) > 0;

            if (!$hasAny) {
                if ($headerEnabled && trim($headerHtml) !== '') {
                    DB::table('layout_blocks')->insert([
                        'type' => 'header',
                        'name' => 'Global header (imported)',
                        'is_enabled' => true,
                        'target_mode' => 'global',
                        'priority' => 10,
                        'content' => $headerHtml,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($footerEnabled && trim($footerHtml) !== '') {
                    DB::table('layout_blocks')->insert([
                        'type' => 'footer',
                        'name' => 'Global footer (imported)',
                        'is_enabled' => true,
                        'target_mode' => 'global',
                        'priority' => 10,
                        'content' => $footerHtml,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // New toggles (default enabled) - if not already set
            $existsHeaderToggle = DB::table('settings')->where('key', 'layout_header_enabled')->exists();
            $existsFooterToggle = DB::table('settings')->where('key', 'layout_footer_enabled')->exists();
            if (!$existsHeaderToggle) {
                DB::table('settings')->insert([
                    'key' => 'layout_header_enabled',
                    'value' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if (!$existsFooterToggle) {
                DB::table('settings')->insert([
                    'key' => 'layout_footer_enabled',
                    'value' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (Schema::hasColumn('pages', 'footer_block_id')) {
                $table->dropForeign(['footer_block_id']);
                $table->dropColumn('footer_block_id');
            }
            if (Schema::hasColumn('pages', 'header_block_id')) {
                $table->dropForeign(['header_block_id']);
                $table->dropColumn('header_block_id');
            }
        });

        Schema::dropIfExists('layout_block_pages');
        Schema::dropIfExists('layout_blocks');
    }
};
