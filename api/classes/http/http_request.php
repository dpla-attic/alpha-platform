<?php

namespace org\librarycloud\api\http;

/**
 * A container object to house our incoming HTTP request
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License
 */
 
class http_request {

    public $uri;
    public $method;

    // List of parameters, all are multivalued (this is an array or arrays)
    public $params = array();

    // We're trying to match anything after librarycloud.org/api/v.nn/
    // Example: his would look like ['resource_type' => item, 'resource_id' => 23432] for 
    // http://localhost/librarycloud/api/v.2/item/23432?search_type=keyword&query=literature
    public $action_params = array();
    public $headers = array();

    /**
     * We'll need some data in this class. Get it here.
     *
     * @param array $server_vars this is the PHP $_SERVER reserved var, http://php.net/manual/en/reserved.variables.server.php
     */
    function __construct($server_vars) {
        $this->method = $server_vars['REQUEST_METHOD'];
        $this->uri = $server_vars['REQUEST_URI'];
        $this->build_params();
        $this->build_action_params();
        $this->build_headers($server_vars);
    }

    /**
     * Get a list of all http params (everything after the ? in the URI)
     */
    function build_params() {
        preg_match('/\?(.*)/', $this->uri, $matches);

        if (!empty($matches)) {
            $param_pairs  = explode('&', $matches[1]);
            foreach ($param_pairs as $param) {
                list($key, $value) = explode('=', $param);
                $params[urldecode($key)][] = urldecode($value);
            }
            $this->params = $params;
        }
    }

    /**
     * Get the action parameter (the thing before the ?)
     * In the following example, item is the action param:
     * http://host.com/librarycloud/v.3/api/item?otherparams=value
     */
    function build_action_params() {
        //preg_match('/\/[^\/]+\/api\/([^?]*)/', $this->uri, $matches);
        preg_match('/\/[^\/]+\/([^?]*)/', $this->uri, $matches);
        $path_params = explode('/', $matches[1]);

        // This is probably item or event
        if (!empty($path_params[0])) {
            $this->action_params['resource_type'] = $path_params[0];
        }

        // This is the UUID of the item or event
        if (!empty($path_params[1])) {
            $this->action_params['resource_id'] = $path_params[1];
        }
    }

    /**
     * Add all headers to our http object
     *
     * @param array $server_vars this is the PHP $_SERVER reserved var, http://php.net/manual/en/reserved.variables.server.php
     */
    function build_headers($headers) {
        foreach ($headers as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $key = str_replace('_', ' ', substr($key, 5));
                $key = str_replace(' ', '-', ucwords(strtolower($key)));
                $this->headers[$key] = $value;
            }
        }
    }
}
