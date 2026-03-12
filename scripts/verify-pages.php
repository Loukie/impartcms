<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Page;

$pages = Page::where('slug', 'like', 'smart-home-3%')->orderBy('id')->get();

foreach ($pages as $p) {
    $hasDoctype = str_contains($p->body, '<!DOCTYPE html>') ? 'YES' : 'NO';
    $len = strlen($p->body);
    echo "[{$p->id}] {$p->slug} | status={$p->status} | template={$p->template} | doctype={$hasDoctype} | {$len} chars\n";
}
