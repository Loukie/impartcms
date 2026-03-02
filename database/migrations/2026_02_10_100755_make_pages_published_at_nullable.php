<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip sqlite (used in tests) – SHOW/ALTER syntax not supported.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Detect current column type so we alter it safely (DATETIME vs TIMESTAMP)
        $col = DB::selectOne("SHOW COLUMNS FROM pages WHERE Field = 'published_at'");

        $type = 'DATETIME';
        if ($col && isset($col->Type)) {
            $t = strtolower((string) $col->Type);
            if (str_contains($t, 'timestamp')) $type = 'TIMESTAMP';
            if (str_contains($t, 'datetime'))  $type = 'DATETIME';
        }

        DB::statement("ALTER TABLE pages MODIFY published_at {$type} NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $col = DB::selectOne("SHOW COLUMNS FROM pages WHERE Field = 'published_at'");

        $type = 'DATETIME';
        if ($col && isset($col->Type)) {
            $t = strtolower((string) $col->Type);
            if (str_contains($t, 'timestamp')) $type = 'TIMESTAMP';
            if (str_contains($t, 'datetime'))  $type = 'DATETIME';
        }

        // ensure there are no NULL values before making NOT NULL
        DB::table('pages')->whereNull('published_at')->update(['published_at' => now()]);
        DB::statement("ALTER TABLE pages MODIFY published_at {$type} NOT NULL");
    }
};
