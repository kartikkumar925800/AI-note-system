<?php
namespace App\Contracts\Repositories;

use App\Models\Note;
use App\DTOs\NoteDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface NoteRepositoryInterface
{
    public function getPaginated(int $perPage = 10, ?string $searchQuery = null, ?array $queryEmbedding = null): LengthAwarePaginator;
    public function findById(int $id): ?Note;
    public function create(NoteDTO $dto): Note;
    public function update(Note $note, NoteDTO $dto): Note;
    public function delete(Note $note): bool;
    public function saveEmbedding(Note $note, array $embedding): bool;
    public function saveSummary(Note $note, string $summary, string $model): bool;
}
