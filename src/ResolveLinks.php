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

    /**
     * Text inside these tags is left alone: code is shown as typed, links
     * already are links, and s/e/i hold the BBCode markup the renderer drops.
     */
    private const SKIP_TAGS = ['CODE', 'C', 'URL', 'EMAIL', 's', 'e', 'i'];

    public function __construct(private Container $container)
    {
    }

    public function __invoke(Renderer $renderer, $context, string $xml, ?ServerRequestInterface $request = null): string
    {
        $pattern = '/\b(' . implode('|', array_keys(self::RESOLVERS)) . '):(\d+)\b/';

        // quick exit for the posts without any short syntax, nearly all of them
        if (!preg_match($pattern, $xml)) {
            return $xml;
        }

        // tags at odd indexes, text between them at even indexes: attributes
        // live inside the tags, so they are never touched
        $parts = preg_split('/(<[^>]*>)/', $xml, -1, PREG_SPLIT_DELIM_CAPTURE);

        // first pass: collect the ids of the text that will be displayed
        $idsByType = [];
        $this->eachDisplayedText($parts, function (string $text) use ($pattern, &$idsByType) {
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
            foreach ($matches as [, $type, $id]) {
                $idsByType[$type][] = (int) $id;
            }

            return $text;
        });

        if (!$idsByType) {
            return $xml;
        }

        $actor = $request ? RequestUtil::getActor($request) : new Guest();

        $links = [];
        foreach ($idsByType as $type => $ids) {
            /** @var Resolver $resolver */
            $resolver = $this->container->make(self::RESOLVERS[$type]);
            $links[$type] = $resolver->resolve(array_values(array_unique($ids)), $actor);
        }

        // second pass: replace in the same text, with the resolved links
        $parts = $this->eachDisplayedText($parts, function (string $text) use ($pattern, $links) {
            return preg_replace_callback($pattern, function (array $m) use ($links) {
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
            }, $text);
        });

        $xml = implode('', $parts);

        // <t> is plain text: the renderer prints it without looking at tags
        if (str_starts_with($xml, '<t>')) {
            $xml = '<r>' . substr($xml, 3, -4) . '</r>';
        }

        return $xml;
    }

    /**
     * Calls $callback on every piece of text outside SKIP_TAGS and puts back
     * what it returns.
     *
     * @param string[] $parts
     * @return string[]
     */
    private function eachDisplayedText(array $parts, callable $callback): array
    {
        $skipDepth = 0;

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                // a tag: count how deep we are inside the tags to skip
                if (preg_match('#^<(/?)([A-Za-z0-9_]+)#', $part, $m) && in_array($m[2], self::SKIP_TAGS, true)) {
                    if ($m[1] === '/') {
                        $skipDepth--;
                    } elseif (!str_ends_with($part, '/>')) {
                        $skipDepth++;
                    }
                }

                continue;
            }

            if ($skipDepth === 0 && $part !== '') {
                $parts[$i] = $callback($part);
            }
        }

        return $parts;
    }
}
