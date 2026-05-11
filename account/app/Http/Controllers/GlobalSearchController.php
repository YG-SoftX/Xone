<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlobalSearchController extends Controller
{
    /**
     * Aggregate search results from all ecosystem services.
     */
    public function aggregate(Request $request)
    {
        $q = $request->q;
        if (!$q) return response()->json(['results' => []]);

        $services = [
            'Mail'      => 'https://mail.ygxone.com/api/search',
            'Pay'       => 'https://pay.ygxone.com/api/search',
            'Play Store'=> 'https://play.ygxone.com/api/search',
            'AI'        => 'https://ai.ygxone.com/api/index.php?action=search',
            'Web'       => 'https://ygxone.com/search.php?action=api',
            'News'      => 'https://news.ygxone.com/api/index.php?action=search',
        ];

        $allResults = [];

        foreach ($services as $name => $url) {
            try {
                // In a real app, use auth tokens for each service
                $response = Http::timeout(2)->get($url, ['q' => $q]);
                if ($response->successful()) {
                    $results = $response->json()['results'] ?? [];
                    $allResults = array_merge($allResults, $results);
                }
            } catch (\Exception $e) {
                Log::warning("Search failed for {$name}: " . $e->getMessage());
            }
        }

        return response()->json(['results' => $allResults]);
    }
}
