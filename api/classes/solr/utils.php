<?php

namespace org\librarycloud\api\solr;

/**
 * Dumping ground for Solr utils
 *
 * @author     Matt Phillips <mphillips@law.harvard.edu>
 * @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
 */

class utils {

    /**
     * Let's cleanup a string so that it can be sent to Solr
     * 
     * Taken directly from http://e-mats.org/2010/01/escaping-characters-in-a-solr-query-solr-url/
     *
     * @param string $to_be_escaped a string that might contian characters that are not Solr frieldy
     * @return string a string only containing solr friedly characters
     */
    static function escape_solr_value($to_be_escaped) {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
        $to_be_escaped = str_replace($match, $replace, $to_be_escaped);

        return $to_be_escaped;
    }
}

?>