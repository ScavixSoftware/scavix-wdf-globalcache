Scavix Software Web Development Framework
=========================================
This is a package for the Scavix Software Web Development Framework.
It plugs into the core system and provides a global cache, that may be used across user sessions.
It is useful to store global data like resource-paths and buffered user-independents.

Installation
============
Install the package with `composer require scavix/wdf-globalcache`.

Configuration
=============
```
$GLOBALS['CONFIG']['globalcache'] =
[
    'CACHE' => 'globalcache_CACHE_FILES|globalcache_CACHE_DB',
    'key_prefix => 'optional_prefix_to_separate_system_parts'
];
```

Dependencies
------------
* [scavix/wdf-core (^1.0.1)](https://packagist.org/packages/scavix/wdf-core#v1.0.1)
