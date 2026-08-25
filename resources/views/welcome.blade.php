<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Notes System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #09090b; color: #fafafa; }
        .glass { background: rgba(24, 24, 27, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="min-h-screen p-8">
    <div id="app" class="max-w-4xl mx-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">AI Notes</h1>
                <p class="text-zinc-400 text-sm mt-1">Semantic Search & AI Summaries</p>
            </div>
            <div class="relative w-64">
                <input v-model="searchQuery" @input="debounceSearch" type="text" placeholder="Semantic Search..." 
                    class="w-full bg-zinc-900 border border-zinc-700 rounded-lg py-2 px-4 text-sm focus:outline-none focus:border-blue-500 transition-colors">
            </div>
        </header>

        <!-- Main Content -->
        <div class="grid grid-cols-3 gap-6">
            <!-- Note Form -->
            <div class="col-span-1">
                <form @submit.prevent="saveNote" class="glass rounded-xl p-5 shadow-2xl">
                    <h2 class="text-lg font-semibold mb-4 text-zinc-100">[[ editingNote ? 'Edit Note' : 'New Note' ]]</h2>
                    <input v-model="form.title" type="text" placeholder="Title" required
                        class="w-full bg-zinc-900 border border-zinc-700 rounded-md py-2 px-3 text-sm mb-3 focus:outline-none focus:border-blue-500">
                    <textarea v-model="form.content" placeholder="Content..." rows="6" required
                        class="w-full bg-zinc-900 border border-zinc-700 rounded-md py-2 px-3 text-sm mb-4 focus:outline-none focus:border-blue-500"></textarea>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white rounded-md py-2 text-sm font-medium transition-colors">
                            [[ editingNote ? 'Update' : 'Save' ]]
                        </button>
                        <button v-if="editingNote" @click="resetForm" type="button" class="w-full bg-zinc-700 hover:bg-zinc-600 text-white rounded-md py-2 text-sm font-medium transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notes List -->
            <div class="col-span-2 space-y-4">
                <div v-if="searchQuery" class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold text-emerald-400">Semantic Search Results</h2>
                    <span class="text-xs text-zinc-500">Filtered by AI conceptual similarity</span>
                </div>
                
                <div v-if="loading" class="text-center text-zinc-500 py-10">Loading notes...</div>
                <div v-else-if="notes.length === 0" class="text-center text-zinc-500 py-10">
                    [[ searchQuery ? 'No relevant notes found.' : 'No notes yet. Create one!' ]]
                </div>
                
                <div v-for="note in notes" :key="note.id" class="glass rounded-xl p-5 hover:border-zinc-500 transition-colors group">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-semibold text-blue-300">[[ note.title ]]</h3>
                            <span v-if="note.similarity_score" class="text-xs bg-emerald-900 text-emerald-300 px-2 py-0.5 rounded border border-emerald-700">
                                [[ note.similarity_score ]]% Match
                            </span>
                        </div>
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="editNote(note)" class="text-zinc-400 hover:text-blue-400">✎</button>
                            <button @click="deleteNote(note.id)" class="text-zinc-400 hover:text-red-400">🗑</button>
                        </div>
                    </div>
                    <p class="text-zinc-300 text-sm mb-4 whitespace-pre-wrap">[[ note.content ]]</p>
                    
                    <div class="border-t border-zinc-800 pt-3 mt-3">
                        <div class="flex justify-between items-center">
                            <div class="flex-1">
                                <div v-if="note.summary" class="text-xs bg-zinc-900 text-emerald-300 p-3 rounded-md border border-zinc-800">
                                    <strong class="text-emerald-500 mb-1 block">AI Summary (gemini-2.5-flash):</strong>
                                    [[ note.summary ]]
                                </div>
                            </div>
                            <button @click="generateSummary(note.id)" class="ml-4 bg-zinc-800 hover:bg-zinc-700 text-xs px-3 py-1.5 rounded text-zinc-300 transition-colors flex items-center gap-1 border border-zinc-700">
                                [[ note.summary ? 'Regenerate' : 'Summarize' ]]
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue

        createApp({
            delimiters: ['[[', ']]'],
            data() {
                return {
                    notes: [],
                    searchQuery: '',
                    loading: false,
                    editingNote: null,
                    form: { title: '', content: '' },
                    searchTimeout: null
                }
            },
            mounted() {
                this.fetchNotes();
            },
            methods: {
                async fetchNotes() {
                    this.loading = true;
                    try {
                        const url = this.searchQuery ? `/api/notes?q=${encodeURIComponent(this.searchQuery)}` : '/api/notes';
                        const res = await fetch(url);
                        const data = await res.json();
                        this.notes = data.data;
                    } catch (e) {
                        console.error(e);
                    }
                    this.loading = false;
                },
                debounceSearch() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.fetchNotes();
                    }, 500);
                },
                async saveNote() {
                    const method = this.editingNote ? 'PUT' : 'POST';
                    const url = this.editingNote ? `/api/notes/${this.editingNote.id}` : '/api/notes';
                    
                    await fetch(url, {
                        method: method,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    
                    this.resetForm();
                    this.fetchNotes();
                },
                async deleteNote(id) {
                    if (!confirm('Delete this note?')) return;
                    await fetch(`/api/notes/${id}`, { method: 'DELETE' });
                    this.fetchNotes();
                },
                editNote(note) {
                    this.editingNote = note;
                    this.form = { title: note.title, content: note.content };
                },
                resetForm() {
                    this.editingNote = null;
                    this.form = { title: '', content: '' };
                },
                async generateSummary(id) {
                    const idx = this.notes.findIndex(n => n.id === id);
                    this.notes[idx].summary = 'Generating summary...';
                    
                    const res = await fetch(`/api/notes/${id}/summary`, { 
                        method: 'POST',
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    
                    if (res.ok) {
                        this.notes[idx] = data.data;
                    } else {
                        alert(data.error?.message || 'Error generating summary');
                        this.notes[idx].summary = null;
                    }
                }
            }
        }).mount('#app')
    </script>
</body>
</html>
