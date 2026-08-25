<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$generator = app(\App\Contracts\AI\EmbeddingGeneratorInterface::class);
$repo = app(\App\Contracts\Repositories\NoteRepositoryInterface::class);
$notes = \App\Models\Note::whereNull('embedding')->get();

foreach ($notes as $note) {
    try {
        echo "Generating for: {$note->title}...\n";
        $embedding = $generator->generate($note->title . "\n" . $note->content);
        $repo->saveEmbedding($note, $embedding);
        echo "Done.\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
