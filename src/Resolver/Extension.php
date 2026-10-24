<?php

/*
 * This file is part of piwigo/flarum-piwigo-links.
 *
 * Copyright (c) 2026 Linty.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Piwigo\Links\Resolver;

use Flarum\Foundation\Config;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use Piwigo\Links\Link;

/**
 * extension:123 → the extension page on PEM, with its name and author.
 *
 * The list of extensions comes from the PEM API and is kept 5 minutes in the
 * Flarum cache, like FluxBB did with its cache file. The link goes to the
 * same domain as the forum: piwigo.org, fr.piwigo.org or de.piwigo.org.
 */
final class Extension implements Resolver
{
    public const CACHE_KEY = 'piwigo-links.extensions';

    private const API_URL = 'https://xx';

    private const TTL = 300;

    private const TTL_ON_FAILURE = 60;

    private const TIMEOUT = 3;

    public function __construct(private Repository $cache, private Config $config)
    {
    }

    public function resolve(array $ids, User $actor): array
    {
        $extensions = $this->extensions();
        $baseUrl = 'https://' . $this->config->url()->getHost() . '/ext/extension_view.php?eid=';

        $links = [];
        foreach ($ids as $id) {
            if (!isset($extensions[$id]['name'], $extensions[$id]['username'])) {
                continue;
            }

            $links[$id] = new Link(
                $baseUrl . $id,
                sprintf('[extension by %s] %s', $extensions[$id]['username'], $extensions[$id]['name'])
            );
        }

        return $links;
    }

    /**
     * @return array<int, array{name: string, username: string}>
     */
    private function extensions(): array
    {
        $extensions = $this->cache->get(self::CACHE_KEY);
        if (is_array($extensions)) {
            return $extensions;
        }

        $content = @file_get_contents(self::API_URL, false, stream_context_create([
            'http' => ['timeout' => self::TIMEOUT],
        ]));

        // allowed_classes: the answer is an array, never let it build objects
        $extensions = $content === false ? false : @unserialize($content, ['allowed_classes' => false]);

        if (!is_array($extensions)) {
            // PEM is down: links stay plain text for a minute instead of
            // making every page wait for the timeout
            $this->cache->put(self::CACHE_KEY, [], self::TTL_ON_FAILURE);

            return [];
        }

        $this->cache->put(self::CACHE_KEY, $extensions, self::TTL);

        return $extensions;
    }
}
