<?php

namespace App\Services;

class ChatAdService
{
    /**
     * Get a contextually relevant YG Ad for a public channel
     * Ads are shown every N messages to monetize public spaces
     */
    public static function getChannelAd(int $spaceId, int $messageCount): ?array
    {
        // Show an ad every 20 messages in public channels
        if ($messageCount % 20 !== 0) {
            return null;
        }

        // In production, this would query AdAuctionService for the winning bid
        $ads = [
            [
                'title'   => 'YG Xcel: Sovereign Financial Intelligence',
                'body'    => 'Pull live YG Pay & AdSense data directly into your spreadsheets.',
                'cta'     => 'Open YG Xcel',
                'link'    => '/xcel',
                'badge'   => 'From YG Ads',
            ],
            [
                'title'   => 'YG Drive: Your Private Cloud',
                'body'    => 'Store and share files across all 16 nodes with 100% data sovereignty.',
                'cta'     => 'Explore Drive',
                'link'    => '/drive',
                'badge'   => 'From YG Ads',
            ],
            [
                'title'   => 'YG DocX: Write & Earn',
                'body'    => 'Publish directly to the Imperial Journal and earn ad revenue from your words.',
                'cta'     => 'Start Writing',
                'link'    => '/docs',
                'badge'   => 'From YG Ads',
            ],
        ];

        return $ads[random_int(0, count($ads) - 1)];
    }
}
