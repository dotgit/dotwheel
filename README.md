dotwheel
====

Introduction
----

dotwheel is a performance-oriented, minimalistic php framework. Based on a Model-View-Controller architecture it helps with code organization and contains the following functionality:

- database access (including fields repository handling, advanced sharding technics, mysql handler interface, blob zipping)
- cache handling (including memcache and APC utilisation)
- http-related module (auto-definition of request type, clean response architecture)
- UI module (including Twitter Bootstrap integration, easy html table generation using information from fields repository)
- additional utility classes helping with ACL handling, password hashing, gettext-based NLS support, etc.

Fully namespaced, framework facilitates the use of advanced development technics such as data caching, database sharding, high-speed user interfaces when creating modern web-based applications.

Requirements
----
Runs on PHP 5.3, latest PHP version recommended. The following extensions are addressed:

- [caching] apc, memcached
- [database] mysqli, zlib
- [input] filter, pcre
- [output] gettext, json

Installation
----
Clone the repository into the Vendors directory of your project.
