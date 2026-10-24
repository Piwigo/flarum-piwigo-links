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

use Flarum\Extend;

return [
    (new Extend\Formatter())
        ->configure(ConfigureParser::class)
        ->render(ResolveLinks::class),
];
