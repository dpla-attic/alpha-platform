<?php

namespace org\librarycloud\api\translator;

/**
 * Logic handle the transformation of an http_request to a solr_request
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
 */

require_once LC_HOME . 'api/classes/request/solr_data_store_request.php';
require_once LC_HOME . 'api/classes/solr/utils.php';

use org\librarycloud\api\request as request;
use org\librarycloud\api\solr as solr;

class solr_request_translator {

    public $http_request;
    public $solr_data_store_request;
    public $lc_config;

    /**
     * We'll need some data in this class. Get it here
     *
     * @param http_request $http_request a container holding the details of the HTTP request
     * @param lc_config $lc_config a container holding the details of our config
     */
    function __construct($http_request, $lc_config) {
        $this->http_request = $http_request;
        $this->solr_data_store_request = new request\solr_data_store_request();
        $this->lc_config = $lc_config;
    }

    /**
     * Let's translate the request set by our contructor into somethign we can pass to our 
     * 
     */
    function translate() {
        // Set some defaults
        $this->solr_data_store_request->resource = $this->http_request->action_params['resource_type'];
        $this->solr_data_store_request->query = '*:*';
        $this->solr_data_store_request->params = array('sort' => $this->lc_config['default_sort_field'] . ' desc');
        $this->solr_data_store_request->start = 0;
        $this->solr_data_store_request->rows = 25;

        $this->parse_primary_request();
    }

    /**
     * Builds the the properties of this object into a Solr request object
     * Order of precedence:
     * single resource id request: api/item/8982
     * search request: api/item/8982?search_type=title_exact&query=fight club&sort=total_score asc
     * all resources: api/item/
     */
    function parse_primary_request() {
        // Set some defaults in case anything goes wacky

        // Look ma, we have a direct resource request
        if (!empty($this->http_request->action_params['resource_id'])) {
            $this->solr_data_store_request->query = 'dpla.id:' . $this->http_request->action_params['resource_id'];
            $this->parse_commons_params();
            $this->parse_facet();
            $this->parse_stats();

           // Look ma, we have a search query
        } elseif (!empty($this->http_request->params['search_type'][0])) {
            $query_field_key = urldecode($this->http_request->params['search_type'][0]);
            $query_field_value = '*';

            // If we have a search query let's replace the "match anything, *" query
            if (!empty($this->http_request->params['query'][0])) {
                $query_field_value = urldecode($this->http_request->params['query'][0]);
            }

            $solr_query_string = $query_field_key . ':';
            
            if ($query_field_value != '*') {
                $solr_query_string = $solr_query_string . solr\utils::escape_solr_value($query_field_value);
            } else {
                $solr_query_string = $solr_query_string . $query_field_value;
            }

            $this->solr_data_store_request->query = $solr_query_string;

            $this->parse_filter();
            $this->parse_commons_params();
            $this->parse_facet();
            $this->parse_stats();

            // Look ma, we have a request for all resources
        } elseif (!empty($this->http_request->action_params['resource_type'])) {
            $this->parse_filter();
            $this->parse_commons_params();
            $this->parse_facet();
            $this->parse_stats();
        } else {
            throw new \Exception('Unable to determine request target for the resource. See ' .
                $this->lc_config['lc_doc_loc'] . ' for documention request targets.');
        }
    }

    /**
     * Some params will be common to many types of requests. Parse here.
     * 
     */
    function parse_commons_params() {
        // Resource, probably item or event
        $this->solr_data_store_request->resource = $this->http_request->action_params['resource_type'];

        // Paging controls:
        if (!empty($this->http_request->params['start'][0])) {
            $this->solr_data_store_request->start = $this->http_request->params['start'][0];
        }

        if (!empty($this->http_request->params['limit'][0]) && $this->http_request->params['limit'][0] <= 1000) {
            $this->solr_data_store_request->rows = $this->http_request->params['limit'][0];
        }

        // Sorting controls:
        if (!empty($this->http_request->params['sort'][0])) {
            $this->solr_data_store_request->params['sort'] = $this->http_request->params['sort'][0];
        }
    }

    /**
     * Filters can be a little tricky. Let's parse them here.
     * 
     * TODO: This method is nasty. Clean it up.
     */
    function parse_filter() {
        if (!empty($this->http_request->params['filter'][0])) {
            $scrubbed_filters = array();
            foreach ($this->http_request->params['filter'] as $filter) {
                preg_match('/^([^:]*:)(.*)$/', $filter, $matches);
                $field = $matches[1];

                // If we have a range filter we don't want to escape the [ TO ]
                // TODO: Should we always be escaping? Not sure...
                if (preg_match('/^\[.+ TO/', $matches[2])) {
                    $value = $matches[2];
                } else {
                    $value = solr\utils::escape_solr_value($matches[2]);
                }
                $scrubbed_filters[] = $field . $value;
            }
            $this->solr_data_store_request->params['fq'] = $scrubbed_filters;
        }
    }

    /**
     * Facet reqeusts are per field reuests. Parse here
     * 
     * TODO: This method is nasty looking too. Clean it up.
     */
    function parse_facet() {
        // TODO: We should probably do some validation here
        // Handle normal faceting. This comes in the form of http://...&facet=langauge
        if (!empty($this->http_request->params['facet'][0])) {
            $facets = array();
            foreach ($this->http_request->params['facet'] as $facet) {
                $this->solr_data_store_request->params['f.' . $facet . '.facet.mincount'] = 1;

                if (!empty($this->http_request->params['facet_limit_' . $facet][0])) {
                    $this->solr_data_store_request->params['f.' . $facet . '.facet.limit'] = $this->http_request->params['facet_limit_' . $facet][0];
                }
            }
            $this->solr_data_store_request->params['facet.field'] = $this->http_request->params['facet'];
            $this->solr_data_store_request->params['facet'] = 'true';
        }

        // Handle facet query faceting. This comes in the form of http://...&facet_query=circ_fac_score:[2 TO *]
        if (!empty($this->http_request->params['facet_query'][0])) {    
            $this->solr_data_store_request->params['facet.query'] = $this->http_request->params['facet_query'];
            $this->solr_data_store_request->params['facet'] = 'true';
        }
    }

    /**
     * Solr can give us stats about numeric fields. Parse here.
     * 
     */
    function parse_stats() {
        // TODO: We should probably do some validation here
        if (!empty($this->http_request->params['stats'][0])) {
            $this->solr_data_store_request->params['stats.field'] = $this->http_request->params['stats'];

            $this->solr_data_store_request->params['stats'] = 'true';
        }
    }

}

?>
