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

/**
 * @internal NULL-Wrapper for global cache
 */
class WdfOffCacheWrapper
{
    function __construct($key_prefix)
    {
    }

    function get($key,$default)
    {
        return $default;
    }

    function set($key,$val,$ttl)
    {
        return true;
    }

    function delete($key)
    {
        return true;
    }

    function clear($expired_only)
    {
        return true;
    }

    function info()
    {
        return [];
    }

    function keys()
    {
        return [];
    }
}