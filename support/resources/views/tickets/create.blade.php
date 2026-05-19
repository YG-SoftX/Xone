@extends('layouts.app')
@section('title', 'New Ticket')

@section('content')
<div class="max-w-2xl">
    <h2 class="text-lg font-bold mb-6">Create a Support Ticket</h2>

    <form method="POST" action="{{ route('tickets.store') }}"
          class="rounded-2xl p-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
        @csrf

        <div class="space-y-5">
            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium mb-1.5">Subject</label>
                <input type="text" name="subject" required maxlength="255"
                       placeholder="Brief description of your issue"
                       class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/5 border text-white placeholder-gray-500 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/20 transition"
                       style="border-color:rgba(255,255,255,0.1)">
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium mb-1.5">Category</label>
                <select name="category" required
                        class="w-full px-4 py-2.5 rounded-xl text-sm bg-white/5 border text-white focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/20 transition"
                        style="border-color:rgba(255,255,255,0.1)">
                    <option value="" class="bg-gray-900">Select a category...</option>
                    <option value="billing" class="bg-gray-900">Billing & Payments</option>
                    <option value="technical" class="bg-gray-900">Technical Issue</option>
                    <option value="account" class="bg-gray-900">Account & Access</option>
                    <option value="feature" class="bg-gray-900">Feature Request</option>
                    <option value="other" class="bg-gray-900">Other</option>
                </select>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-medium mb-1.5">Priority</label>
                <div class="flex gap-3">
                    @foreach(['low' => '🟢 Low', 'medium' => '🟡 Medium', 'high' => '🔴 High'] as $val => $label)
                    <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl cursor-pointer transition text-sm"
                           style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06)"
                           x-data
                           @click="$el.querySelector('input').checked = true">
                        <input type="radio" name="priority" value="{{ $val }}" required
                               class="text-red-500 focus:ring-red-400">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-sm font-medium mb-1.5">Describe your issue</label>
                <textarea name="message" rows="8" required maxlength="10000"
                          placeholder="Please provide as much detail as possible..."
                          class="w-full px-4 py-3 rounded-xl text-sm bg-white/5 border text-white placeholder-gray-500 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/20 transition resize-y"
                          style="border-color:rgba(255,255,255,0.1)"></textarea>
                <p class="text-xs mt-1" style="color:#4a4044">Max 10,000 characters</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t" style="border-color:rgba(255,255,255,0.06)">
            <a href="{{ route('tickets.index') }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors hover:bg-white/5"
               style="color:#9b8e90">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all hover:opacity-90"
                    style="background:#ff003c;color:#fff">
                <i class="fas fa-paper-plane mr-1.5"></i> Submit Ticket
            </button>
        </div>
    </form>
</div>
@endsection
