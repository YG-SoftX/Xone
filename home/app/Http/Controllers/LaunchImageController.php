<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LaunchImageController
{
    private string $cacheDir;
    private string $defaultBg = '#0f172a';
    private string $defaultAccent = '#2563eb';

    public function __construct()
    {
        $this->cacheDir = base_path('storage/app/agent-cache/launch-images');
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function serve(string $size): Response
    {
        $dim = $this->parseSize($size);
        if (!$dim) {
            return response('Unknown size: ' . $size, 404);
        }
        return $this->generateOrServe($dim[0], $dim[1]);
    }

    public function generateAll(): Response
    {
        $labels = [
            ['750x1334', 750, 1334], ['1125x2436', 1125, 2436],
            ['828x1792', 828, 1792], ['1242x2688', 1242, 2688],
            ['1170x2532', 1170, 2532], ['1179x2556', 1179, 2556],
            ['1536x2048', 1536, 2048], ['1668x2388', 1668, 2388],
            ['2048x2732', 2048, 2732],
        ];
        $generated = [];
        $failed = [];
        foreach ($labels as [$label, $w, $h]) {
            try {
                $this->generateOrServe($w, $h);
                $generated[] = $label;
            } catch (\Exception $e) {
                $failed[] = $label . ': ' . $e->getMessage();
            }
        }
        return response()->json(['generated' => $generated, 'failed' => $failed]);
    }

    private function generateOrServe(int $w, int $h): Response
    {
        $cacheKey = md5($w . 'x' . $h);
        $path = $this->cacheDir . '/' . $cacheKey . '.png';
        if (file_exists($path) && (time() - filemtime($path)) < 86400) {
            return $this->servePng($path);
        }
        $custom = $this->getCustomUrl();
        if (!empty($custom)) {
            $ck = md5($custom . '_' . $w . 'x' . $h);
            $cp = $this->cacheDir . '/' . $ck . '.png';
            if (!file_exists($cp)) {
                $this->fetchAndResize($custom, $w, $h, $cp);
            }
            if (file_exists($cp)) {
                return $this->servePng($cp);
            }
        }
        return $this->generateImage($w, $h, $path);
    }

    private function generateImage(int $w, int $h, string $savePath): Response
    {
        if (!function_exists('imagecreatetruecolor')) {
            return response('GD not available', 500);
        }
        $cfg = $this->getConfig();
        $img = imagecreatetruecolor($w, $h);
        if (!$img) {
            return response('Image create failed', 500);
        }
        $bg  = $this->color($img, $cfg['bg']);
        $acc = $this->color($img, $cfg['accent']);
        $wht = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);
        $this->glow($img, $w, $h, $acc);
        $size = min($w, $h) * 0.22;
        $bx = ($w - $size) / 2;
        $by = ($h - $size) / 2;
        $r = $size * 0.18;
        $this->rrect($img, $bx, $by, $size, $size, $r, $acc);
        $init = strtoupper(substr($cfg['title'], 0, 2));
        imagestring($img, 5, (int)(($w - strlen($init) * 10) / 2), (int)($by + ($size - 16) / 2), $init, $wht);
        $bh = max(4, (int)($h * 0.006));
        imagefilledrectangle($img, 0, $h - $bh, $w, $h, $acc);
        imagepng($img, $savePath, 8);
        imagedestroy($img);
        return $this->servePng($savePath);
    }

    private function glow($img, int $w, int $h, int $rgb): void
    {
        imagealphablending($img, true);
        $cx = $w / 2;
        $cy = $h / 2;
        $maxR = min($w, $h) * 0.48;
        for ($r = $maxR; $r > 0; $r -= max(1, (int)($maxR / 12))) {
            $alpha = (int)(40 * (1 - $r / $maxR));
            $c = imagecolorallocatealpha($img, ($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF, 127 - min(50, $alpha));
            imagefilledellipse($img, (int)$cx, (int)$cy, (int)($r * 2), (int)($r * 2), $c);
        }
    }

    private function rrect($img, float $x, float $y, float $w, float $h, float $r, int $c): void
    {
        $x1 = (int)$x; $y1 = (int)$y;
        $x2 = (int)($x + $w); $y2 = (int)($y + $h);
        $r2 = (int)$r;
        imagefilledrectangle($img, $x1 + $r2, $y1,       $x2 - $r2, $y2,       $c);
        imagefilledrectangle($img, $x1,       $y1 + $r2, $x2,       $y2 - $r2, $c);
        imagefilledellipse($img, $x1 + $r2,       $y1 + $r2,       $r2 * 2, $r2 * 2, $c);
        imagefilledellipse($img, $x2 - $r2,       $y1 + $r2,       $r2 * 2, $r2 * 2, $c);
        imagefilledellipse($img, $x1 + $r2,       $y2 - $r2,       $r2 * 2, $r2 * 2, $c);
        imagefilledellipse($img, $x2 - $r2,       $y2 - $r2,       $r2 * 2, $r2 * 2, $c);
    }

    private function parseSize(string $s): ?array
    {
        $m = [
            '750x1334' => [750, 1334], '1125x2436' => [1125, 2436],
            '828x1792' => [828, 1792], '1242x2688' => [1242, 2688],
            '1170x2532' => [1170, 2532], '1179x2556' => [1179, 2556],
            '1536x2048' => [1536, 2048], '1668x2388' => [1668, 2388],
            '2048x2732' => [2048, 2732],
        ];
        return $m[$s] ?? null;
    }

    private function color($img, string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return imagecolorallocate($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    private function servePng(string $p): Response
    {
        return response(file_get_contents($p))
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400, stale-while-revalidate=604800');
    }

    private function getConfig(): array
    {
        try {
            $cfg = app(\App\Services\BrowserConfigService::class);
            return [
                'bg'     => $cfg->get('splash_bg_color', $this->defaultBg),
                'accent' => $cfg->get('splash_spinner_color', $this->defaultAccent),
                'title'  => $cfg->get('splash_title', 'YGXONE'),
            ];
        } catch (\Exception $e) {
            return ['bg' => $this->defaultBg, 'accent' => $this->defaultAccent, 'title' => 'YGXONE'];
        }
    }

    private function getCustomUrl(): string
    {
        try {
            return app(\App\Services\BrowserConfigService::class)->get('splash_launch_image_url', '');
        } catch (\Exception $e) {
            return '';
        }
    }    private function fetchAndResize(string $url, int $tw, int $th, string $savePath): void
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (empty($data) || $httpCode >= 400) {
            Log::warning("LaunchImageController: Failed to fetch custom image (HTTP {$httpCode}): {$url}");
            return;
        }

        $src = @imagecreatefromstring($data);
        if (!$src) {
            Log::warning("LaunchImageController: Could not decode custom image (not a valid image): {$url}");
            return;
        }

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, imagesx($src), imagesy($src));
        imagepng($dst, $savePath, 8);
        imagedestroy($src);
        imagedestroy($dst);
    }
}
