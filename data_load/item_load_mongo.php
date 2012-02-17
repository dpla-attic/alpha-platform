<?php

namespace org\librarycloud\data_load;

define('LC_HOME', dirname(dirname(__FILE__)).'/' );
require_once LC_HOME . 'lib/SolrPhpClient/Apache/Solr/Service.php';

$lc_config = parse_ini_file(LC_HOME . 'etc/data_load_mongo.ini');

// Call our function that does the heavy lifting
index_items();

// Connect to the mongo datastore and toss thigngs at Solr
function index_items() {

    // If things are multivalued, let's declare here
    $multi_valued_fields = array('creator', 'description', 'subject', 'call_num',
                                 'id_isbn', 'content_link', 'relation', 'rights');

    // Let's report some basic load times
    $start_time = time();

    echo "Throwing a crapload of records at Solr. Hold tight...\n";

    global $lc_config;
    // TODO: Parameterize
    $solr = new \Apache_Solr_Service($lc_config['solr_host'], $lc_config['solr_port'], $lc_config['solr_path']);
    if ( ! $solr->ping() ) {
        echo 'Solr service not responding';
        exit;
    } else {
        echo "Connected to Solr...\n";
    }

    // TODO: Parameterize
    $m = new \Mongo($lc_config['mongo_connection']);
    $db = $m->selectDB($lc_config['mongo_db']);
    $collection = $db->selectCollection($lc_config['mongo_collection']);
    $cursor = $collection->find()->batchSize(400);
    
    foreach ($cursor as $obj) {
        $solr_document = new \Apache_Solr_Document();

        foreach(array_keys($obj) as $key) {
            if ($key != '_id') {
                if (in_array($key, $multi_valued_fields) || preg_match("/^dark_/i", $key)) {
                    add_value($solr_document, $key, $obj[$key], 'multi');
                } else {
                    add_value($solr_document, $key, $obj[$key], 'single');
                }
            }
        }

        $solr_documents[] = $solr_document;
    }

    // Send the docs to Solr
    try {
        $solr->addDocuments($solr_documents);
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit();
    }

    $solr->commit();
    $solr->optimize();

    echo "About " . $collection->count() . " documents indexed in a total of " . (time() - $start_time) . " seconds.\n";
}

// Some local helper methods:
function add_value($document, $field_key, $field_value, $size) {
    // if the field value is empty
    if (empty($field_value) || $field_value == 'NULL') {
        return;
    }

    // Some multi-valued values are stored in one field in MySQL, separated with
    // a %% delimiter, if we find one of multi-valued values, explode
    // and add the value to the document as an array
    if ($size == 'single') {
        $document->$field_key = $field_value;
    } else {
        $values = explode('%%', $field_value);
        $deduped_values = array_unique($values);

        foreach ($deduped_values as $value) {
            $document->addField($field_key, $value);
        }
    }
}

?>