<?php

namespace org\librarycloud\api;

/**
* This is the entry point (front controller) to the LibraryCloud API. Here, 
* we'll convert the HTTP request to an object and pass it to the flow 
* managager.
*
* Let's try to keep this pretty minimial. Set a couple of global constants
* for config management, convert the request to an
* http_request object, then pass it off to our flow_manager.
*
* @author     Matt Phillips <mphillips@law.harvard.edu>
* @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
*/

/* All internal class loading statements are relative to lc_home */
define('LC_HOME', dirname(dirname(__FILE__)) . '/');
define('LC_MASTER_CONFIG', 'etc/librarycloud.ini' );

/* If possible, we don't want to load a config file, so let's define the 
 * memcache config details outside of an ini file
 */
define('MEMCACHED_ENABLED', false);
define('MEMCACHED_HOST', 'localhost');
define('MEMCACHED_PORT', 11211);

require_once LC_HOME . 'api/classes/http/http_request.php';
require_once LC_HOME . 'api/classes/manager/flow_manager.php';

use org\librarycloud\api\http as http;
use org\librarycloud\api\manager as manager;

/* parse the http request into an http object */
$http_request = new http\http_request($_SERVER);

/* flow_manager controls the request/response flow */
$flow_manager = new manager\flow_manager();
$flow_manager->push_the_power_button($http_request);

?>