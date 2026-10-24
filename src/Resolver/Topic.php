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

use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Piwigo\Links\Link;

/**
 * topic:123 → discussion 123 with its current title.
 *
 * The importer kept the FluxBB topic ids, so topic:123 is discussion 123.
 * Only discussions the reader can see are resolved: a private or missing
 * topic stays plain text, which does not reveal that it exists.
 */
final class Topic implements Resolver
{
    public function __construct(private UrlGenerator $url, private SlugManager $slugManager)
    {
    }

    public function resolve(array $ids, User $actor): array
    {
        // one query for every topic: of the post, with Flarum's visibility rules
        $discussions = Discussion::whereVisibleTo($actor)
            ->whereIn('id', $ids)
            ->get(['id', 'title', 'slug']);

        $slugDriver = $this->slugManager->forResource(Discussion::class);

        $links = [];
        foreach ($discussions as $discussion) {
            $links[$discussion->id] = new Link(
                $this->url->to('forum')->route('discussion', ['id' => $slugDriver->toSlug($discussion)]),
                sprintf('[Forum, topic %d] %s', $discussion->id, $discussion->title)
            );
        }

        return $links;
    }
}
