<?php

namespace App\Http\Controllers;

use App\Models\CollectForm;
use App\Models\CollectProject;
use App\Models\CollectSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FormBuilderController extends Controller
{
    /** List all forms for the authenticated user */
    public function index()
    {
        $forms = CollectForm::where('user_id', Auth::id())
            ->withCount('submissions')
            ->orderByDesc('updated_at')
            ->get();

        return view('collect.index', compact('forms'));
    }

    /** Show the form builder UI */
    public function create()
    {
        return view('collect.builder');
    }

    /** Store a new form */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'fields'      => ['required', 'array'],
            'is_public'   => ['boolean'],
            'show_branding' => ['boolean'],
        ]);

        $form = CollectForm::create([
            ...$validated,
            'user_id' => Auth::id(),
            'slug'    => Str::slug($validated['title']) . '-' . Str::random(6),
            'fields'  => $validated['fields'],
        ]);

        return redirect()->route('collect.show', $form->slug)
            ->with('success', 'Form created successfully.');
    }

    /** Show public form for submissions */
    public function showPublic(string $slug)
    {
        $form = CollectForm::where('slug', $slug)->firstOrFail();
        return view('collect.public', compact('form'));
    }

    /** Show form results / responses */
    public function results(CollectForm $form)
    {
        abort_unless($form->user_id === Auth::id(), 403);

        $submissions = CollectSubmission::where('form_id', $form->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('collect.results', compact('form', 'submissions'));
    }

    /** Handle public form submission */
    public function submit(Request $request, string $slug)
    {
        $form = CollectForm::where('slug', $slug)->firstOrFail();

        CollectSubmission::create([
            'form_id'    => $form->id,
            'data'       => $request->except(['_token']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Your response has been submitted.');
    }

    /** Delete a form */
    public function destroy(CollectForm $form)
    {
        abort_unless($form->user_id === Auth::id(), 403);
        $form->delete();
        return redirect()->route('collect.index')->with('success', 'Form deleted.');
    }

    /** Export responses as CSV */
    public function exportCsv(CollectForm $form)
    {
        abort_unless($form->user_id === Auth::id(), 403);

        $submissions = CollectSubmission::where('form_id', $form->id)->get();
        
        if ($submissions->isEmpty()) {
            return back()->with('error', 'No responses to export.');
        }

        $headers = array_keys($submissions->first()->data ?? []);
        $csv     = implode(',', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";

        foreach ($submissions as $submission) {
            $row = array_map(fn($h) => '"' . str_replace('"', '""', $submission->data[$h] ?? '') . '"', $headers);
            $csv .= implode(',', $row) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . Str::slug($form->title) . '-responses.csv"',
        ]);
    }
}
