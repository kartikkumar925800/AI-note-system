<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure pgvector extension is enabled for vector search
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            // Gemini text-embedding-004 has 768 dimensions
            $table->vector('embedding', 768)->nullable();
            
            // AI Summary fields
            $table->text('summary')->nullable();
            $table->string('summary_model')->nullable();
            $table->timestamp('summary_generated_at')->nullable();
            
            // Content hash for idempotency (so we don't regenerate summary if content unchanged)
            $table->string('content_hash')->nullable();

            $table->timestamps();
        });

        // Create HNSW index for fast vector similarity search (cosine distance)
        DB::statement('CREATE INDEX notes_embedding_index ON notes USING hnsw (embedding vector_cosine_ops)');
        
        // Full-text search index for hybrid search
        DB::statement("CREATE INDEX notes_content_fts_idx ON notes USING GIN (to_tsvector('english', title || ' ' || content))");
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        DB::statement('DROP EXTENSION IF EXISTS vector');
    }
};
