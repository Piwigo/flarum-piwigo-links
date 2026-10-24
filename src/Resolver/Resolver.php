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

use Flarum\User\User;
use Piwigo\Links\Link;

/**
 * One resolver per short syntax (topic:, post:, svn:, extension:).
 *
 * Resolution happens when a post is rendered, not when it is saved, so a
 * renamed discussion shows its current title. A resolver receives every id
 * found in the post at once and must not issue one query per id.
 */
interface Resolver
{
    /**
     * @param int[] $ids
     * @param User $actor who is reading the post, a Guest when unknown
     * @return array<int, Link> keyed by id; an id left out stays as plain text
     */
    public function resolve(array $ids, User $actor): array;
}
