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
use Flarum\Post\Post as FlarumPost;
use Flarum\User\User;
use Piwigo\Links\Link;

/**
 * post:123 → post 123 inside its discussion, with the author and the title.
 *
 * The importer kept the FluxBB post ids. Flarum's visibility scope checks the
 * discussion too, so a post of a private forum stays plain text for a reader
 * who cannot see it.
 */
final class Post implements Resolver
{
    public function __construct(private UrlGenerator $url, private SlugManager $slugManager)
    {
    }

    public function resolve(array $ids, User $actor): array
    {
        // posts, their discussions and their authors: three queries in total
        $posts = FlarumPost::whereVisibleTo($actor)
            ->whereIn('posts.id', $ids)
            ->with(['discussion', 'user'])
            ->get(['posts.id', 'posts.discussion_id', 'posts.number', 'posts.user_id']);

        $slugDriver = $this->slugManager->forResource(Discussion::class);

        $links = [];
        foreach ($posts as $post) {
            if ($post->discussion === null) {
                continue;
            }

            $url = $this->url->to('forum')->route('discussion', [
                'id' => $slugDriver->toSlug($post->discussion),
                'near' => $post->number,
            ]);

            // guest posts and authors not imported have no user any more
            $label = $post->user
                ? sprintf('[Forum, post %d by %s in topic %d] %s', $post->id, $post->user->display_name, $post->discussion_id, $post->discussion->title)
                : sprintf('[Forum, post %d in topic %d] %s', $post->id, $post->discussion_id, $post->discussion->title);

            $links[$post->id] = new Link($url, $label);
        }

        return $links;
    }
}
