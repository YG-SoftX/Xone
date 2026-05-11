@extends('layouts.platform')
@php /** @var \App\Models\Form $form */ @endphp

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Form Header -->
    <div class="mb-6">
        <a href="{{ route('forms.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Forms</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $form->title }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Builder -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md">
                <!-- Form Title & Description -->
                <div class="p-6 border-b border-gray-200">
                    <input type="text" id="formTitle" value="{{ $form->title }}" 
                           class="w-full text-xl font-bold border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1"
                           placeholder="Form title">
                    <textarea id="formDescription" rows="2" 
                              class="w-full mt-2 border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1 resize-none"
                              placeholder="Form description">{{ $form->description }}</textarea>
                    
                    <div class="flex gap-2 mt-4">
                        <button onclick="saveFormSettings()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            💾 Save
                        </button>
                        @if($form->is_published)
                            <a href="{{ route('forms.public.show', $form->uuid) }}" target="_blank" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm">
                                👁️ Preview
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Questions List -->
                <div id="questionsList" class="divide-y divide-gray-200">
                    @foreach($questions as $question)
                        <div class="p-6 question-item" data-question-id="{{ $question->id }}">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <input type="text" value="{{ $question->title }}" 
                                           class="question-title w-full font-medium border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1"
                                           placeholder="Question title">
                                    <input type="text" value="{{ $question->description }}" 
                                           class="question-description w-full text-sm text-gray-600 border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1 mt-1"
                                           placeholder="Description (optional)">
                                </div>
                                <div class="flex gap-2 ml-4">
                                    <select class="question-type text-sm border border-gray-300 rounded-md px-2 py-1">
                                        <option value="short_text" {{ $question->type === 'short_text' ? 'selected' : '' }}>Short answer</option>
                                        <option value="paragraph" {{ $question->type === 'paragraph' ? 'selected' : '' }}>Paragraph</option>
                                        <option value="multiple_choice" {{ $question->type === 'multiple_choice' ? 'selected' : '' }}>Multiple choice</option>
                                        <option value="checkboxes" {{ $question->type === 'checkboxes' ? 'selected' : '' }}>Checkboxes</option>
                                        <option value="dropdown" {{ $question->type === 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                                        <option value="linear_scale" {{ $question->type === 'linear_scale' ? 'selected' : '' }}>Linear scale</option>
                                        <option value="date" {{ $question->type === 'date' ? 'selected' : '' }}>Date</option>
                                        <option value="email" {{ $question->type === 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="number" {{ $question->type === 'number' ? 'selected' : '' }}>Number</option>
                                    </select>
                                    <label class="flex items-center text-sm">
                                        <input type="checkbox" class="question-required mr-1" {{ $question->required ? 'checked' : '' }}>
                                        Required
                                    </label>
                                    <button onclick="deleteQuestion({{ $question->id }})" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Options for multiple choice/checkboxes/dropdown -->
                            @if(in_array($question->type, ['multiple_choice', 'checkboxes', 'dropdown']))
                                <div class="mt-3 space-y-2 question-options">
                                    @foreach(($question->options ?? []) as $option)
                                        <div class="flex items-center gap-2">
                                            @if($question->type === 'multiple_choice')
                                                <input type="radio" disabled class="w-4 h-4">
                                            @elseif($question->type === 'checkboxes')
                                                <input type="checkbox" disabled class="w-4 h-4">
                                            @else
                                                <span class="text-gray-400">▾</span>
                                            @endif
                                            <input type="text" value="{{ $option }}" class="flex-1 border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1">
                                            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-red-600">×</button>
                                        </div>
                                    @endforeach
                                    <button onclick="addOption(this)" class="text-blue-600 hover:text-blue-800 text-sm">+ Add option</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Add Question Button -->
                <div class="p-6">
                    <button onclick="addQuestion()" class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-colors">
                        + Add Question
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
                <h3 class="font-semibold text-gray-900 mb-4">Form Settings</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="isPublished" {{ $form->is_published ? 'checked' : '' }} class="mr-2">
                            <span class="text-sm">Publish form</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="acceptResponses" {{ $form->accept_responses ? 'checked' : '' }} class="mr-2">
                            <span class="text-sm">Accept responses</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="requireLogin" {{ $form->require_login ? 'checked' : '' }} class="mr-2">
                            <span class="text-sm">Require login</span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="allowMultiple" {{ $form->allow_multiple_submissions ? 'checked' : '' }} class="mr-2">
                            <span class="text-sm">Allow multiple submissions</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Responses</label>
                        <input type="number" id="maxResponses" value="{{ $form->max_responses }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                               placeholder="Unlimited">
                    </div>

                    <hr>

                    <button onclick="updateSettings()" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 text-sm">
                        Update Settings
                    </button>

                    @if($form->is_published)
                        <div class="pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-600 mb-2">Public Link:</p>
                            <input type="text" readonly value="{{ route('forms.public.show', $form->uuid) }}" 
                                   class="w-full px-2 py-1 bg-gray-50 border border-gray-300 rounded text-xs"
                                   onclick="this.select()">
                            <button onclick="copyLink()" class="mt-2 text-blue-600 hover:text-blue-800 text-xs">
                                Copy link
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function addQuestion() {
    fetch('{{ route('forms.questions.add', $form->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            title: 'Untitled Question',
            type: 'short_text',
            required: false
        })
    })
    .then(response => response.json())
    .then(data => {
        location.reload();
    });
}

function deleteQuestion(questionId) {
    if (confirm('Delete this question?')) {
        fetch(`{{ url('/forms/' . $form->id . '/questions') }}/${questionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            location.reload();
        });
    }
}

function saveFormSettings() {
    const title = document.getElementById('formTitle').value;
    const description = document.getElementById('formDescription').value;
    
    fetch('{{ route('forms.update', $form->id) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ title, description })
    })
    .then(response => response.json())
    .then(data => {
        showNotification('Form saved!');
    });
}

function updateSettings() {
    fetch('{{ route('forms.update', $form->id) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            is_published: document.getElementById('isPublished').checked,
            accept_responses: document.getElementById('acceptResponses').checked,
            require_login: document.getElementById('requireLogin').checked,
            allow_multiple_submissions: document.getElementById('allowMultiple').checked,
            max_responses: document.getElementById('maxResponses').value || null
        })
    })
    .then(response => response.json())
    .then(data => {
        showNotification('Settings updated!');
        setTimeout(() => location.reload(), 1000);
    });
}

function addOption(button) {
    const container = button.parentElement;
    const newOption = document.createElement('div');
    newOption.className = 'flex items-center gap-2';
    newOption.innerHTML = `
        <input type="radio" disabled class="w-4 h-4">
        <input type="text" placeholder="Option" class="flex-1 border-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1">
        <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-red-600">×</button>
    `;
    container.insertBefore(newOption, button);
}

function copyLink() {
    const input = event.target.previousElementSibling;
    input.select();
    document.execCommand('copy');
    showNotification('Link copied!');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-md shadow-lg';
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 2000);
}
</script>
@endpush
@endsection
