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
 * svn:123 → the Subversion changeset, through the piwigo.org short link.
 *
 * Piwigo left Subversion years ago, the syntax only lives in old posts, so
 * nothing is looked up: the link is built from the id alone.
 */
final class Svn implements Resolver
{
    public const BASE_URL = 'https://xx';

    public function resolve(array $ids, User $actor): array
    {
        $links = [];

        foreach ($ids as $id) {
            $links[$id] = new Link(self::BASE_URL . $id, sprintf('[Subversion] r%d', $id));
        }

        return $links;
    }
}
