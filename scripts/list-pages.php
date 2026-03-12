<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pages = \App\Models\Page::where('slug', 'like', 'smart-home-2%')
    ->withTrashed()
    ->get(['id', 'title', 'slug', 'template', 'status']);

foreach ($pages as $p) {
    echo $p->id . ' | ' . $p->slug . ' | ' . $p->title . ' | tpl=' . $p->template . ' | status=' . $p->status . PHP_EOL;
}
