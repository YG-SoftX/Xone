@extends('layouts.app')
@section('title', 'Team — ' . ($project['name'] ?? ''))

@section('content')
@php $pid = $project['id']; @endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('projects.show', $pid) }}" class="text-xs hover:underline" style="color:#9b8e90">← {{ $project['name'] }}</a>
        <h2 class="text-xl font-bold mt-2">Team</h2>
    </div>
</div>

{{-- Invite form --}}
<div class="rounded-2xl p-6 mb-6" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06)">
    <h3 class="text-sm font-semibold mb-4">Invite a member</h3>
    <form method="POST" action="{{ route('projects.team.invite', $pid) }}" class="flex items-end gap-3">
        @csrf
        <div class="flex-1">
            <label class="block text-xs mb-1.5" style="color:#9b8e90">Email address</label>
            <input type="email" name="email" required placeholder="colleague@example.com"
                   class="w-full px-4 py-2.5 rounded-xl text-sm outline-none"
                   style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
        </div>
        <div>
            <label class="block text-xs mb-1.5" style="color:#9b8e90">Role</label>
            <select name="role" class="px-4 py-2.5 rounded-xl text-sm outline-none"
                    style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                <option value="editor">Editor</option>
                <option value="viewer">Viewer</option>
                <option value="billing_admin">Billing Admin</option>
            </select>
        </div>
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90"
                style="background:#ff003c;color:#fff">Send Invite</button>
    </form>
</div>

{{-- Members list --}}
<div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.06)">
    <div class="px-6 py-4 text-sm font-semibold" style="background:rgba(255,255,255,0.02);border-bottom:1px solid rgba(255,255,255,0.06)">
        {{ count($members) }} member{{ count($members)!==1?'s':'' }}
    </div>
    @foreach($members as $member)
    <div class="px-6 py-4 border-b last:border-0 flex items-center justify-between"
         style="border-color:rgba(255,255,255,0.04)">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                 style="background:rgba(155,27,48,0.3);color:#ff003c">
                {{ strtoupper(substr($member['name']??'?',0,1)) }}
            </div>
            <div>
                <div class="text-sm font-medium">{{ $member['name'] }}</div>
                <div class="text-xs" style="color:#9b8e90">{{ $member['email'] }}</div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($member['role'] !== 'owner')
            <form method="POST" action="{{ route('projects.team.role', [$pid, $member['id']]) }}" class="flex items-center gap-2">
                @csrf @method('PUT')
                <select name="role" onchange="this.form.submit()"
                        class="px-3 py-1.5 rounded-lg text-xs outline-none"
                        style="background:#0a0a0a;border:1px solid rgba(255,255,255,0.1);color:#fff">
                    @foreach(['editor'=>'Editor','viewer'=>'Viewer','billing_admin'=>'Billing Admin'] as $val=>$label)
                    <option value="{{ $val }}" {{ $member['role']===$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('projects.team.remove', [$pid, $member['id']]) }}"
                  onsubmit="return confirm('Remove this member?')">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 rounded-lg text-xs hover:bg-red-500/10"
                        style="color:#ff6b6b;border:1px solid rgba(255,0,60,0.15)">Remove</button>
            </form>
            @else
            <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(155,27,48,0.2);color:#ff003c">Owner</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
