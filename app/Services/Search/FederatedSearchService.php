<?php

namespace App\Services\Search;

use App\Services\EventHub\TrustedModuleRegistry;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final readonly class FederatedSearchService
{
    public function __construct(private TrustedModuleRegistry $modules)
    {
    }

    /**
     * @param  array<int, string>  $allowedModules  Module keys the user may access.
     *                                               Empty array = no restriction (admin).
     * @return array<string, mixed>
     */
    public function search(string $query, int $limit = 8, array $allowedModules = []): array
    {
        $query = trim($query);
        $limit = max(1, min($limit, 25));

        if ($query === '') {
            return ['query' => $query, 'results' => [], 'modules' => []];
        }

        // Cache key includes the allowed-module set so different roles never
        // share cached results with each other.
        $moduleScope = empty($allowedModules) ? 'all' : implode(',', $allowedModules);
        $cacheKey = 'deoris:federated-search:'.sha1($query.'|'.$limit.'|'.$moduleScope);

        return Cache::remember($cacheKey, config('deoris_events.search_cache_seconds', 60), function () use ($query, $limit, $allowedModules): array {
            $modules = collect($this->modules->modules())
                ->filter(fn (array $module) => filled($module['url'] ?? null) && filled($module['search_token'] ?? null))
                // Only query modules the user is allowed to see.
                ->when(! empty($allowedModules), fn ($col) => $col->filter(
                    fn (array $module) => in_array($module['key'] ?? '', $allowedModules, true)
                ))
                ->values();

            $responses = Http::pool(function (Pool $pool) use ($modules, $query, $limit): array {
                return $modules->map(function (array $module) use ($pool, $query, $limit) {
                    $url = rtrim((string) $module['url'], '/').'/api/search';

                    return $pool
                        ->as((string) $module['key'])
                        ->withToken((string) ($module['search_token'] ?? ''))
                        ->acceptJson()
                        ->timeout(4)
                        ->get($url, ['q' => $query, 'limit' => $limit]);
                })->all();
            });

            $results = [];
            $moduleStatuses = [];

            foreach ($modules as $module) {
                $key = (string) $module['key'];
                $response = $responses[$key] ?? null;

                if (! $response || $response->failed()) {
                    $moduleStatuses[$key] = [
                        'ok' => false,
                        'status' => $response?->status(),
                    ];
                    continue;
                }

                $payload = $response->json();
                $items = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);

                foreach (array_slice($items, 0, $limit) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $results[] = [
                        'module' => $key,
                        'module_label' => $this->moduleLabel($key),
                        'type' => (string) ($item['type'] ?? 'record'),
                        'title' => (string) ($item['title'] ?? $item['name'] ?? 'Untitled result'),
                        'subtitle' => (string) ($item['subtitle'] ?? $item['description'] ?? ''),
                        'url' => (string) ($item['url'] ?? $module['url']),
                        'score' => (float) ($item['score'] ?? $this->score($query, $item)),
                        'meta' => $item['meta'] ?? [],
                    ];
                }

                $moduleStatuses[$key] = ['ok' => true, 'status' => $response->status()];
            }

            usort($results, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

            return [
                'query' => $query,
                'results' => array_slice($results, 0, $limit * 3),
                'modules' => $moduleStatuses,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function score(string $query, array $item): float
    {
        $haystack = Str::lower(($item['title'] ?? '').' '.($item['subtitle'] ?? '').' '.($item['description'] ?? ''));
        $needle = Str::lower($query);

        return Str::contains($haystack, $needle) ? 1.0 : 0.3;
    }

    private function moduleLabel(string $key): string
    {
        $module = collect($this->modules->modules())
            ->first(fn (array $module): bool => ($module['key'] ?? null) === $key);

        return (string) ($module['label'] ?? Str::headline($key));
    }
}
