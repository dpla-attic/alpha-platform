<?php

namespace org\librarycloud\api\connector;

/**
 * This interface defines the form of connectors in LibraryCloud
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License
 */

interface connector_interface {

    /**
     * Send the dispatch to our datastore
     */
    function dispatch_request();
}

?>