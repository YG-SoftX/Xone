<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates with filters.
     */
    public function index(Request $request)
    {
        try {
            $query = Template::query();

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            // Filter by public or system templates
            if ($request->boolean('public')) {
                $query->where('is_public', true);
            }

            if ($request->boolean('system')) {
                $query->where('is_system', true);
            }

            // Always include user's own templates
            if ($request->user()) {
                $query->orWhere('user_id', $request->user()->id);
            }

            $templates = $query->with(['user'])->latest()->paginate(20)->withQueryString();

            return Inertia::render('Templates/Index', [
                'templates' => $templates,
                'filters' => $request->only(['category', 'public', 'system']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list templates', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to load templates. Please try again.');
        }
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'content' => 'nullable|string',
                'content_json' => 'nullable|array',
                'category' => 'required|string|max:100',
                'is_public' => 'boolean',
            ]);

            $validated['user_id'] = $request->user()->id;
            $validated['is_system'] = false;

            Template::create($validated);

            return redirect()->back()->with('success', 'Template saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create template', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to create template. Please try again.');
        }
    }

    /**
     * Apply a template to a document.
     */
    public function apply(Request $request, Template $template)
    {
        try {
            $validated = $request->validate([
                'document_id' => 'required|exists:documents,id',
            ]);

            $document = Document::findOrFail($validated['document_id']);

            // Apply template content to document
            $document->content = $template->content;
            $document->content_json = $template->content_json;
            $document->save();

            // Create a version for the change
            $document->createVersion($request->user(), 'Applied template: ' . $template->name);

            return redirect()->route('documents.show', $document)
                ->with('success', 'Template applied to document successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to apply template', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);

            return redirect()->back()->with('error', 'Failed to apply template. Please try again.');
        }
    }

    /**
     * Remove the specified template.
     */
    public function delete(Template $template)
    {
        try {
            $template->delete();

            return redirect()->back()->with('success', 'Template deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete template', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete template. Please try again.');
        }
    }
}
