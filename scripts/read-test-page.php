<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$p = App\Models\Page::where('slug', 'test-page')->first();
if (!$p) { echo "NOT FOUND\n"; exit; }

// Output chars 10000-18000 (HTML body + JS)
echo substr($p->body, 10000, 8000);
