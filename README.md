# Dotwheel

## Introduction

Dotwheel is a PHP framework of useful performance-tuned code for professional developers. It provides lightweight solutions for most frequent development tasks and may be freely integrated with any architecture, be it a complex Model-View-Controller application or just a bunch of CLI scripts.

## Features

- **HTTP-related module** with auto-definition of [request format][], special object-oriented [response architecture][]
- **advanced technics of database access** including centralized [DB repository][], sharding implementation, blob compression, MySQL handler interface
- **cache handling** including memcache and APCu wrappers
- **UI module** with [handling output][] via named buffers, Twitter Bootstrap integration, easy HTML table generation using information from DB fields repository
- additional utility classes helping with [handling ACL][], [NLS support][] based on gettext `.po` files, password hashing, etc.

Fully namespaced, framework facilitates the use of advanced development technics such as data caching, database sharding, high-speed user interfaces when creating modern web-based applications.

## Requirements

Runs on PHP 5.3, latest PHP version recommended.

The following extensions are addressed:

- `apcu` or `memcached` for caching module
- `mysqli` for database module (`zlib`, `json` for blob compression)
- `filter` for input filtering

## Installation

Clone the repository into the `/vendors` directory of your project, update `set_include_path()` in your autoloader, go! For more instructions refer to [Dotwheel adoption strategy][].

[request format]: /doc/request_format.md
[response architecture]: /doc/http_response_introduction.md
[db repository]: /doc/db_repository.md
[handling output]: /doc/handling_output.md
[handling acl]: /doc/handling_acl.md
[nls support]: /doc/nls_support.md
[dotwheel adoption strategy]: /doc/adoption_strategy.md
