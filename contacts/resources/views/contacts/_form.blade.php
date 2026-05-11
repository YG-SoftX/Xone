{{-- Shared contact form partial. Expects: $action, $method, optional $contact --}}
<form method="POST" action="{{ $action }}" x-data="{
    emails: {{ json_encode($contact->emails ?? [['email'=>'','type'=>'work','is_primary'=>true]]) }},
    phones: {{ json_encode($contact->phones ?? [['number'=>'','type'=>'mobile']]) }},
}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

        {{-- Basic info --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name', $contact->first_name ?? '') }}" required
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="First name">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name', $contact->last_name ?? '') }}"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Last name">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                <input type="text" name="company" value="{{ old('company', $contact->company ?? '') }}"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Company name">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                <input type="text" name="job_title" value="{{ old('job_title', $contact->job_title ?? '') }}"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Software Engineer">
            </div>
        </div>

        {{-- Emails --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">Email Addresses</label>
                <button type="button" @click="emails.push({email:'',type:'work',is_primary:false})"
                        class="text-xs text-blue-600 hover:text-blue-800">+ Add email</button>
            </div>
            <template x-for="(emailEntry, i) in emails" :key="i">
                <div class="flex gap-2 mb-2">
                    <input type="email" :name="`emails[${i}][email]`" x-model="emailEntry.email"
                           class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="email@example.com">
                    <select :name="`emails[${i}][type]`" x-model="emailEntry.type"
                            class="border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none">
                        <option value="work">Work</option>
                        <option value="personal">Personal</option>
                        <option value="other">Other</option>
                    </select>
                    <button type="button" @click="emails.splice(i,1)" x-show="emails.length > 1"
                            class="text-gray-400 hover:text-red-500 px-2"><i class="fas fa-times"></i></button>
                </div>
            </template>
        </div>

        {{-- Phones --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700">Phone Numbers</label>
                <button type="button" @click="phones.push({number:'',type:'mobile'})"
                        class="text-xs text-blue-600 hover:text-blue-800">+ Add phone</button>
            </div>
            <template x-for="(phone, i) in phones" :key="i">
                <div class="flex gap-2 mb-2">
                    <input type="tel" :name="`phones[${i}][number]`" x-model="phone.number"
                           class="flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="+1 555 000 0000">
                    <select :name="`phones[${i}][type]`" x-model="phone.type"
                            class="border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none">
                        <option value="mobile">Mobile</option>
                        <option value="work">Work</option>
                        <option value="home">Home</option>
                        <option value="other">Other</option>
                    </select>
                    <button type="button" @click="phones.splice(i,1)" x-show="phones.length > 1"
                            class="text-gray-400 hover:text-red-500 px-2"><i class="fas fa-times"></i></button>
                </div>
            </template>
        </div>

        {{-- Birthday + Notes --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Birthday</label>
                <input type="date" name="birthday" value="{{ old('birthday', optional($contact->birthday ?? null)?->format('Y-m-d')) }}"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="3"
                      class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                      placeholder="Add notes...">{{ old('notes', $contact->notes ?? '') }}</textarea>
        </div>

        {{-- Labels/Groups --}}
        @if(isset($groups) && $groups->count())
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Labels</label>
            <div class="flex flex-wrap gap-2">
                @foreach($groups as $group)
                <label class="cursor-pointer">
                    <input type="checkbox" name="groups[]" value="{{ $group->id }}" class="sr-only peer"
                           {{ in_array($group->id, $contact->groups->pluck('id')->toArray() ?? []) ? 'checked' : '' }}>
                    <span class="px-3 py-1.5 rounded-full text-xs font-medium border-2 transition
                                 peer-checked:text-white peer-checked:border-transparent
                                 border-gray-200 text-gray-600 peer-checked:bg-{{ $group->color }}"
                          style="--label-bg: {{ $group->color }}">
                        {{ $group->name }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Submit --}}
        <div class="flex justify-between pt-2">
            <a href="{{ isset($contact) ? route('contacts.show', $contact->id) : route('contacts.index') }}"
               class="px-5 py-2 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                {{ isset($contact) ? 'Save Changes' : 'Create Contact' }}
            </button>
        </div>
    </div>
</form>
