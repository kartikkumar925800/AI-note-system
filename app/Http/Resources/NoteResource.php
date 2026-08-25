<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'summary' => $this->summary,
            'summary_model' => $this->summary_model,
            'summary_generated_at' => $this->summary_generated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'similarity_score' => isset($this->similarity_score) ? round((float)$this->similarity_score * 100) : null,
            // Intentionally excluding 'embedding' and 'content_hash' to prevent internal leakage
        ];
    }
}
