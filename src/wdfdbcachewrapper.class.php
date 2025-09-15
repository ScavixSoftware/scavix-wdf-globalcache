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
namespace ScavixWDF\Wdf;

/**
 * @internal DB-Wrapper for global cache
 */
class WdfDbCacheWrapper
{
    private $id, $ds;

    function __construct($key_prefix)
    {
        $this->id = substr(md5($key_prefix),0,16);
        if( isset($GLOBALS['CONFIG']['globalcache']['datasource']) )
            $this->ds = model_datasource($GLOBALS['CONFIG']['globalcache']['datasource']);
        else
        {
            log_warn("\$CONFIG['globalcache']['datasource'] not specified, using 'default'");
            $this->ds = model_datasource('default');
        }
    }

    function get($key, $default)
    {
        try
        {
            $ret = $this->ds->ExecuteScalar(
                "SELECT cvalue FROM wdf_cache WHERE ckey=? AND (valid_until IS NULL OR valid_until>=" . $this->ds->Driver->Now() . ")",
                [md5($key)]
            );
        }
        catch (Exception $ex)
        {
            return $default;
        }
        if ($ret === false)
            return $default;
        return session_unserialize($ret);
    }

    function set($key, $val, $ttl)
    {
        $val = session_serialize($val);
        $now = $this->ds->Driver->Now($ttl);
        try
        {
            if ($ttl > 0)
            {
                $this->ds->ExecuteSql(
                    "REPLACE INTO wdf_cache(ckey,full_key,cvalue,valid_until)VALUES(?,?,?,$now)",
                    [md5($key), $key, $val]
                );
            }
            else
                $this->ds->ExecuteSql(
                    "REPLACE INTO wdf_cache(ckey,full_key,cvalue)VALUES(?,?,?)",
                    [md5($key), $key, $val]
                );
        }
        catch (Exception $ex)
        {
            $this->ds->ExecuteSql("CREATE TABLE IF NOT EXISTS wdf_cache (
                ckey VARCHAR(32)  NOT NULL,
                cvalue LONGTEXT  NOT NULL,
                valid_until DATETIME  NULL,
                full_key TEXT  NOT NULL,
                PRIMARY KEY (ckey))");

            if ($ttl > 0)
                $this->ds->ExecuteSql(
                    "REPLACE INTO wdf_cache(ckey,full_key,cvalue,valid_until)VALUES(?,?,?,$now)",
                    [md5($key), $key, $val]
                );
            else
                $this->ds->ExecuteSql(
                    "REPLACE INTO wdf_cache(ckey,full_key,cvalue)VALUES(?,?,?)",
                    [md5($key), $key, $val]
                );
        }
        return true;
    }

    function delete($key)
    {
        try
        {
            $this->ds->ExecuteSql("DELETE FROM wdf_cache WHERE ckey=?", md5($key));
        }
        catch (Exception $ex)
        {
        }
        return true;
    }

    function clear($expired_only)
    {
        $sql = $expired_only ? "DELETE FROM wdf_cache WHERE valid_until<now()" : "DELETE FROM wdf_cache";
        try
        {
            $this->ds->ExecuteSql($sql);
        }
        catch (Exception $ex)
        {
        }
        return true;
    }

    function info()
    {
        try
        {
            $ret = "Global cache is handled by DB module.\n";
            $ret .= "Datasource: {$GLOBALS['CONFIG']['globalcache']['datasource']}\n";
            $ret .= "DSN: " . $this->ds->GetDsn() . "\n";
            $ret .= "Records: " . $this->ds->ExecuteScalar("SELECT count(*) FROM wdf_cache") . "\n";
        }
        catch (Exception $ex)
        {
        }
        return $ret;
    }

    function keys()
    {
        try
        {
            $rs = $this->ds->ExecuteSql(
                "SELECT full_key FROM wdf_cache WHERE (valid_until IS NULL OR valid_until>=" . $this->ds->Driver->Now() . ")"
            );
            return $rs->Enumerate('full_key');
        }
        catch (Exception $ex)
        {
        }
        return [];
    }
}