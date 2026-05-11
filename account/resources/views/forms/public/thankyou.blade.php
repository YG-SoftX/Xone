<!DOCTYPE html>
@php /** @var \App\Models\Form $form */ @endphp
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - {{ $form->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Thank You!</h1>
                <p class="text-gray-600 mb-6">{{ json_decode($form->confirmation_message)->message ?? 'Your response has been recorded.' }}</p>
                
                @if(json_decode($form->confirmation_message)->show_link)
                    <a href="{{ route('forms.public.show', $form->uuid) }}" 
                       class="inline-block px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        {{ json_decode($form->confirmation_message)->link_text ?? 'Submit another response' }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
