<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package  Cache
 *
 * Cache backend driver. Kohana comes with file, database, and memcache drivers.
 * - File cache is fast and reliable, but requires many filesystem lookups.
 * - Database cache can be used to cache items remotely, but is slower.
 * - Memcache is very high performance, but prevents cache tags from being used.
 */
$config['driver'] = 'file';

/**
 * Driver parameters, specific to each driver.
 */
$config['params'] = 'application/cache';

/**
 * Default lifetime to of caches, seconds. By default, caches are stored for
 * thirty minutes. Specific lifetime can also be set when creating a new cache.
 *
 * Setting this to 0 will never automatically delete caches.
 */
$config['lifetime'] = 1800;

/**
 * Average number of cache requests that will processed before all expired
 * caches are deleted. This is commonly referred to as "garbage collection".
 * Setting this to a negative number will disable automatic garbage collection.
 */
$config['requests'] = 1000;