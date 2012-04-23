<?php

namespace org\librarycloud\data_load;

ini_set("memory_limit","900M");

define('LC_HOME', dirname(dirname(__FILE__)).'/' );
require_once LC_HOME . 'lib/SolrPhpClient/Apache/Solr/Service.php';

$lc_config = parse_ini_file(LC_HOME . 'etc/data_load_mongo.ini');

// Call our function that does the heavy lifting
index_items();

// Connect to the mongo datastore and toss things at Solr
function index_items() {

    // Let's report some basic load times
    $start_time = time();

    echo "Throwing a crapload of records at Solr. Hold tight...\n";

    global $lc_config;
    
    $solr = new \Apache_Solr_Service($lc_config['solr_host'], $lc_config['solr_port'], $lc_config['solr_path']);
    if ( ! $solr->ping() ) {
        echo 'Solr service not responding';
        exit;
    } else {
        echo "Connected to Solr...\n";
    }

    $m = new \Mongo($lc_config['mongo_connection']);
    $db = $m->selectDB($lc_config['mongo_db']);
    $collection = $db->selectCollection($lc_config['mongo_collection']);
    $cursor = $collection->find()->batchSize(400);
    
    $count = 0;
    foreach ($cursor as $obj) {

        $count++;

        if ($count % 1000 == 0) {
            echo "now processing document no. $count\n";
        }

        $solr_document = new \Apache_Solr_Document();

        // Process our mapped terms
        foreach ($obj['dpla'] as $dpla_key => $dpla_value) {
            if ($dpla_key == 'name') {
                parse_name($solr_document, $dpla_value);
            }
            if ($dpla_key == 'url') {
                parse_dpla_url($solr_document, $dpla_value);
            }
            if ($dpla_key == 'dpla_id') {
                parse_dpla_id($solr_document, $dpla_value);
            }
            if ($dpla_key == 'type') {
                parse_type($solr_document, $dpla_value);
            }
            if ($dpla_key == 'contributor') {
                parse_contributors($solr_document, $dpla_value);
            }
            if ($dpla_key == 'dataset_id') {
                parse_dataset_id($solr_document, $dpla_value);
            }
            if ($dpla_key == 'location') {
                parse_location($solr_document, $dpla_value);
            }
        }
        
        // Process the local data
        if ($obj['local']) {
            parse_local($solr_document, $obj['local']); 
        } 
   
        $solr_documents[] = $solr_document;
		// Send the docs to Solr
	    try {
	        $solr->addDocuments($solr_documents);
	    } catch (\Exception $e) {
	        echo $e->getMessage();
	        exit();
	    }
	    
	    // Release memory
        unset ($solr_documents);
    }
		
    $solr->commit();
    $solr->optimize();

    echo "About " . $collection->count() . " documents indexed in a total of " . (time() - $start_time) . " seconds.\n";
}

/////////
// A whole bunch of helper functions to parse the fields
// TODO: generalize a whole bunch of the mess below
/////////

// Given a name object, add it to a solr doc
function parse_name($document, $name) {
    if (!empty($name) && $name!= 'NULL' && $name!= 'n/a') {
        $document->addField('dpla.name', $name);
        //print "\ndpla.name = $name \n";
    }
}

// Given a url object, add it to a solr doc
function parse_dpla_url($document, $url) {
    if (!empty($url) && $url != 'NULL' && $url!= 'n/a') {
        $document->addField('dpla.url', $url);
        //print "\ndpla.url = $url \n";
    }
}

// Given a dpla_id object, add it to a solr doc
function parse_dpla_id($document, $dpla_id) {
    if (!empty($dpla_id) && $dpla_id != 'NULL' && $dpla_id != 'n/a') {
        $document->addField('dpla.id', $dpla_id);
        //print "\ndpla.id = $dpla_id \n";
    }   
}

// Given a type object, add it to a solr doc
function parse_type($document, $type) {
    if (!empty($type) && $type!= 'NULL' && $type!= 'n/a') {
        $document->addField('dpla.type', $type);
        //print "\ndpla.type = $type \n";
    }   
}

// Given an contributor object, add it to a solr doc
function parse_contributors($document, $contributors) {
    foreach ($contributors as $key => $value) {
        if ($key == 'name') {
             $document->addField('dpla.contributor', $value);
             //print "\ndpla.contributor = {$value} \n\n";
        }
    }
}

// Given an dataset_id list, add it to a solr doc
function parse_dataset_id($document, $dataset_ids) {
    foreach ($dataset_ids as $dataset_id) {
        if (!empty($dataset_id)) {
             $document->addField('dpla.dataset_id', $dataset_id);
             //print "\ndpla.dataset_id = {$dataset_id} \n\n";
        }
    }
}

// Given an location object, add it to a solr doc
function parse_location($document, $locations) {
    foreach($locations as $location){
        if (!empty($location['address'])) {
            if (!empty($location['address']['street'])) {
                $document->addField('dpla.location.address.street', $location['address']['street']);
                //print "dpla.location.address.street = {$location['address']['street']}\n";
            }
            if (!empty($location['address']['city'])) {
                $document->addField('dpla.location.address.city', $location['address']['city']);
                //print "dpla.location.address.city = {$location['address']['city']}\n";
            }
            if (!empty($location['address']['state'])) {
                $document->addField('dpla.location.address.state', $location['address']['state']);
                //print "dpla.location.address.state = {$location['address']['state']}\n";
            }
            if (!empty($location['address']['country'])) {
                $document->addField('dpla.location.address.country', $location['address']['country']);
                //print "dpla.location.address.country= {$location['address']['country']}\n";
            }
            if (!empty($location['address']['zip'])) {
                $document->addField('dpla.location.address.zip', $location['address']['zip']);
                //print "dpla.location.address.zip= {$location['address']['zip']}\n";
            }
        }
        if (!empty($location['geocoords'])) {
            if (!empty($location['geocoords']['lat'])) {
                $document->addField('dpla.location.geocoords.lat', $location['geocoords']['lat']);
                //print "dpla.location.geocoords.lat = {$location['geocoords']['lat']}\n";
            }
            if (!empty($location['geocoords']['lon'])) {
                $document->addField('dpla.location.geocoords.lon', $location['geocoords']['lon']);
                //print "dpla.location.geocoords.lon= {$location['geocoords']['lon']}\n";
            }
        }
    }
}

// Given an local object, add it to a solr doc
function parse_local($document, $local) {
    foreach ($local as $key => $val) {
        if (!empty($val)) {
             $document->addField($key, $val);
             //print "\n$key = {$val} \n";
        }
    }
}
