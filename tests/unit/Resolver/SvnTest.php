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

use Flarum\Testing\unit\TestCase;
use Flarum\User\Guest;
use Piwigo\Links\Resolver\Svn;

class SvnTest extends TestCase
{
    public function test_every_id_becomes_a_short_link(): void
    {
        $links = (new Svn())->resolve([123, 4567], new Guest());

        $this->assertSame(['123', '4567'], array_map('strval', array_keys($links)));
        $this->assertSame('https://piwigo.org/shortlinks/svn/123', $links[123]->url);
        $this->assertSame('[Subversion] r123', $links[123]->label);
        $this->assertSame('[Subversion] r4567', $links[4567]->label);
    }
}
