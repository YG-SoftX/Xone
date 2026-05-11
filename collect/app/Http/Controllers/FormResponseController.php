<?php

namespace App\Http\Controllers;

use App\Models\CollectForm;
use App\Models\CollectSubmission;
use App\Services\YgAccountEventPublisher;
use Illuminate\Http\Request;

class FormResponseController extends Controller
{
    protected $eventPublisher;

    public function __construct(YgAccountEventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    public function show($form_id)
    {
        $form = CollectForm::with('project')->findOrFail($form_id);

        if ($form->status !== 'published') {
            abort(403, 'This form is not currently accepting responses.');
        }

        return view('forms.public.show', compact('form'));
    }

    public function submit(Request $request, $form_id)
    {
        $form = CollectForm::findOrFail($form_id);

        if ($form->status !== 'published') {
            abort(403, 'Submission failed: Form is not published.');
        }

        // Basic submission
        $data = $request->except(['_token']);
        $isEncrypted = $request->has('e2e_blob');

        if ($isEncrypted) {
            $data = ['e2e_blob' => $request->e2e_blob];
        }

        $submission = CollectSubmission::create([
            'form_id' => $form->id,
            'data' => $data,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'submitted_at' => now(),
                'is_encrypted' => $isEncrypted,
            ],
            'user_id' => auth()->id(),
        ]);

        // Publish event for cross-module sync (search indexing, notifications, etc.)
        $this->eventPublisher->publishFormSubmitted(
            $submission->id,
            $form->id,
            auth()->id() ?? 0,
            $form->title,
            $isEncrypted
        );

        return back()->with('success', 'Thank you! Your response has been recorded.');
    }

    public function verify($submission_id)
    {
        $submission = CollectSubmission::findOrFail($submission_id);
        return view('forms.public.verify', compact('submission'));
    }
}
