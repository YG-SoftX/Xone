<?php

namespace App\Http\Controllers;

use App\Jobs\EnrichContactJob;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\ContactImportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('search');
        $groupId = $request->query('group');
        $filter = $request->query('filter', 'all'); // all | starred | no_email

        $query = Contact::forUser($user->id)->with('groups');

        if ($search) {
            $query->search($search);
        }
        if ($groupId) {
            $query->whereHas('groups', fn($q) => $q->where('contact_groups.id', $groupId));
        }
        if ($filter === 'starred') {
            $query->starred();
        }
        if ($filter === 'no_email') {
            $query->whereNull('emails')->orWhere('emails', '[]');
        }

        $contacts = $query->orderBy('first_name')->paginate(50)->withQueryString();
        $groups = ContactGroup::where('user_id', $user->id)->withCount('contacts')->get();

        return view('contacts.index', compact('contacts', 'groups', 'search', 'groupId', 'filter'));
    }

    public function show(Contact $contact)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $contact->load('groups');
        return view('contacts.show', compact('contact'));
    }

    public function create()
    {
        $groups = ContactGroup::where('user_id', Auth::id())->get();
        return view('contacts.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'emails' => ['nullable', 'array'],
            'emails.*.email' => ['required', 'email'],
            'emails.*.type' => ['nullable', 'string'],
            'phones' => ['nullable', 'array'],
            'phones.*.number' => ['required', 'string'],
            'phones.*.type' => ['nullable', 'string'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:contact_groups,id'],
        ]);

        // Mark first email as primary
        if (!empty($validated['emails'])) {
            $validated['emails'][0]['is_primary'] = true;
        }

        $contact = Contact::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        if (!empty($validated['groups'])) {
            $contact->groups()->sync($validated['groups']);
        }

        // Publish contact created event for search indexing
        $primaryEmail = !empty($validated['emails']) ? $validated['emails'][0]['email'] : '';
        $primaryPhone = !empty($validated['phones']) ? $validated['phones'][0]['number'] : null;

        app(\App\Services\YgAccountEventPublisher::class)->publishContactCreated(
            $contact->id,
            Auth::id(),
            $contact->first_name,
            $contact->last_name ?? '',
            $primaryEmail,
            $primaryPhone
        );

        // Trigger AI enrichment in background
        if ($primaryEmail) {
            EnrichContactJob::dispatch(
                $contact->id,
                $primaryEmail,
                $contact->full_name
            )->onQueue('ai-processing');
        }

        return redirect()->route('contacts.show', $contact)->with('success', 'Contact saved.');
    }

    public function edit(Contact $contact)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $groups = ContactGroup::where('user_id', Auth::id())->get();
        $contact->load('groups');
        return view('contacts.edit', compact('contact', 'groups'));
    }

    public function update(Request $request, Contact $contact)
    {
        abort_unless($contact->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'emails' => ['nullable', 'array'],
            'emails.*.email' => ['required', 'email'],
            'emails.*.type' => ['nullable', 'string'],
            'phones' => ['nullable', 'array'],
            'phones.*.number' => ['required', 'string'],
            'phones.*.type' => ['nullable', 'string'],
            'groups' => ['nullable', 'array'],
        ]);

        if (!empty($validated['emails'])) {
            $validated['emails'][0]['is_primary'] = true;
        }

        $contact->update($validated);

        if (!empty($validated['groups'])) {
            $contact->groups()->sync($validated['groups']);
        }

        // Publish contact updated event
        $primaryEmail = !empty($validated['emails']) ? $validated['emails'][0]['email'] : '';
        app(\App\Services\YgAccountEventPublisher::class)->publishContactUpdated(
            $contact->id,
            Auth::id(),
            $primaryEmail
        );

        return redirect()->route('contacts.show', $contact)->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $contact->delete();
        return redirect()->route('contacts.index')->with('success', 'Contact deleted.');
    }

    public function toggleStar(Contact $contact)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $contact->update(['is_starred' => !$contact->is_starred]);
        return redirect()->back();
    }

    // ── Groups ──────────────────────────────────────────────────────────────────

    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        ContactGroup::create([...$validated, 'user_id' => Auth::id()]);

        return redirect()->back()->with('success', 'Group created.');
    }

    public function deleteGroup(ContactGroup $group)
    {
        abort_unless($group->user_id === Auth::id(), 403);
        $group->delete();
        return redirect()->route('contacts.index')->with('success', 'Group deleted.');
    }

    // ── Import / Export ─────────────────────────────────────────────────────────

    public function exportVCard()
    {
        $contacts = Contact::forUser(Auth::id())->get()->map(fn($c) => [
            'name' => $c->full_name,
            'email' => $c->primary_email,
            'phone' => $c->phones[0]['number'] ?? '',
            'organization' => $c->company,
            'job_title' => $c->job_title,
            'website' => '',
        ])->toArray();

        $vcard = ContactImportExportService::exportVCard($contacts);

        return response($vcard, 200, [
            'Content-Type' => 'text/vcard',
            'Content-Disposition' => 'attachment; filename="yg-contacts.vcf"',
        ]);
    }

    public function exportCsv()
    {
        $contacts = Contact::forUser(Auth::id())->get()->map(fn($c) => [
            'name' => $c->full_name,
            'email' => $c->primary_email,
            'phone' => $c->phones[0]['number'] ?? '',
            'organization' => $c->company,
            'job_title' => $c->job_title,
            'website' => '',
            'notes' => $c->notes,
        ])->toArray();

        $csv = ContactImportExportService::exportCsv($contacts);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="yg-contacts.csv"',
        ]);
    }

    public function importContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,vcf|max:2048',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $extension = $request->file('file')->getClientOriginalExtension();

        if ($extension === 'vcf') {
            $parsed = ContactImportExportService::parseVCard($content);
        } else {
            $parsed = ContactImportExportService::parseCsv($content);
        }

        foreach ($parsed as $data) {
            Contact::create([
                'user_id' => Auth::id(),
                'first_name' => $data['name'],
                'company' => $data['organization'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'emails' => $data['email'] ? [['email' => $data['email'], 'type' => 'work', 'is_primary' => true]] : null,
                'phones' => $data['phone'] ? [['number' => $data['phone'], 'type' => 'mobile']] : null,
            ]);
        }

        return redirect()->route('contacts.index')->with('success', count($parsed) . ' contacts imported successfully.');
    }

    // ── API: search (for Mail/Meet autocomplete) ────────────────────────────────

    public function apiSearch(Request $request)
    {
        $q = $request->query('q', '');

        $contacts = Contact::forUser(Auth::id())
            ->search($q)
            ->select(['id', 'first_name', 'last_name', 'emails', 'avatar'])
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
                'email' => $c->primary_email,
                'initials' => $c->initials,
            ]);

        return response()->json($contacts);
    }
}
