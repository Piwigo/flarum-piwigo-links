<?php

/*
 * This file is part of piwigo/flarum-piwigo-links.
 *
 * Copyright (c) 2026 Linty.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Piwigo\Links\Tests\unit;

use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use Piwigo\Links\ConfigureParser;
use Piwigo\Links\ResolveLinks;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Renderer;

class ResolveLinksTest extends TestCase
{
    private Parser $parser;

    private Renderer $renderer;

    private ResolveLinks $resolve;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Configurator();
        (new ConfigureParser())($config);

        ['parser' => $this->parser, 'renderer' => $this->renderer] = $config->finalize();

        $this->resolve = new ResolveLinks(new Container());
    }

    private function render(string $text): string
    {
        $xml = $this->parser->parse($text);

        return $this->renderer->render(($this->resolve)($this->renderer, null, $xml, null));
    }

    public function test_svn_becomes_a_link(): void
    {
        $this->assertSame(
            'fixed in <a href="https://piwigo.org/shortlinks/svn/123" class="PiwigoLink" target="_blank" rel="noopener">[Subversion] r123</a>',
            $this->render('fixed in svn:123')
        );
    }

    public function test_a_post_without_syntax_is_left_untouched(): void
    {
        $xml = $this->parser->parse('nothing to see');

        $this->assertSame($xml, ($this->resolve)($this->renderer, null, $xml, null));
    }
}
