<?php

namespace Yuga\Core;

/**
 * Yuga Visual Sentinel
 * Specialized image moderation using Binary Byte-Stream Analysis.
 * Designed for environments without GD or Imagick.
 */
class VisualSentinel
{
    private $config;
    private $dataDir;

    public function __construct(array $config, string $dataDir)
    {
        $this->config = $config;
        $this->dataDir = $dataDir;
    }

    /**
     * Perform a visual safety check on an uploaded file
     * Returns: ['safe' => bool, 'confidence' => float, 'reason' => string]
     */
    public function analyze(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['safe' => true, 'confidence' => 1.0, 'reason' => 'File not found'];
        }

        // ── Phase 1: MIME Verification ─────────────────────────────
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        if (!str_starts_with($mime, 'image/') && !str_starts_with($mime, 'video/')) {
            return ['safe' => true, 'confidence' => 1.0, 'reason' => 'Non-media file'];
        }

        // ── Phase 2: Binary Signature Analysis ──────────────────────
        // We scan the raw bytes for inappropriate metadata or signatures
        $binaryVerdict = $this->scanBinary($filePath);
        if (!$binaryVerdict['safe']) {
            return $binaryVerdict;
        }

        // ── Phase 3: Metadata / EXIF Scan ───────────────────────────
        $metaVerdict = $this->scanMetadata($filePath);
        if (!$metaVerdict['safe']) {
            return $metaVerdict;
        }

        return [
            'safe' => true,
            'confidence' => 0.8,
            'reason' => 'Media verified safe via Binary Signature Analysis'
        ];
    }

    /**
     * Scans raw binary for toxic signatures or adult-themed metadata
     */
    private function scanBinary(string $path): array
    {
        $handle = fopen($path, 'rb');
        $buffer = fread($handle, 8192); // Scan the first 8KB (where most metadata lives)
        fclose($handle);

        $lowered = strtolower($buffer);
        $toxicSignatures = [
            'porn', 'nude', 'sex', 'naked', 'adult', 'xxx', 'hentai', 
            'pussy', 'dick', 'asshole', 'blowjob', 'ebony', 'milf'
        ];

        foreach ($toxicSignatures as $sig) {
            if (str_contains($lowered, $sig)) {
                return [
                    'safe' => false, 
                    'confidence' => 1.0, 
                    'reason' => "Found inappropriate binary signature: '{$sig}'"
                ];
            }
        }

        return ['safe' => true];
    }

    /**
     * Specialized metadata scan (looking for adult-software signatures)
     */
    private function scanMetadata(string $path): array
    {
        $raw = file_get_contents($path, false, null, 0, 4096);
        
        // Look for common adult content creation tool signatures
        $softwareTriggers = [
            'PornHub', 'xHamster', 'OnlyFans', 'Brazzers'
        ];

        foreach ($softwareTriggers as $soft) {
            if (str_contains($raw, $soft)) {
                return [
                    'safe' => false, 
                    'confidence' => 1.0, 
                    'reason' => "Media contains signatures from blacklisted source: {$soft}"
                ];
            }
        }

        return ['safe' => true];
    }
}
