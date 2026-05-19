<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class StatusController extends Controller
{
    /**
     * Ecosystem services to monitor — label → URL.
     */
    private const SERVICES = [
        'Home'       => 'https://ygxone.com',
        'Mail'       => 'https://mail.ygxone.com',
        'Xcel'       => 'https://xcel.ygxone.com',
        'DocX'       => 'https://docs.ygxone.com',
        'Drive'      => 'https://drive.ygxone.com',
        'Notes'      => 'https://notes.ygxone.com',
        'Chat'       => 'https://chat.ygxone.com',
        'Meet'       => 'https://meet.ygxone.com',
        'Calendar'   => 'https://calendar.ygxone.com',
        'Contacts'   => 'https://contacts.ygxone.com',
        'Developer'  => 'https://developer.ygxone.com',
        'Pay'        => 'https://pay.ygxone.com',
        'Account'    => 'https://account.ygxone.com',
        'Master'     => 'https://master.ygxone.com',
    ];

    /**
     * HTTP request timeout in seconds.
     */
    private const TIMEOUT = 8;

    /**
     * Cache TTL for healthy services (seconds).
     */
    private const CACHE_TTL = 120;

    /**
     * Show the system status page.
     */
    public function index()
    {
        $services = $this->checkAllServices();

        $stats = [
            'operational' => collect($services)->where('status', 'operational')->count(),
            'degraded'    => collect($services)->where('status', 'degraded')->count(),
            'down'        => collect($services)->where('status', 'down')->count(),
            'total'       => count($services),
        ];

        $uptime = $stats['total'] > 0
            ? round(($stats['operational'] / $stats['total']) * 100, 1)
            : 0;

        return view('status.index', [
            'services' => $services,
            'stats'    => $stats,
            'uptime'   => $uptime,
            'lastCheck' => now()->toIso8601String(),
        ]);
    }

    /**
     * Ping all ecosystem services and return their health status.
     */
    private function checkAllServices(): array
    {
        $client = new Client([
            'timeout'         => self::TIMEOUT,
            'connect_timeout' => self::TIMEOUT,
            'http_errors'     => false,
            'allow_redirects' => ['max' => 2],
            'verify'          => false, // skip SSL verification for internal services
            'headers'         => [
                'User-Agent' => 'YG Support HealthChecker/1.0',
            ],
        ]);

        $results = [];

        foreach (self::SERVICES as $name => $url) {
            $cacheKey = 'sys_status_' . strtolower(str_replace(' ', '_', $name));

            $results[$name] = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($client, $name, $url) {
                $start = microtime(true);

                try {
                    $response = $client->get($url);
                    $code = $response->getStatusCode();
                    $latency = round((microtime(true) - $start) * 1000); // ms

                    if ($code >= 200 && $code < 400) {
                        return [
                            'name'     => $name,
                            'url'      => $url,
                            'status'   => 'operational',
                            'code'     => $code,
                            'latency'  => $latency,
                            'icon'     => '✅',
                            'color'    => '#34d399',
                            'message'  => "HTTP {$code} — {$latency}ms",
                        ];
                    }

                    if ($code >= 400 && $code < 500) {
                        return [
                            'name'     => $name,
                            'url'      => $url,
                            'status'   => 'degraded',
                            'code'     => $code,
                            'latency'  => $latency,
                            'icon'     => '⚠️',
                            'color'    => '#facc15',
                            'message'  => "HTTP {$code} — {$latency}ms",
                        ];
                    }

                    return [
                        'name'     => $name,
                        'url'      => $url,
                        'status'   => 'down',
                        'code'     => $code,
                        'latency'  => $latency,
                        'icon'     => '❌',
                        'color'    => '#fb7185',
                        'message'  => "HTTP {$code} — {$latency}ms",
                    ];
                } catch (ConnectException $e) {
                    return [
                        'name'     => $name,
                        'url'      => $url,
                        'status'   => 'down',
                        'code'     => 0,
                        'latency'  => round((microtime(true) - $start) * 1000),
                        'icon'     => '❌',
                        'color'    => '#fb7185',
                        'message'  => 'Connection failed',
                    ];
                } catch (TooManyRedirectsException $e) {
                    return [
                        'name'     => $name,
                        'url'      => $url,
                        'status'   => 'degraded',
                        'code'     => 0,
                        'latency'  => round((microtime(true) - $start) * 1000),
                        'icon'     => '⚠️',
                        'color'    => '#facc15',
                        'message'  => 'Too many redirects',
                    ];
                } catch (RequestException $e) {
                    return [
                        'name'     => $name,
                        'url'      => $url,
                        'status'   => 'degraded',
                        'code'     => 0,
                        'latency'  => round((microtime(true) - $start) * 1000),
                        'icon'     => '⚠️',
                        'color'    => '#facc15',
                        'message'  => 'Request error: ' . class_basename($e),
                    ];
                } catch (\Throwable $e) {
                    return [
                        'name'     => $name,
                        'url'      => $url,
                        'status'   => 'down',
                        'code'     => 0,
                        'latency'  => 0,
                        'icon'     => '❌',
                        'color'    => '#fb7185',
                        'message'  => 'Error: ' . $e->getMessage(),
                    ];
                }
            });
        }

        return $results;
    }

    /**
     * Force-refresh all status checks (clear cache).
     */
    public function refresh()
    {
        foreach (array_keys(self::SERVICES) as $name) {
            $cacheKey = 'sys_status_' . strtolower(str_replace(' ', '_', $name));
            Cache::forget($cacheKey);
        }

        return redirect()->route('status.index')
            ->with('success', 'Status cache cleared. Results are fresh.');
    }
}
