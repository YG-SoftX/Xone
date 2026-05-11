<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }} - YG Forms</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-3xl mx-auto">
            <!-- Form Header -->
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="p-6 border-b-8" style="border-color: {{ json_decode($form->theme)->primary_color ?? '#4285f4' }}">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $form->title }}</h1>
                    @if($form->description)
                        <p class="mt-2 text-gray-600">{{ $form->description }}</p>
                    @endif
                    @if($form->show_progress_bar)
                        <div class="mt-4">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><span id="currentQuestion">1</span> of {{ $questions->count() }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Form Questions -->
            <form id="formSubmit" action="{{ route('forms.public.submit', $form->uuid) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                @foreach($questions as $index => $question)
                    <div class="bg-white rounded-lg shadow-md p-6 question-card" data-question-index="{{ $index }}">
                        <div class="mb-4">
                            <label class="block text-gray-900 font-medium mb-2">
                                {{ $question->title }}
                                @if($question->required)
                                    <span class="text-red-600">*</span>
                                @endif
                            </label>
                            @if($question->description)
                                <p class="text-sm text-gray-600 mb-3">{{ $question->description }}</p>
                            @endif

                            <!-- Question Input Based on Type -->
                            @switch($question->type)
                                @case('short_text')
                                    <input type="text" name="question_{{ $question->id }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           {{ $question->required ? 'required' : '' }}>
                                    @break

                                @case('paragraph')
                                    <textarea name="question_{{ $question->id }}" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              {{ $question->required ? 'required' : '' }}></textarea>
                                    @break

                                @case('multiple_choice')
                                    <div class="space-y-2">
                                        @foreach(($question->options ?? []) as $option)
                                            <label class="flex items-center">
                                                <input type="radio" name="question_{{ $question->id }}" value="{{ $option }}"
                                                       class="mr-2" {{ $question->required ? 'required' : '' }}>
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @case('checkboxes')
                                    <div class="space-y-2">
                                        @foreach(($question->options ?? []) as $option)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="question_{{ $question->id }}[]" value="{{ $option }}"
                                                       class="mr-2">
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @case('dropdown')
                                    <select name="question_{{ $question->id }}" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            {{ $question->required ? 'required' : '' }}>
                                        <option value="">Select an option</option>
                                        @foreach(($question->options ?? []) as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('email')
                                    <input type="email" name="question_{{ $question->id }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           {{ $question->required ? 'required' : '' }}>
                                    @break

                                @case('number')
                                    <input type="number" name="question_{{ $question->id }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           {{ $question->required ? 'required' : '' }}>
                                    @break

                                @case('date')
                                    <input type="date" name="question_{{ $question->id }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           {{ $question->required ? 'required' : '' }}>
                                    @break

                                @case('file_upload')
                                    <input type="file" name="files[{{ $question->id }}]" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                           {{ $question->required ? 'required' : '' }}>
                                    @break
                            @endswitch
                        </div>
                    </div>
                @endforeach

                <!-- Submit Button -->
                <div class="flex justify-between items-center">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium">
                        Submit
                    </button>
                    <p class="text-sm text-gray-500">Never submit passwords through forms.</p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update progress bar
        const totalQuestions = {{ $questions->count() }};
        let currentQuestion = 1;

        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('change', updateProgress);
            input.addEventListener('input', updateProgress);
        });

        function updateProgress() {
            const answered = Array.from(document.querySelectorAll('[name^="question_"]')).filter(input => {
                if (input.type === 'radio') {
                    return input.checked;
                } else if (input.type === 'checkbox') {
                    return input.checked;
                }
                return input.value.trim() !== '';
            }).length;

            const progress = Math.min(100, (answered / totalQuestions) * 100);
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentQuestion').textContent = Math.min(currentQuestion + 1, totalQuestions);
        }

        // Form submission
        document.getElementById('formSubmit').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                if (response.ok) {
                    window.location.href = response.url;
                } else {
                    alert('Error submitting form. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting form. Please try again.');
            });
        });
    </script>
</body>
</html>
