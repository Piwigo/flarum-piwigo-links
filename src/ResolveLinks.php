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

use Flarum\Http\RequestUtil;
use Flarum\User\Guest;
use Illuminate\Contracts\Container\Container;
use Piwigo\Links\Resolver\Extension;
use Piwigo\Links\Resolver\Post;
use Piwigo\Links\Resolver\Resolver;
use Piwigo\Links\Resolver\Svn;
use Piwigo\Links\Resolver\Topic;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;

/**
 * Finds the short syntaxes when a post is rendered and turns them into
 * PIWIGOLINK tags, so imported posts work without being parsed again.
 */
final class ResolveLinks
{
    /** @var array<string, class-string<Resolver>> */
    private const RESOLVERS = [
        'svn' => Svn::class,
        'extension' => Extension::class,
        'topic' => Topic::class,
        'post' => Post::class,
    ];

    public function __construct(private Container $container)
    {
    }

    public function __invoke(Renderer $renderer, $context, string $xml, ?ServerRequestInterface $request = null): string
    {
        $pattern = '/\b(' . implode('|', array_keys(self::RESOLVERS)) . '):(\d+)\b/';

        if (!preg_match_all($pattern, $xml, $matches, PREG_SET_ORDER)) {
            return $xml;
        }

        $actor = $request ? RequestUtil::getActor($request) : new Guest();

        $idsByType = [];
        foreach ($matches as [, $type, $id]) {
            $idsByType[$type][] = (int) $id;
        }

        $links = [];
        foreach ($idsByType as $type => $ids) {
            /** @var Resolver $resolver */
            $resolver = $this->container->make(self::RESOLVERS[$type]);
            $links[$type] = $resolver->resolve(array_values(array_unique($ids)), $actor);
        }

        $xml = preg_replace_callback($pattern, function (array $m) use ($links) {
            $link = $links[$m[1]][(int) $m[2]] ?? null;

            if ($link === null) {
                return $m[0];
            }

            return sprintf(
                '<%s label="%s" url="%s">%s</%1$s>',
                ConfigureParser::TAG,
                htmlspecialchars($link->label, ENT_QUOTES | ENT_XML1),
                htmlspecialchars($link->url, ENT_QUOTES | ENT_XML1),
                $m[0]
            );
        }, $xml);

        // <t> is plain text: the renderer prints it without looking at tags
        if (str_starts_with($xml, '<t>')) {
            $xml = '<r>' . substr($xml, 3, -4) . '</r>';
        }

        return $xml;
    }
}
