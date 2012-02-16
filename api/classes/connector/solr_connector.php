<?php

namespace org\librarycloud\api\connector;

require_once LC_HOME . 'api/classes/connector/connector_interface.php';
require_once LC_HOME . 'lib/SolrPhpClient/Apache/Solr/Service.php';

/**
 * This class handles connection, request, and results with Solr.
 * This is mostly a wrapper for the SolrPHPClient lib.
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
 */

class solr_connector {

    public $solr_data_store_request;
    public $solr_results;
    public $lc_config;

    /**
     * We'll need some data in this class. Get it here
     *
     * @param solr_data_store_request $solr_data_store_request a container object that holds the request we'll send to Solr
     * @param lc_config $lc_config a container holding the details of our config
     */
    function __construct($solr_data_store_request, $lc_config) {
        $this->solr_data_store_request = $solr_data_store_request;
        $this->lc_config = $lc_config;
    }

    /**
     * Send the reqeust to Solr
     *
     * @throws Exception if we can't reach solr or if something goes wrong, raise it here
     */
    function dispatch_request() {
        $host = $this->lc_config['solr_host'];
        $port = $this->lc_config['solr_port'];
        $path = $this->lc_config['solr_path'];
        
        try {
            $solr = new \Apache_Solr_Service($host, $port, $path);
            $this->solr_results = $solr->search($this->solr_data_store_request->query, $this->solr_data_store_request->start, $this->solr_data_store_request->rows, $this->solr_data_store_request->params);

        }
        catch (\Exception $e) {
            throw new \Exception('This is a bummer, man. An error was encountered while processing your request. ' .
            'See ' . $this->lc_config['lc_doc_loc'] . ' for examples of well formed requests.');
        }
    }
}

?>