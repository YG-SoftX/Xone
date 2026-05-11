<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mail;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->q;
        if (!$q) return response()->json(['results' => []]);

        $mails = Mail::where('user_id', auth()->id())
            ->where(function($query) use ($q) {
                $query->where('subject', 'like', "%{$q}%")
                      ->orWhere('body', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(function($mail) {
                return [
                    'type'  => 'Mail',
                    'title' => $mail->subject,
                    'desc'  => substr(strip_tags($mail->body), 0, 100) . '...',
                    'url'   => 'https://mail.ygxone.com/view/' . $mail->id,
                    'icon'  => 'fa-envelope'
                ];
            });

        return response()->json(['results' => $mails]);
    }
}
