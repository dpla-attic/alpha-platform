<?php

namespace org\librarycloud\api\config;

use org\librarycloud\api\utils as utils;

/**
* Logic to deal with multiple types of configs
*
* @author     Matt Phillips <mphillips@law.harvard.edu>
* @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
*/

class config {

    public $http_request;

    /**
     * We'll need some data in this class. Get it here
     *
     * @param http_request $http_request a container holding the details of the HTTP request
     */
    function __construct($http_request) {
        $this->http_request = $http_request;
    }
    
    /**
     * Get the resource config and merge it with the master config
     * Attempt to retrieve through memcached before opening the ini file
     *
     * @return array a nested array of all config details
     * @throws Exception an exception is raised if the local config isn's consistent
     */
    function get_config() {
        $memcached = new \Memcached();
        $memcached->addServer(MEMCACHED_HOST, MEMCACHED_PORT);
        
        if ($memcached->get('lc_config') && MEMCACHED_ENABLED) {
            return $memcached->get('lc_config');
        }
        else {       
            // Load our master config
            $master_config =  parse_ini_file(LC_HOME . LC_MASTER_CONFIG, True);
        
            // Let's get the local config based on the resource type (an action parameter)
            if (!empty($this->http_request->action_params['resource_type'])){
                $resource_type = $this->http_request->action_params['resource_type'];
                if (!empty($resource_type) && !in_array($resource_type, array_keys($master_config['resource_types']))) {
                  throw new \Exception('Incorrect resource type specified' .
                  '  -->  ' . $resource_type . '  <--  See ' .
                  $master_config['doc']['lc_doc_loc'] . ' for documention on resource types.');
                }
            }
        
            $resource_config = parse_ini_file(LC_HOME . $master_config['resource_types'][$resource_type]);

            // Since we turn all sections in our master config into arrays (this 
            // way we can validate keys easily), we need to flatten it back down
            $flattened_master_config = array();
            foreach ($master_config as $top_level_element) {
                foreach ($top_level_element as $key => $value) {
                    $flattened_master_config[$key] = $value;
                }
            }

            $merged = array_merge($flattened_master_config, $resource_config);
            $memcached->set('lc_config', $merged);
            return $merged;
        }
    }
}
?>
