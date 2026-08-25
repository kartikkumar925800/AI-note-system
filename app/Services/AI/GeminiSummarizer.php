<?php
namespace App\Services\AI;

use App\Contracts\AI\SummarizerInterface;
use Illuminate\Support\Facades\Http;
use App\Exceptions\AIProviderException;

class GeminiSummarizer implements SummarizerInterface
{
    public function summarize(string $content): string
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = $this->getModelName();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(60)->post($url, [
            'contents' => [
                ['parts' => [['text' => "You are an expert assistant. Provide a highly concise, direct summary of the following text in 1-2 sentences. Do not use filler words like 'The writer' or 'This note'. Just summarize the core facts directly.\n\nText:\n" . $content]]]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 1000,
            ]
        ]);

        if ($response->failed()) {
            throw new AIProviderException('Failed to generate summary from Gemini API.', $response->json() ?? []);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        
        if (!$text) {
             throw new AIProviderException('Invalid or missing summary in Gemini response.', $response->json() ?? []);
        }

        return $text;
    }

    public function getModelName(): string
    {
        return 'gemini-2.5-flash';
    }
}
