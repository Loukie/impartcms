<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
        $col = DB::selectOne("SHOW COLUMNS FROM pages WHERE Field = 'published_at'");

        $type = 'DATETIME';
        if ($col && isset($col->Type)) {
            $t = strtolower((string) $col->Type);
            if (str_contains($t, 'timestamp')) $type = 'TIMESTAMP';
            if (str_contains($t, 'datetime'))  $type = 'DATETIME';
        }

        DB::statement("ALTER TABLE pages MODIFY published_at {$type} NOT NULL");
    }
};
