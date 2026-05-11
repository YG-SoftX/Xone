@extends('layouts.app')
@section('title', 'New Project')

@section('content')

<div class="max-w-xl">
    <div class="mb-8">
        <a href="{{ route('projects.index') }}" class="text-sm hover:underline" style="color:#9b8e90">← Projects</a>
        <h2 class="text-xl font-bold mt-3">Create a new project</h2>
    </div>

    @if($errors->has('api'))
        <div class="mb-6 px-4 py-3 rounded-xl text-sm" style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2)">
            {{ $errors->first('api') }}
        </div>
    @endif

    <form method="POST" action="{{ route('projects.store') }}" class="space-y-5">
        @csrf

        <div class="rounded-2xl p-6 space-y-5" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
            <div>
                <label class="block text-sm font-medium mb-2">Project Name <span style="color:#ff003c">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. My Awesome App"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none transition"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                @error('name')<p class="text-xs mt-1" style="color:#ff6b6b">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" placeholder="What does this project do?"
                          class="w-full px-4 py-2.5 rounded-xl text-sm outline-none resize-none"
                          style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Website URL</label>
                <input type="url" name="website_url" value="{{ old('website_url') }}"
                       placeholder="https://example.com"
                       class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                       style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Environment <span style="color:#ff003c">*</span></label>
                <select name="environment" required
                        class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                        style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                    <option value="development" {{ old('environment','development')==='development'?'selected':'' }}>Development</option>
                    <option value="staging"     {{ old('environment')==='staging'    ?'selected':'' }}>Staging</option>
                    <option value="production"  {{ old('environment')==='production' ?'selected':'' }}>Production</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold transition hover:opacity-90"
                    style="background:#ff003c;color:#fff">Create Project</button>
            <a href="{{ route('projects.index') }}"
               class="px-6 py-2.5 rounded-xl text-sm font-semibold transition hover:bg-white/5"
               style="color:#9b8e90;border:1px solid rgba(255,255,255,0.08)">Cancel</a>
        </div>
    </form>
</div>

@endsection
