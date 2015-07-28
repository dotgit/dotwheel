# Dotwheel

## Introduction

Dotwheel is a PHP framework of useful performance-tuned code for professional developers. It provides lightweight solutions for most frequent development tasks and may be freely integrated with any architecture, be it a complex Model-View-Controller application or just a bunch of CLI scripts.

## Features

- **HTTP-related module** with auto-definition of [request format][], special object-oriented [response architecture][]
- **advanced technics of database access** including centralized [DB repository][], sharding implementation, blob compression, MySQL handler interface
- **cache handling** including memcache and APCu wrappers
- **UI module** with [handling output][] via named buffers, Twitter Bootstrap integration, easy HTML table generation using information from DB fields repository
- additional utility classes helping with [handling ACL][], [NLS support][] based on gettext `.po` files, password hashing, etc.

Fully namespaced, framework facilitates the use of advanced development technics when creating modern high-speed web-based applications.

## Requirements

Runs on PHP 5.3, latest PHP version recommended.

The following extensions are addressed:

- `apcu` or `memcached` for caching module
- `mysqli` for database module (`zlib`, `json` for blob compression)
- `filter` for input filtering

## Installation

Clone the repository into the `/vendors` directory of your project, update `set_include_path()` in your autoloader, go!

For the instructions on starting a new project refer to [Starter][].

[request format]: /doc/http_request.md
[response architecture]: /doc/http_response.md
[db repository]: /doc/db_repository.md
[handling output]: /doc/handling_output.md
[handling acl]: /doc/handling_acl.md
[nls support]: /doc/nls_support.md
[starter]: /doc/starter.md
