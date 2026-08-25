<?php
namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use App\Services\NoteService;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(private NoteService $noteService) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('limit', 10);
        $search = $request->query('q');

        $notes = $this->noteService->getNotes($perPage, $search);

        return NoteResource::collection($notes);
    }

    public function store(NoteRequest $request)
    {
        $dto = new \App\DTOs\NoteDTO($request->validated('title'), $request->validated('content'));
        $note = $this->noteService->createNote($dto);

        return new NoteResource($note);
    }

    public function show(Note $note)
    {
        return new NoteResource($note);
    }

    public function update(NoteRequest $request, Note $note)
    {
        $dto = new \App\DTOs\NoteDTO($request->validated('title'), $request->validated('content'));
        $note = $this->noteService->updateNote($note, $dto);

        return new NoteResource($note);
    }

    public function destroy(Note $note)
    {
        $this->noteService->deleteNote($note);
        return response()->json(null, 204);
    }

    public function summary(Note $note)
    {
        $note = $this->noteService->generateSummary($note);
        return new NoteResource($note);
    }
}
