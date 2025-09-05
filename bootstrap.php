<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2025 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2025 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

use ScavixWDF\Wdf;

Wdf::RegisterPackage('globalcache', 'globalcache_init');

const globalcache_CACHE_OFF = 0;
const globalcache_CACHE_EACCELERATOR = 1;
const globalcache_CACHE_MEMCACHE = 2;
const globalcache_CACHE_APC = 4;
const globalcache_CACHE_DB = 5;
const globalcache_CACHE_YAC = 6;
const globalcache_CACHE_FILES = 7;

/**
 * Initializes the globalcache module.
 *
 * @return void
 */
function globalcache_init()
{
	global $CONFIG;

    classpath_add(__DIR__ . '/src', true, 'system');

	if( !isset($CONFIG['globalcache']) )
		$CONFIG['globalcache'] = [];
    if (!isset($CONFIG['globalcache']['CACHE']))
        $CONFIG['globalcache']['CACHE'] = globalcache_CACHE_OFF;

    if (!is_numeric($CONFIG['globalcache']['CACHE']))
    {
        if( defined($CONFIG['globalcache']['CACHE']))
            $CONFIG['globalcache']['CACHE'] = constant($CONFIG['globalcache']['CACHE']);
        else
        {
            log_warn("Invalid globalcache handler '{$CONFIG['globalcache']['CACHE']}', falling back to OFF");
            $CONFIG['globalcache']['CACHE'] = globalcache_CACHE_OFF;
        }
    }

	if( isset($CONFIG['globalcache']['key_prefix']))
		$prefix = $CONFIG['globalcache']['key_prefix'];
    else
    {
        $servername = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:"SCAVIX_WDF_SERVER";
        $prefix = "K" . md5($servername . "-" . session_name() . "-" . getAppVersion('nc'));
    }

    switch( $CONFIG['globalcache']['CACHE'] )
    {
        case globalcache_CACHE_FILES:
            $GLOBALS['globalcache_handler'] = new WdfFileCacheWrapper($prefix);
            break;
        case globalcache_CACHE_DB:
            $GLOBALS['globalcache_handler'] = new WdfDBCacheWrapper($prefix);
            break;
        default:
            if( $CONFIG['globalcache']['CACHE'] != globalcache_CACHE_OFF )
                log_warn("Globalcache handler {$CONFIG['globalcache']['CACHE']} not found/deprecated, falling back to OFF");
            $GLOBALS['globalcache_handler'] = new WdfOffCacheWrapper($prefix);
            break;
    }
}

/**
 * Save a value/object in the global cache.
 *
 * @param string $key the key of the value
 * @param mixed $value the object/string to save
 * @param int $ttl time to live (in seconds) of the caching
 * @return bool true if ok, false on error
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_set($key, $value, $ttl = false)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

    return $GLOBALS['globalcache_handler']->set($key, $value, $ttl);
}

/**
 * Get a value/object from the global cache.
 *
 * @param string $key the key of the value
 * @param mixed $default a default return value if the key can not be found in the cache
 * @return mixed The object from the cache or `$default`
 *
 * @suppress PHP0404,PHP0417,PHP0412,PHP0423,PHP1412,PHP0443
 */
function globalcache_get($key, $default = false)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return $default;

    return $GLOBALS['globalcache_handler']->get($key, $default);
}

/**
 * Empty the whole cache.
 *
 * @param bool $expired_only If true, only expired entries will be deleted
 * @return bool true if ok, false on error
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_clear($expired_only=false)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

	return $GLOBALS['globalcache_handler']->clear($expired_only);
}

/**
 * Delete a value from the global cache.
 *
 * @param string $key the key of the value
 * @param mixed $value the object/string to save
 * @param int $ttl time to live (in seconds) of the caching
 * @return bool true if ok, false on error
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_delete($key)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

    return $GLOBALS['globalcache_handler']->delete($key);
}

/**
 * Returns information about the cache usage.
 *
 * Note: this currently returns various different information and format thus needs to be streamlined.
 * @return mixed Cache information
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_info()
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

    return $GLOBALS['globalcache_handler']->info();
}

/**
 * Gets a list of all keys in the cache.
 *
 * @return array list of all keys
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_list_keys()
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return [];

    return $GLOBALS['globalcache_handler']->keys();
}