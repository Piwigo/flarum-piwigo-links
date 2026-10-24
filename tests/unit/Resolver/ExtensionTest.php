<?php

/*
 * This file is part of piwigo/flarum-piwigo-links.
 *
 * Copyright (c) 2026 Linty.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Piwigo\Links\Tests\unit\Resolver;

use Flarum\Foundation\Config;
use Flarum\Testing\unit\TestCase;
use Flarum\User\Guest;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Piwigo\Links\Resolver\Extension;

class ExtensionTest extends TestCase
{
    /**
     * The cache is filled beforehand, so the PEM API is never called.
     */
    private function resolver(string $forumUrl): Extension
    {
        $cache = new Repository(new ArrayStore());
        $cache->put(Extension::CACHE_KEY, [
            5 => ['id_extension' => '5', 'name' => 'Czech', 'idx_category' => '8', 'username' => 'plg'],
        ], 300);

        return new Extension($cache, new Config(['url' => $forumUrl]));
    }

    public function test_a_known_extension_gets_its_name_and_author(): void
    {
        $links = $this->resolver('https://piwigo.org/forum')->resolve([5], new Guest());

        $this->assertSame('https://piwigo.org/ext/extension_view.php?eid=5', $links[5]->url);
        $this->assertSame('[extension by plg] Czech', $links[5]->label);
    }

    public function test_the_link_follows_the_forum_domain(): void
    {
        $links = $this->resolver('https://fr.piwigo.org/forum')->resolve([5], new Guest());

        $this->assertSame('https://fr.piwigo.org/ext/extension_view.php?eid=5', $links[5]->url);
    }

    public function test_an_unknown_extension_is_not_resolved(): void
    {
        $this->assertSame([], $this->resolver('https://piwigo.org/forum')->resolve([999999], new Guest()));
    }
}
