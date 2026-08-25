<?php
namespace App\DTOs;

class NoteDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content
    ) {}
}
