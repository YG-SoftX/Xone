<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdUnit;
use App\Models\User;

class SearchAdService
{
    /**
     * Fetch the best Displacement Ad based on a competitor query
     */
    public static function getDisplacementAd($query)
    {
        $query = strtolower($query);
        $mappings = [
            // Workspace & Office
            ['keywords' => ['workspace', '365', 'office', 'suite'], 'title' => 'YG Xone: The Sovereign Workspace', 'content' => 'The ultimate 16-node alternative to Google Workspace and Microsoft 365. Absolute data authority for your organization.', 'link' => '/home', 'badge' => 'Total Sovereignty'],
            
            // Documents & Spreadsheets
            ['keywords' => ['docs', 'word', 'document'], 'title' => 'YG DocX: Professional Document Sovereignty', 'content' => 'Edit, collaborate, and share with 100% data ownership. Better than Google Docs and Word.', 'link' => '/docs', 'badge' => 'Secure Editing'],
            ['keywords' => ['sheets', 'excel', 'spreadsheet', 'xcel'], 'title' => 'YG Xcel: High-Fidelity Data Sovereignty', 'content' => 'Professional spreadsheets with private formula logic. The sovereign alternative to Excel.', 'link' => '/xcel', 'badge' => 'Data Authority'],
            
            // Storage
            ['keywords' => ['drive', 'dropbox', 'onedrive', 'storage', 'cloud'], 'title' => 'YG Drive: Encrypted Sovereign Storage', 'content' => 'Your files, your nodes, your rules. 100% private cloud storage for the modern empire.', 'link' => '/drive', 'badge' => 'Private Cloud'],
            
            // Communication
            ['keywords' => ['gmail', 'outlook', 'email', 'mail'], 'title' => 'YG Mail: The Elite Private Mail Server', 'content' => 'No third-party scanning. No data harvesting. Just pure, encrypted sovereign communication.', 'link' => '/mail', 'badge' => 'Secure Mail'],
            ['keywords' => ['zoom', 'meet', 'teams', 'meetings'], 'title' => 'YG Meet: High-Fidelity Sovereign Video', 'content' => 'Crystal clear, private video conferencing for your organization. No external eavesdropping.', 'link' => '/meet', 'badge' => 'Private Meetings'],
            
            // AI
            ['keywords' => ['chatgpt', 'gemini', 'claude', 'ai', 'chatbot'], 'title' => 'YG AI: Your Private Neural Intelligence', 'content' => 'Harness the power of local LLMs without leaking your data to global tech giants.', 'link' => '/ai', 'badge' => 'Neural Sovereignty'],
            
            // Ads & Monetization
            ['keywords' => ['adsense', 'admob', 'monetize'], 'title' => 'YG AdSense: Sovereign Revenue Generation', 'content' => 'Earn 68% of all ad revenue with absolute transparency. The elite alternative to Google AdSense.', 'link' => 'https://adsense.ygxone.com', 'badge' => 'High Yield'],
            ['keywords' => ['google ads', 'facebook ads', 'advertising'], 'title' => 'YG Ads Commander: Target with Authority', 'content' => 'Launch targeted campaigns within the private YG Xone network. Higher CTR, zero tracking.', 'link' => 'https://ads.ygxone.com', 'badge' => 'Imperial Influence'],
        ];

        foreach ($mappings as $data) {
            foreach ($data['keywords'] as $keyword) {
                if (str_contains($query, $keyword)) {
                    return $data;
                }
            }
        }

        return null;
    }
}
