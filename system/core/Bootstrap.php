<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana process control file, loaded by the front controller.
 * 
 * $Id: Bootstrap.php 2824 2008-06-11 17:24:09Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */

define('KOHANA_VERSION',  '2.1.2');
define('KOHANA_CODENAME', 'Diuturnal');

// Test of Kohana is running in Windows
define('KOHANA_IS_WIN', PHP_SHLIB_SUFFIX === 'dll');

// Kohana benchmarks are prefixed by a random string to prevent collisions
define('SYSTEM_BENCHMARK', uniqid());

// Load benchmarking support
require SYSPATH.'core/Benchmark'.EXT;

// Start: total_execution
Benchmark::start(SYSTEM_BENCHMARK.'_total_execution');

// Start: kohana_loading
Benchmark::start(SYSTEM_BENCHMARK.'_kohana_loading');

// Define Kohana error constant
defined('E_KOHANA') or define('E_KOHANA', 42);
// Define 404 error constant
defined('E_PAGE_NOT_FOUND') or define('E_PAGE_NOT_FOUND', 43);
// Define database error constant
defined('E_DATABASE_ERROR') or define('E_DATABASE_ERROR', 44);
// Define extra E_RECOVERABLE_ERROR for PHP < 5.2
defined('E_RECOVERABLE_ERROR') or define('E_RECOVERABLE_ERROR', 4096);
// Load core files
require SYSPATH.'core/utf8'.EXT;
require SYSPATH.'core/Config'.EXT;
require SYSPATH.'core/Log'.EXT;
require SYSPATH.'core/Event'.EXT;
require SYSPATH.'core/Kohana'.EXT;

// End: kohana_loading
Benchmark::stop(SYSTEM_BENCHMARK.'_kohana_loading');

// Start: system_initialization
Benchmark::start(SYSTEM_BENCHMARK.'_system_initialization');

Event::run('system.ready');
Event::run('system.routing');

// End: system_initialization
Benchmark::stop(SYSTEM_BENCHMARK.'_system_initialization');

Event::run('system.execute');
Event::run('system.shutdown');