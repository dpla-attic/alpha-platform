<?php

namespace org\librarycloud\api\validation;

use org\librarycloud\api\utils as utils;

/**
* Logic related to validation of request parameters that can be used
* in LibraryCloud.
*
* We're very intersted in echoing out useful error messages
* to the user.
*
* @author     Matt Phillips <mphillips@law.harvard.edu>
* @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
*/

class http_validator {

    public $http_request;
    public $lc_config;

    /**
     * We'll need some data in this class. Get it here
     *
     * @param http_request $http_request a container holding the details of the HTTP request
     * @param lc_config $lc_config a container holding the details of our config
     * @throws Exception if we can't validate a field passed in with the request, we raise this flag
     */
    function __construct($http_request, $lc_config) {
        $this->http_request = $http_request;
        $this->lc_config = $lc_config;
    }

    /**
     * Validate that the fieds specified in the incoming http request exist
     */
    function validate() {
        // A list of anything that a user can search or facet on (this means any
        // field that is indexed in solr)
        $valid_fields = $this->lc_config['valid_params'];

        // The fields in which we can supply stats
        $valid_numeric_fields = array('date');

        // Any field that is required and is common to all resources
        // If you wanted to require an API key param, it'd go here
        $required_common_fields = array();

        // Validate search types (the field to search on)
        if (!empty($this->http_request->params['search_type'])){
            $search_type = $this->http_request->params['search_type'][0];
//                throw new \Exception('Incorrect search type specified, ' .
//                    '  -->  ' . $this->http_request->params['search_type'][0] . '  <--  See ' .
//                    $this->lc_config['lc_doc_loc'] . ' for documention on searh types.');


//            if (!empty($search_type) && !in_array($search_type, $valid_fields)) {
//            }
        }

        // Validate facet fields
        if (!empty($this->http_request->params['facet'])) {
            foreach ($this->http_request->params['facet'] as $facet) {
                if (!in_array($facet, $valid_fields)) {
                    throw new \Exception("Incorrect facet field,  -->  $facet  <--  specified. See " .
                        $this->lc_config['lc_doc_loc'] . " for documention on facet fields.");
                }
            }
        }

        // Validate stats fields
        if (!empty($this->http_request->params['stats'])) {
            foreach ($this->http_request->params['stats'] as $stats) {
                if (!in_array($stats, $valid_numeric_fields)) {
                    throw new \Exception("Incorrect stats field,  -->  $stats  <--  specified. See " .
                        $this->lc_config['lc_doc_loc'] . " for documention on stats fields.");
                }
            }
        }

        // Validate required fields
        foreach ($required_common_fields as $required_param) {
            if (!array_key_exists($required_param, $this->http_request->params)) {
                throw new \Exception("You did not supply the required field, $required_param. See " .
                    $this->lc_config['lc_doc_loc'] . " for details on required fields.");
            }
        }
    }

}

?>