@extends('layouts.platform')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Forms Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">YG Forms</h1>
            <p class="text-sm text-gray-600">Create surveys, quizzes, and forms</p>
        </div>
        <a href="{{ route('forms.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            ➕ Create Form
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Forms</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_forms'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Published</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['published_forms'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Responses</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_responses'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Grid -->
    @if($forms->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($forms as $form)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="flex gap-2">
                                @if($form->is_published)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        Draft
                                    </span>
                                @endif
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate">{{ $form->title }}</h3>
                        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $form->description ?? 'No description' }}</p>

                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span>{{ $form->response_count }} responses</span>
                            <span>{{ $form->questions->count() }} questions</span>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('forms.edit', $form->id) }}" class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition-colors text-center text-sm">
                                Edit
                            </a>
                            <a href="{{ route('forms.responses', $form->id) }}" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-50 transition-colors text-center text-sm">
                                Responses
                            </a>
                        </div>

                        @if($form->is_published)
                            <a href="{{ route('forms.public.show', $form->uuid) }}" target="_blank" 
                               class="mt-2 block text-center text-blue-600 hover:text-blue-800 text-sm">
                                View Public Link →
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($forms->hasPages())
            <div class="mt-6">
                {{ $forms->links() }}
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">No forms yet</p>
            <a href="{{ route('forms.create') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800 text-sm font-medium">
                Create your first form →
            </a>
        </div>
    @endif
</div>
@endsection
