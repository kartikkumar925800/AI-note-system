<?php
namespace App\Repositories;

use App\Contracts\Repositories\NoteRepositoryInterface;
use App\Models\Note;
use App\DTOs\NoteDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NoteRepository implements NoteRepositoryInterface
{
    public function getPaginated(int $perPage = 10, ?string $searchQuery = null, ?array $queryEmbedding = null): LengthAwarePaginator
    {
        $query = Note::query();

        if ($queryEmbedding) {
            // Pure vector semantic search: ranks ALL notes by conceptual similarity
            // <=> is the cosine distance operator in pgvector. Lower distance = higher similarity.
            $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';
            // Select the cosine similarity (1 - distance) and filter out completely unrelated notes (distance > 0.35)
            $query->selectRaw("notes.*, 1 - (embedding <=> ?::vector) AS similarity_score", [$embeddingStr])
                  ->whereRaw("embedding <=> ?::vector < 0.35", [$embeddingStr])
                  ->orderByRaw("embedding <=> ?::vector", [$embeddingStr]);
        } elseif ($searchQuery) {
            // Graceful fallback to PostgreSQL full-text search if AI is unavailable
            $query->whereRaw("to_tsvector('english', title || ' ' || content) @@ plainto_tsquery('english', ?)", [$searchQuery])
                  ->orderByDesc('created_at');
        } else {
            // Default listing: newest notes at the top
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Note
    {
        return Note::find($id);
    }

    public function create(NoteDTO $dto): Note
    {
        $hash = md5($dto->title . $dto->content);
        return Note::create([
            'title' => $dto->title,
            'content' => $dto->content,
            'content_hash' => $hash,
        ]);
    }

    public function update(Note $note, NoteDTO $dto): Note
    {
        $hash = md5($dto->title . $dto->content);
        
        $data = [
            'title' => $dto->title,
            'content' => $dto->content,
            'content_hash' => $hash,
        ];

        // If content changes, invalidate old summary and embeddings
        if ($note->content_hash !== $hash) {
            $data['summary'] = null;
            $data['embedding'] = null;
        }

        $note->update($data);
        return $note;
    }

    public function delete(Note $note): bool
    {
        return $note->delete();
    }
    
    public function saveEmbedding(Note $note, array $embedding): bool
    {
        $note->embedding = '[' . implode(',', $embedding) . ']';
        return $note->save();
    }

    public function saveSummary(Note $note, string $summary, string $model): bool
    {
        $note->summary = $summary;
        $note->summary_model = $model;
        $note->summary_generated_at = now();
        return $note->save();
    }
}
