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

/**
 * What a short syntax resolves to: where it points and what to print.
 */
final class Link
{
    public function __construct(
        public readonly string $url,
        public readonly string $label
    ) {
    }
}
