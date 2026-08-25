<?php
namespace App\Contracts\AI;

interface SummarizerInterface
{
    public function summarize(string $content): string;
    public function getModelName(): string;
}
