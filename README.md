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

## Installation

1. Clone this repository.
2. Copy `include/db/config.php.sample` (if available) to `include/db/config.php` and fill in your database credentials.
3. Ensure the `cache/` and `files/` directories are writable by the web server.
4. Run the installer or upgrade scripts as needed.

## History

This project originated as a fork of Phorum 5.2.23, maintained for the LibreForum community, before being spun off as an independent effort to keep the Phorum spirit alive in the modern web era.

## License

Phorum is licensed under the [Phorum License](http://www.phorum.org/license.txt).
