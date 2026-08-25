<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$generator = app(\App\Contracts\AI\EmbeddingGeneratorInterface::class);
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key=" . env('GEMINI_API_KEY');
$response = Illuminate\Support\Facades\Http::post($url, ['model'=>'models/gemini-embedding-001', 'content'=>['parts'=>[['text'=>'food']]]]);
$arr = $response->json('embedding.values');
echo is_array($arr) ? count($arr) : 'Error';
