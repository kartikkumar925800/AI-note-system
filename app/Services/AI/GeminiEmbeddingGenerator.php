<?php
namespace App\Services\AI;

use App\Contracts\AI\EmbeddingGeneratorInterface;
use Illuminate\Support\Facades\Http;
use App\Exceptions\AIProviderException;

class GeminiEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public function generate(string $content): array
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = 'gemini-embedding-2';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$apiKey}";

        $response = Http::timeout(10)->post($url, [
            'model' => 'models/' . $model,
            'content' => [
                'parts' => [['text' => $content]]
            ],
            'outputDimensionality' => 768
        ]);

        if ($response->failed()) {
            throw new AIProviderException('Failed to generate embeddings from Gemini API.', $response->json() ?? []);
        }

        $embedding = $response->json('embedding.values');
        
        if (!$embedding || !is_array($embedding)) {
             throw new AIProviderException('Invalid embedding format received from Gemini.', $response->json() ?? []);
        }

        return $embedding;
    }
}
