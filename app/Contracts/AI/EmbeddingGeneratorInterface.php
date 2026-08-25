<?php
namespace App\Contracts\AI;

interface EmbeddingGeneratorInterface
{
    /**
     * @return float[]
     */
    public function generate(string $content): array;
}
