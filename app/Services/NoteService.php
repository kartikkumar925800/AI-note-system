<?php
namespace App\Services;

use App\Contracts\Repositories\NoteRepositoryInterface;
use App\Contracts\AI\SummarizerInterface;
use App\Contracts\AI\EmbeddingGeneratorInterface;
use App\DTOs\NoteDTO;
use App\Models\Note;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteService
{
    public function __construct(
        private NoteRepositoryInterface $repository,
        private SummarizerInterface $summarizer,
        private EmbeddingGeneratorInterface $embeddingGenerator
    ) {}

    public function getNotes(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $queryEmbedding = null;
        if ($search) {
            try {
                $queryEmbedding = $this->embeddingGenerator->generate($search);
            } catch (\Exception $e) {
                // If Gemini is rate-limited or down, fallback to full-text search
                \Illuminate\Support\Facades\Log::warning('AI Search degraded to full-text: ' . $e->getMessage());
            }
        }
        
        return $this->repository->getPaginated($perPage, $search, $queryEmbedding);
    }

    public function createNote(NoteDTO $dto): Note
    {
        $note = $this->repository->create($dto);
        $this->generateAndSaveEmbedding($note);
        return $note;
    }

    public function updateNote(Note $note, NoteDTO $dto): Note
    {
        $oldHash = $note->content_hash;
        $note = $this->repository->update($note, $dto);

        if ($oldHash !== $note->content_hash) {
            $this->generateAndSaveEmbedding($note);
        }

        return $note;
    }

    public function deleteNote(Note $note): bool
    {
        return $this->repository->delete($note);
    }

    public function generateSummary(Note $note): Note
    {
        // Idempotency check: if summary exists and content hash matches current content
        $currentHash = md5($note->title . $note->content);
        if ($note->summary && $note->content_hash === $currentHash) {
            return $note;
        }

        $summaryText = $this->summarizer->summarize($note->content);
        $this->repository->saveSummary($note, $summaryText, $this->summarizer->getModelName());
        
        return $note;
    }

    private function generateAndSaveEmbedding(Note $note): void
    {
        $embedding = $this->embeddingGenerator->generate($note->title . "\n" . $note->content);
        $this->repository->saveEmbedding($note, $embedding);
    }
}
