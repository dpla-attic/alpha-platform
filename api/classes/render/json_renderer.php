<?php 

namespace org\librarycloud\api\render;

require_once LC_HOME . 'api/classes/render/renderer_interface.php';

/**
 * We need to render the results from the datastore (Solr) into something that 
 * is consumable by the client. Here, we house the logic to render JSON
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
 */

class json_renderer implements renderer_interface {

    /**
     * We should have a set of results (and maybe some errors). Let's send them
     * to the user
     *
     * @param http_request $http_request a container holding the details of the HTTP request
     * @param array $raw_results a nested array of solr docs, solr stats, etc.
     * @param array $errors an array of errors we found along the way
     */
    static function render($http_request, $raw_results, $errors) {
        $raw_results['errors'] = $errors;
        header('Content-type: application/json');

        // Do we have a JSONP call? If so, let's echo the callback out
        if (!empty($http_request->params['callback'][0])) {
            echo $http_request->params['callback'][0] . '(' . json_encode($raw_results) . ')';
        } else {
            echo json_encode($raw_results);
        }
    }
}

?>