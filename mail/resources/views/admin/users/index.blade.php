@extends('admin.layout')

@section('title', 'Users')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <p style="color:#94a3b8;">{{ $users->total() }} total users</p>
</div>

<table>
    <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Emails</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->mails_count }}</td>
                <td>{{ $user->created_at->format('Y-m-d') }}</td>
                <td>
                    @if($user->id !== 1)
                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display:inline" onsubmit="return confirm('Delete this user and all their emails?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    @else
                        <span style="color:#64748b;font-size:12px;">Primary admin</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" style="color:#64748b">No users found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $users->links() }}
