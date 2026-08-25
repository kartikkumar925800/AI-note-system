<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Note;
use App\Contracts\AI\SummarizerInterface;
use App\Contracts\AI\EmbeddingGeneratorInterface;
use Mockery\MockInterface;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Embedding Generator for all tests so we don't hit the real Gemini API
        // This ensures tests are deterministic, fast, and free.
        $this->mock(EmbeddingGeneratorInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->andReturn(array_fill(0, 768, 0.1));
        });
    }

    public function test_can_create_note()
    {
        $payload = [
            'title' => 'Test Note',
            'content' => 'This is a test note.'
        ];

        $response = $this->postJson('/api/notes', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('data.title', 'Test Note');

        $this->assertDatabaseHas('notes', ['title' => 'Test Note']);
    }

    public function test_can_generate_summary_with_mocked_ai()
    {
        // Mock Summarizer specifically for this test
        $this->mock(SummarizerInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('summarize')->once()->andReturn('Mocked summary text.');
            $mock->shouldReceive('getModelName')->andReturn('mock-model');
        });

        $note = Note::create([
            'title' => 'AI Test',
            'content' => 'Content to summarize',
            'content_hash' => md5('AI Test' . 'Content to summarize'),
        ]);

        $response = $this->postJson("/api/notes/{$note->id}/summary");

        $response->assertStatus(200)
                 ->assertJsonPath('data.summary', 'Mocked summary text.');
                 
        $this->assertDatabaseHas('notes', [
            'id' => $note->id, 
            'summary' => 'Mocked summary text.'
        ]);
    }

    public function test_validation_fails_if_content_missing()
    {
        $response = $this->postJson('/api/notes', ['title' => 'Missing content']);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['content']);
    }

    public function test_rate_limiting_on_ai_endpoints()
    {
        $this->mock(SummarizerInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('summarize')->andReturn('Mocked');
            $mock->shouldReceive('getModelName')->andReturn('mock');
        });

        $note = Note::create([
            'title' => 'Rate Limit Test',
            'content' => 'Content',
            'content_hash' => md5('Rate Limit Test' . 'Content'),
        ]);

        // Rate limit is 5 per minute
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/notes/{$note->id}/summary")->assertStatus(200);
        }

        // 6th request should hit 429 Too Many Requests
        $this->postJson("/api/notes/{$note->id}/summary")->assertStatus(429);
    }
}
