<?php

namespace org\librarycloud\api\request;

/**
 * This is a container object for our Solr data store request
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
 */

class solr_data_store_request {

    public $resource;
    public $query;
    public $params = array();
    public $start;
    public $rows;
}

?>