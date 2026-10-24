# Piwigo Links

![License](https://img.shields.io/badge/license-MIT-blue.svg)

A [Flarum](https://flarum.org) extension for the piwigo.org forums. It brings
back the short syntaxes of the former FluxBB forums: write `topic:123` in a
post, readers see a link with the topic title.

## Syntaxes

| You write       | Readers see                                         |
| --------------- | --------------------------------------------------- |
| `topic:123`     | `[Forum, topic 123] Title of the discussion`        |
| `post:456`      | `[Forum, post 456 by Author in topic 123] Title`    |
| `extension:789` | `[extension by Author] Name of the extension`       |
| `svn:1234`      | `[Subversion] r1234`                                |

Each one is a link, opened in a new tab.

## How it works

- **Resolved when a post is displayed, not when it is saved.** Posts imported
  from FluxBB work without being parsed again, and a renamed discussion shows
  its current title everywhere.
- **Visibility is respected.** `topic:` and `post:` only resolve what the reader
  is allowed to see. A private topic, or an id that does not exist, stays plain
  text, so nothing reveals that it exists.
- **Code and links are left alone.** A syntax inside a code block, inside a URL
  or inside BBCode markup is not touched.
- **One query per type and per post.** A post with five `topic:` makes one
  query for the five. Posts without any syntax are skipped right away.
- **The extension list comes from the Piwigo extension directory** and is kept
  5 minutes in the Flarum cache. If the directory is down, `extension:` stays
  plain text for a minute, and pages never wait more than 3 seconds.
- **Links follow the forum domain**, so the same extension serves piwigo.org,
  fr.piwigo.org and de.piwigo.org.

Topic and post ids are the FluxBB ids, kept by the import.

## Requirements

- Flarum 1.8
- PHP 8.1 or later

## Installation

The extension is not on Packagist. Add the repository, then require it:

```sh
composer config repositories.piwigo-links vcs https://github.com/Piwigo/flarum-piwigo-links
composer require piwigo/flarum-piwigo-links:dev-main
php flarum extension:enable piwigo-piwigo-links
php flarum cache:clear
```

## Updating

```sh
composer update piwigo/flarum-piwigo-links
php flarum cache:clear
```

`cache:clear` matters: Flarum keeps the compiled formatter in its cache.

## Tests

```sh
composer install
composer test:unit
```

## License

MIT, see [LICENSE.md](LICENSE.md).
