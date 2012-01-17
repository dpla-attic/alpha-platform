<?php

namespace org\librarycloud\api\manager;

require_once LC_HOME . 'api/classes/validation/http_validator.php';
require_once LC_HOME . 'api/classes/translator/solr_request_translator.php';
require_once LC_HOME . 'api/classes/connector/solr_connector.php';
require_once LC_HOME . 'api/classes/translator/solr_response_translator.php';
require_once LC_HOME . 'api/classes/render/json_renderer.php';
require_once LC_HOME . 'api/classes/config/config.php';

use org\librarycloud\api\render as render;
use org\librarycloud\api\config as config;
use org\librarycloud\api\validation as validation;
use org\librarycloud\api\connector as connector;
use org\librarycloud\api\translator as translator;

/**
* The logic contained in this class organizes the flow of the request
* and the associated response though LibraryCloud. This is master controller
*
* @author     Matt Phillips <mphillips@law.harvard.edu>
* @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
*/

class flow_manager {

    public $http_request;
    public $data_store_request;
    public $data_store_response;
    public $results;
    public $errors = array();
    public $lc_config;

    /**
     * Turn the HTTP request into a Solr response.
     *
     * @param http_request $http_request a container holding the details of the HTTP request
     * @throws Exception a handful of different exceptions (TODO: cleanup this exception silliness)
     */
    function push_the_power_button($http_request) { 
        $this->http_request = $http_request;

        // We have different configs for different
        // resource types (item, event). Build the config here.
        try {
            $config = new config\config($this->http_request);
            $this->lc_config = $config->get_config();
        } catch (\Exception $e) {
            header("HTTP/1.0 400 Bad Request");
            header('Content-type: application/json');
            $this->add_error($e->getMessage());
            $this->render();
            exit;
        }

        //Does the request contain paramaters that don't make sense?
        //if so, let's alert the user and point them to the doc
        try {
            $http_validator = new validation\http_validator($this->http_request, $this->lc_config);
            $http_validator->validate();
        } catch (\Exception $e) {
            header("HTTP/1.0 400 Bad Request");
            header('Content-type: application/json');
            $this->add_error($e->getMessage());
            $this->render();
            exit;
        }

        // Translate the http request to something that makes sense to solr
        try {
            $request_translator = new translator\solr_request_translator($this->http_request, $this->lc_config);
            $request_translator->translate();
            $this->data_store_request = $request_translator->solr_data_store_request;
        } catch (\Exception $e) {
            header("HTTP/1.0 400 Bad Request");
            header('Content-type: application/json');
            $this->add_error($e->getMessage());
            $this->render();
            exit;
        }

        // Send the request to Solr
        try {
            $solr_connector = new connector\solr_connector($this->data_store_request, $this->lc_config);
            $solr_connector->dispatch_request();
            $this->data_store_response = $solr_connector->solr_results;
        } catch (\Exception $e) {
            header("HTTP/1.0 500 Internal Server Error");
            header('Content-type: application/json');
            $this->add_error($e->getMessage());
            $this->render();
            exit;
        }

        // Translate the Solr response to the LibraryCloud data model
        // TODO: Should we be looking for exceptions here?
        $solr_response_translator = new translator\solr_response_translator($this->data_store_response, $this->lc_config);
        $solr_response_translator->translate();
        $this->results = $solr_response_translator->results;

        // Serialize the response out to the user
        $this->render();
    }

    // Write response, probably in json form, from results
    function render(){
        // TODO: we probably want to check the headers for content-accept
        render\json_renderer::render($this->http_request, $this->results, $this->errors);
    }

    // Helper method for managing error messages
    function add_error($error_string) {
        array_push($this->errors, $error_string);
    }
}

?>