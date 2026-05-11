<?php

namespace App\Http\Controllers;

use App\Models\KYC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KYCController extends Controller
{
    /**
     * Allowed MIME types for KYC documents.
     */
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    /**
     * Maximum file size in bytes (5MB).
     */
    const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Show KYC Verification page.
     */
    public function index(Request $request)
    {
        $kyc = KYC::where('user_id', $request->user()->id)->latest()->first();

        return Inertia::render('KYC', [
            'kycStatus' => $kyc ? $kyc->status : null,
            'kycData' => $kyc,
        ]);
    }

    /**
     * Store KYC Document.
     */
    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|in:passport,drivers_license,national_id,voter_id',
            'document_number' => 'required|string|max:50',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $file = $request->file('document');

        // Validate MIME type (not just extension)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return back()->withErrors([
                'document' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.',
            ]);
        }

        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return back()->withErrors([
                'document' => 'File size exceeds the maximum limit of 5MB.',
            ]);
        }

        // Generate a safe file name to prevent path traversal
        $safeFileName = 'kyc_' . $request->user()->id . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store on private disk - NOT publicly accessible
        $path = $file->storeAs('kyc_documents', $safeFileName);

        KYC::create([
            'user_id' => $request->user()->id,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'document_path' => $path,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'KYC application submitted for review.');
    }
}
