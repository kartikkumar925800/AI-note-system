<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$generator = app(\App\Contracts\AI\EmbeddingGeneratorInterface::class);
$embedding = $generator->generate('food');
$embeddingStr = '[' . implode(',', $embedding) . ']';

$notes = \App\Models\Note::selectRaw("id, title, embedding <=> ?::vector as distance", [$embeddingStr])->get();
foreach ($notes as $n) {
    echo "ID: {$n->id} | Title: {$n->title} | Distance: {$n->distance}\n";
}
