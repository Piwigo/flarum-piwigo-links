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

        // the BBCodes and the autolinking a real Flarum post goes through
        $config = new Configurator();
        $config->BBCodes->addFromRepository('B');
        $config->BBCodes->addFromRepository('CODE');
        $config->BBCodes->addFromRepository('URL');
        $config->plugins->load('Autolink');
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

    public function test_a_link_inside_formatting_is_resolved(): void
    {
        $this->assertStringContainsString(
            '<b><a href="https://piwigo.org/shortlinks/svn/1" class="PiwigoLink"',
            $this->render('[b]svn:1[/b]')
        );
    }

    public function test_a_code_block_is_left_as_typed(): void
    {
        $html = $this->render("[code]svn:1[/code]\nsvn:2");

        $this->assertStringNotContainsString('shortlinks/svn/1', $html);
        $this->assertStringContainsString('shortlinks/svn/2', $html);
    }

    public function test_an_autolinked_url_keeps_its_address(): void
    {
        $html = $this->render('see https://example.com/svn:1');

        $this->assertStringNotContainsString('PiwigoLink', $html);
        $this->assertStringContainsString('href="https://example.com/svn:1"', $html);
    }

    public function test_a_url_bbcode_keeps_its_attribute(): void
    {
        $html = $this->render('[url=https://example.com/?svn:1]example[/url]');

        $this->assertStringNotContainsString('PiwigoLink', $html);
        $this->assertStringContainsString('href="https://example.com/?svn:1"', $html);
    }

    public function test_a_post_without_syntax_is_left_untouched(): void
    {
        $xml = $this->parser->parse('nothing to see');

        $this->assertSame($xml, ($this->resolve)($this->renderer, null, $xml, null));
    }
}
