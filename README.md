# AI-Powered Notes Management System

This is a production-grade Notes Management System built with Laravel 11 and Vue.js. It features a complete AI integration for Semantic Search (via PostgreSQL pgvector) and intelligent note summarization.

## Demo video:


https://github.com/user-attachments/assets/62414109-3fef-4696-b639-4acce0795f94


## Setup Instructions

1. **Prerequisites**: Ensure Docker and Docker Compose are installed.
2. **Environment**: Clone the repository and configure your `.env` file:
   ```bash
   cp .env.example .env
   # Add your Gemini API key to .env
   GEMINI_API_KEY="your-gemini-key"
   ```
3. **Start the Sail Containers**:
   ```bash
   ./vendor/bin/sail up -d
   ```
4. **Run Migrations & Tests**:
   ```bash
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail artisan test
   ```
5. **Access the App**: Navigate to `http://localhost` in your browser.

---

## API Documentation

The backend exposes clean, standard REST APIs returning consistent JSON envelopes.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/notes` | List notes (paginated). Optional `?q=search` parameter performs AI Semantic Search. |
| `POST` | `/api/notes` | Create a note. Body: `{"title": "...", "content": "..."}`. Generates AI vector automatically. |
| `GET` | `/api/notes/{id}` | Get a specific note. |
| `PUT` | `/api/notes/{id}` | Update a note. Invalidates AI cache if content changes. |
| `DELETE`| `/api/notes/{id}` | Delete a note. |
| `POST` | `/api/notes/{id}/summary` | Generate an AI summary for the note. Idempotent. |

---

## Database Schema

Database used: **PostgreSQL 16 (with pgvector)**

Table: `notes`
- `id` (bigint, PK)
- `title` (varchar)
- `content` (text)
- `embedding` (vector, 768 dimensions) - Stores the AI representation of the note. Indexed using `hnsw`.
- `summary` (text, nullable)
- `summary_model` (varchar, nullable)
- `summary_generated_at` (timestamp, nullable)
- `content_hash` (varchar) - MD5 hash of title+content used for idempotency.
- `created_at`, `updated_at` (timestamps)

---

## Architecture Explanation

- **Repository Pattern & Interfaces**: Abstracted database logic and AI providers (`NoteRepositoryInterface`, `SummarizerInterface`, `EmbeddingGeneratorInterface`) bound via Laravel's Service Container for true Dependency Inversion.
- **DTOs & API Resources**: Strict data transfer objects and JSON resources ensure clean, consistent API envelopes and prevent internal data leakage (like raw embedding floats).
- **Graceful Degradation**: If the Gemini API is rate-limited or offline, the semantic search automatically degrades to PostgreSQL's native full-text search (`to_tsvector`).
- **Tiered Rate Limiting**: AI endpoints are throttled strictly (5 requests/minute) to prevent abuse and cost overruns, while standard CRUD operations enjoy generous limits (60/min).
- **Security**: Parameter binding on all raw vector SQL queries prevents SQL injection. `FormRequest` validation enforces input caps.

---

## AI Tools Used & Prompts

### Code Generation & Scaffolding
- **Claude / Gemini**: AI pairs were used to rapidly scaffold the Laravel backend, construct the Vue + Tailwind SPA frontend, and write the PHPUnit feature tests. 
- **Validation**: All AI-generated code was locally validated by running static analysis (PHPCS/Linting), building the containers (`sail up`), and passing the automated test suite (`sail artisan test`).

### Integrated AI Features (Gemini API)
- **Summarization Model**: `gemini-2.5-flash`
  - *Prompt Used*: `"You are an expert assistant. Provide a highly concise, direct summary of the following text in 1-2 sentences. Do not use filler words like 'The writer' or 'This note'. Just summarize the core facts directly.\n\nText:\n{content}"*
- **Vector Embedding Model**: `gemini-embedding-2` (Output Dimensionality strictly set to 768 to match the database).
  - *Usage*: Both Note contents and Search Queries are converted to 768-dimension vectors and mathematically compared in PostgreSQL using Cosine Distance (`<=>`).
