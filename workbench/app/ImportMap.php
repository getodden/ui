<?php

declare(strict_types=1);

namespace Workbench\App;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ImportMap
{
    private const URL_PREFIX = '/__workbench/';

    /**
     * @return array<string, string>
     */
    public static function imports(): array
    {
        $imports = [];
        $queue = self::rootPackages();
        $seen = [];

        while (($name = array_shift($queue)) !== null) {
            if (isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $manifest = self::manifest($name);

            if ($manifest === null) {
                continue;
            }

            foreach (self::entries($manifest) as $subpath => $file) {
                $imports[$name.$subpath] = self::URL_PREFIX.'npm/'.$name.'/'.ltrim($file, './');
            }

            foreach (['dependencies', 'peerDependencies'] as $field) {
                $dependencies = $manifest[$field] ?? null;

                if (is_array($dependencies)) {
                    array_push($queue, ...array_map('strval', array_keys($dependencies)));
                }
            }
        }

        ksort($imports);

        return $imports;
    }

    public static function response(string $root, string $path): Response
    {
        $base = match ($root) {
            'js' => realpath(self::packageRoot().'/resources/js'),
            'npm' => realpath(self::packageRoot().'/node_modules'),
            default => false,
        };

        $file = $base === false ? false : realpath($base.'/'.$path);

        if (
            $base === false
            || $file === false
            || ! str_starts_with($file, $base.DIRECTORY_SEPARATOR)
            || ! is_file($file)
            || ! in_array(Str::lower(pathinfo($file, PATHINFO_EXTENSION)), ['js', 'mjs'], true)
        ) {
            throw new NotFoundHttpException;
        }

        return new Response((string) file_get_contents($file), 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @return list<string>
     */
    private static function rootPackages(): array
    {
        return ['@tiptap/core', '@tiptap/pm', '@tiptap/starter-kit', '@tiptap/extension-mention', '@tiptap/suggestion'];
    }

    private static function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function manifest(string $name): ?array
    {
        $path = self::packageRoot().'/node_modules/'.$name.'/package.json';

        if (! is_file($path)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        if (! is_array($manifest)) {
            return null;
        }

        $normalised = [];

        foreach ($manifest as $key => $value) {
            $normalised[(string) $key] = $value;
        }

        return $normalised;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, string>
     */
    private static function entries(array $manifest): array
    {
        $exports = $manifest['exports'] ?? null;

        if (is_array($exports)) {
            $subpaths = array_filter(array_keys($exports), fn (int|string $key): bool => str_starts_with((string) $key, '.'));
            $map = $subpaths === [] ? ['.' => $exports] : array_intersect_key($exports, array_flip($subpaths));
            $entries = [];

            foreach ($map as $subpath => $target) {
                $file = self::pick($target);

                if ($file !== null && ! str_contains((string) $subpath, '*')) {
                    $entries[substr((string) $subpath, 1)] = $file;
                }
            }

            if ($entries !== []) {
                return $entries;
            }
        }

        foreach (['module', 'main'] as $field) {
            $file = $manifest[$field] ?? null;

            if (is_string($file) && preg_match('/\.m?js$/', $file) === 1) {
                return ['' => $file];
            }
        }

        return [];
    }

    private static function pick(mixed $target): ?string
    {
        if (is_string($target)) {
            return preg_match('/\.m?js$/', $target) === 1 ? $target : null;
        }

        if (! is_array($target)) {
            return null;
        }

        foreach (['import', 'module', 'browser', 'default'] as $condition) {
            if (array_key_exists($condition, $target)) {
                $file = self::pick($target[$condition]);

                if ($file !== null) {
                    return $file;
                }
            }
        }

        return null;
    }
}
