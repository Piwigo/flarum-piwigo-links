<?php

/*
 * This file is part of piwigo/flarum-piwigo-links.
 *
 * Copyright (c) 2026 Linty.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Piwigo\Links;

use s9e\TextFormatter\Configurator;

/**
 * Declares the PIWIGOLINK tag and how it renders.
 *
 * Nothing is detected when a post is saved: ResolveLinks finds the short
 * syntaxes and inserts the tag when the post is rendered.
 */
final class ConfigureParser
{
    public const TAG = 'PIWIGOLINK';

    public function __invoke(Configurator $config): void
    {
        $tag = $config->tags->add(self::TAG);

        // the template checker only accepts a dynamic href filtered by #url
        $tag->attributes->add('url')->filterChain->append('#url');
        $tag->attributes->add('label');

        $tag->template = '<a href="{@url}" class="PiwigoLink" target="_blank" rel="noopener"><xsl:value-of select="@label"/></a>';
    }
}
