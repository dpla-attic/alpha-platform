<?php

namespace org\librarycloud\api\render;

/**
 * This interface defines the shape of our renderers
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
 */

interface renderer_interface {

    /**
     * We should have a set of results (and maybe some errors). Let's send them
     * to the user.
     *
     * @param http_request $http_request a container holding the details of the HTTP request
     * @param array $raw_results a nested array of solr docs, solr stats, etc.
     * @param array $errors an array of errors we found along the way
     */
    static function render($http_request, $raw_results, $errors);
}

?>