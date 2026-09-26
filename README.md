# LibreForum

LibreForum is a modernized fork of the classic Phorum 5.2 discussion board. 

## Goals

- **PHP 8+ Compatibility**: Fully patched to run on modern PHP versions (8.0, 8.1, 8.2, 8.3).
- **Security First**: Backported security patches and hardened defaults.
- **Modernization**: Progressive transition towards a modern stack, including API support and eventual JavaScript/TypeScript components.
- **Maintainability**: Cleaned up codebase with a focus on long-term stability.

## Features (Added in this fork)

- Fixed numerous PHP 8 deprecations (curly brace offset access, old constructors, `=& new`, etc.).
- Hardened session management with `HttpOnly` cookies.
- Security headers (X-Frame-Options, etc.).
- "Force Password Change" feature for improved user security.
- Improved database error handling.
- **Markdown & Video Auto-Embedding**: Integrated Markdown formatting support with native YouTube/Vimeo video auto-embedding (simply paste the video URL on its own line) and updated editor toolbar tools.
- **Full Unicode (utf8mb4)**: emojis and every other Unicode character are stored as typed; with MySQL's 3-byte `utf8`, a message containing an emoji was rejected with a database error.

## Installation

1. Clone this repository.
2. Copy `include/db/config.php-dist` to `include/db/config.php` and fill in your database credentials.
3. Ensure the `cache/` and `files/` directories are writable by the web server.
4. Run the installer or upgrade scripts as needed.

### Upgrading an existing forum to utf8mb4

Forums created with `'charset' => 'utf8'` reject emojis. After a backup, run
`php scripts/convert_to_utf8mb4.php` to see what would change, then again with
`--apply`, and finally set `'charset' => 'utf8mb4'` in `include/db/config.php`.
The script converts the `utf8` tables, shortens the indexes that would become
too long, checks that the content is unchanged, and leaves `latin1` tables alone.

## License

LibreForum is a fork of Phorum 5.2, and its code is covered by two licenses:

- the code **inherited from Phorum** remains under the **Phorum License 2.0** — see [LICENSE-PHORUM](LICENSE-PHORUM). Its copyright notice, conditions and disclaimer must be kept in any redistribution, and the name "Phorum" may not be used to name or promote a derived product;
- **modifications and additions made by LibreForum** are licensed under the **Apache License 2.0** — see [LICENSE](LICENSE).

See [NOTICE](NOTICE) for the required attributions. Internal identifiers such as `$PHORUM`, `phorum_*()` or the `phorum_` table prefix are kept for compatibility with existing modules; they do not name the product.
