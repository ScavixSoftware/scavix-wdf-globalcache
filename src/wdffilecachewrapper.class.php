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
 * @internal Wrapper class file-based caching
 */
class WdfFileCacheWrapper
{
    private $root;
    private $prefix;
    private $map;

    public function __construct($key_prefix)
    {
        $this->prefix = $key_prefix;
        $this->map = [];
        $this->root = sys_get_temp_dir()."/wdf_globalcache/{$key_prefix}";
        $um = umask(0);
        @mkdir($this->root,0777,true);
        umask($um);

        if( $this->get('WdfFileCacheWrapper::NextCleanup',0) < time() )
        {
            if( $lock = Wdf::GetLock(__METHOD__,0,false) )
            {
                $this->set('WdfFileCacheWrapper::NextCleanup', strtotime('midnight + 23 hour'));
                //log_debug("WdfFileCacheWrapper starting auto-cleanup");
                $this->clear(true,1);
                Wdf::ReleaseLock($lock);
            }
        }
    }

    protected function getPath($key)
    {
        $file = md5($key);
        $dir = $this->root."/".substr($file, 0, 2);
        $um = umask(0);
        @mkdir($dir, 0777, true);
        umask($um);
        return "$dir/$file";
    }

    protected function unpack($file, $metadata_only = false)
    {
        $c = @file_get_contents($file);
        if (!$c)
            return null;
        $res = session_unserialize($c);
        if (!isset($data['exp']) && isset($res['expiry']))
            $res['exp'] = $res['expiry'];
        elseif (!$metadata_only && is_array($res) && isset($res['data']) )
            $res['data'] = session_unserialize($res['data']);
        return $res;
    }

    public function set($key, $val, $ttl = 0)
    {
        $eol = time() + (($ttl <= 0) ? 86400 : $ttl);
        $val = [
            'exp' => $eol > time() ? $eol : false,
            'key' => $key,
            'data' => session_serialize($val),
        ];
        // Write to temp file first to ensure atomicity
        $um = umask(0);
        $dest = $this->getPath($key);
        $tmp = $dest . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, session_serialize($val), LOCK_EX);
        rename($tmp, $dest);
        umask($um);
        return true;
    }

    public function get($key,$default)
    {
        $file = $this->getPath($key);

        $filemtime = @filemtime($file);
        if (isset($this->map[$key]) && $this->map[$key]['filemtime'] == $filemtime)
            return $this->map[$key]['data'];

        $val = $this->unpack($file);

        if (!isset($val['key']) || $val['key'] != $key)
            return $default;
        if (!$val['exp'] || $val['exp'] > time())
        {
            $this->map[$key] = $val;
            $this->map[$key]['filemtime'] = $filemtime;
            return $val['data'];
        }
        $this->delete($key);
        return $default;
    }

    public function delete($key)
    {
        $file = $this->getPath($key);
        @unlink($file);
        if( isset($this->map[$key]) )
            unset( $this->map[$key]);
        return true;
    }

    function clear($expired_only, $ttl = 10)
    {
        $count = 0;
        $forced_end = time() + $ttl;
        system_walk_files($this->root, '*', function ($file)use($expired_only, $forced_end, $ttl,&$count)
        {
            if( $expired_only )
            {
                $val = $this->unpack($file,true);
                if( !isset($val['exp']) || !$val['exp'] || $val['exp'] > time() )
                    return;
                //usleep(100000);
            }
            @unlink($file);
            //log_debug($file);
            $count++;

            if( time()>$forced_end )
            {
                //log_debug("WdfFileCacheWrapper::clear() unfinished after {$ttl}s (and $count files), let others work too");
                $this->set('WdfFileCacheWrapper::NextCleanup', time());
                return false;
            }
        });
        $this->map = [];
        return true;
    }

    function info($include_keys=true)
    {
        $r = [
            'map_size' => count($this->map),
            'entries' => 0,
            'next_cleanup' => date("c", $this->get('WdfFileCacheWrapper::NextCleanup', time())),
            'next_cleanup_file' => $this->getPath('WdfFileCacheWrapper::NextCleanup'),
        ];
        if ($include_keys)
        {
            $keys = $this->keys();
            $r['entries'] = count($keys);
            $r['keys'] = $keys;
        }
        else
            system_walk_files($this->root, '*', function ($file) use (&$r)
            {
                $r['entries']++;
            });
        return $r;
    }

    function keys()
    {
        $ret = [];
        system_walk_files($this->root, '*', function ($file)use(&$ret)
        {
            $val = $this->unpack($file,true);
            if( isset($val['key']) )
                $ret[] = $val['key'];
        });
        return $ret;
    }
}