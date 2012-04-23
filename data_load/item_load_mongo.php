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
            if ($dpla_key == 'title') {
                parse_title($solr_document, $dpla_value);
            }
            if ($dpla_key == 'publisher') {
                parse_publisher($solr_document, $dpla_value);
            }
            if ($dpla_key == 'language') {
                parse_language($solr_document, $dpla_value);
            }
            if ($dpla_key == 'resource_type') {
                parse_resource_type($solr_document, $dpla_value);
            }
            if ($dpla_key == 'dpla_id') {
                parse_dpla_id($solr_document, $dpla_value);
            }
            if ($dpla_key == 'contributor') {
                parse_contributor($solr_document, $dpla_value);
            }
            if ($dpla_key == 'dataset_id') {
                parse_dataset_id($solr_document, $dpla_value);
            }
            if ($dpla_key == 'creator') {
                parse_creator($solr_document, $dpla_value);
            }
            if ($dpla_key == 'subject') {
                parse_subject($solr_document, $dpla_value);
            }
            if ($dpla_key == 'description') {
                parse_description($solr_document, $dpla_value);
            }
            if ($dpla_key == 'date') {
                parse_date($solr_document, $dpla_value);
            }
            if ($dpla_key == 'format') {
                parse_format($solr_document, $dpla_value);
            }
            if ($dpla_key == 'call_number') {
                parse_call_number($solr_document, $dpla_value);
            }
            if ($dpla_key == 'identifier') {
                parse_identifier($solr_document, $dpla_value);
            }
        }
        
        // Process the local data
        // This is about the ugliest thing I've ever seen.
        foreach ($obj['local'] as $local_key => $local_value) {
            if (gettype($local_value) == 'array'){
                foreach ($local_value as $field_name => $field_ja){
                    foreach ($field_ja as $a => $b) {
                        if (!empty($b['subfields']) && gettype($b['subfields']) == 'array') {
                            foreach ($b['subfields'] as $subfield_det) {
                                foreach($subfield_det as $code => $det) {
                                    $solr_document->addField("$a$code", $det);
                                }
                            }
                        }
                    }
                }
            }
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

// Given a title object, add it to a solr doc
function parse_title($document, $title) {
    if (!empty($title) && $title != 'NULL' && $title != 'n/a') {
        $document->addField('dpla.title', $title);
        //print "\ndpla.title = $title \n";
    }
}

// Given a publisher object, add it to a solr doc
function parse_publisher($document, $publisher) {
    if (!empty($publisher) && $publisher != 'NULL' && $publisher != 'n/a') {
        $document->addField('dpla.publisher', $publisher);
        //print "\ndpla.publisher = $publisher \n";
    }
}

// Given a language object, add it to a solr doc
function parse_language($document, $language) {
    if (!empty($language) && $language != 'NULL' && $language != 'n/a') {
        $document->addField('dpla.language', $language);
        //print "\ndpla.language = $language \n";
    }
}

// Given a resource_type object, add it to a solr doc
function parse_resource_type($document, $resource_type) {
    if (!empty($resource_type) && $resource_type != 'NULL' && $resource_type != 'n/a') {
        $document->addField('dpla.resource_type', $resource_type);
        //print "\ndpla.resource_type = $resource_type \n";
    }
}

// Given a dpla_id object, add it to a solr doc
function parse_dpla_id($document, $dpla_id) {
    if (!empty($dpla_id) && $dpla_id != 'NULL' && $dpla_id != 'n/a') {
        $document->addField('dpla.id', $dpla_id);
        //print "\ndpla.id = $dpla_id \n";
    }
}

// Given a contributor object, add it to a solr doc
function parse_contributor($document, $contributor) {
    if (!empty($contributor['name']) && $contributor['name'] != 'NULL' && $contributor['name'] != 'n/a') {
        $document->addField('dpla.contributor', $contributor['name']);
        //print "\ndpla.contributor = {$contributor['name']} \n";
    }
}

// Given a dataset_id array, add it to a solr doc
function parse_dataset_id($document, $dataset_ids) {
    foreach ($dataset_ids as $dataset_id) {
        if (!empty($dataset_id) && $dataset_id != 'NULL' && $dataset_id != 'n/a') {
            $document->addField('dpla.dataset_id', $dataset_id);
            //print "\ndpla.dataset_id = $dataset_id \n";
        }
    }
}

// Given a creator object, add it to a solr doc
function parse_creator($document, $creator) {
    foreach ($creator as $key => $value) {
        foreach ($value as $type => $name) {
            if ($type == 'name' && !empty($name) && $name != 'NULL' && $name != 'n/a') {
                $document->addField('dpla.creator', $name);
                //print "\ndpla.creator = $name \n\n";
            }
        }
    }
}

// Given a subject array, add it to a solr doc
function parse_subject($document, $subjects) {
    foreach ($subjects as $subject) {
        if (!empty($subject) && $subject != 'NULL' && $subject != 'n/a') {
            $document->addField('dpla.subject', $subject);
            //print "\ndpla.subject = $subject \n";
        }
    }
}

// Given a description array, add it to a solr doc
function parse_description($document, $descs) {
    foreach ($descs as $desc) {
        if (!empty($desc) && $desc != 'NULL' && $desc != 'n/a') {
            $document->addField('dpla.description', $desc);
            //print "\ndpla.description = $desc \n";
        }
    }
}

// Given a date object, add it to a solr doc
function parse_date($document, $date) {
    foreach ($date as $key => $value) {
        foreach ($value as $type => $expression) {
            if ($type == 'expression' && !empty($expression) && $expression != 'NULL' && $expression != 'n/a') {
                $document->addField('dpla.date', $expression);
                //print "\ndpla.date = $expression \n\n";
            }
        }
    }
}

// Given a format object, add it to a solr doc
function parse_format($document, $format) {
    foreach ($format as $key => $value) {
        foreach ($value as $type => $type_value) {
            if (!empty($type_value) && $type_value != 'NULL' && $type_value != 'n/a') {
                $document->addField('dpla.format', $type_value);
                //print "\ndpla.format = $type_value \n\n";
            }
        }
    }
}

// Given a call number object, add it to a solr doc
function parse_call_number($document, $call_num) {
    foreach ($call_num as $key => $value) {
        foreach ($value as $type => $type_value) {
            if ($type_value == 'value' && !empty($type_value) && $type_value != 'NULL' && $type_value != 'n/a') {
                $document->addField('dpla.call_num', $type_value);
                //print "\ndpla.call_num = $type_value \n\n";
            }
        }
    }
}

// Given an identifier object, add it to a solr doc
function parse_identifier($document, $identifiers) {
    foreach ($identifiers as $key => $value) {
        if (!empty($value['type']) && !empty($value['id']) && $value['id'] != 'NULL' && $value['id'] != 'n/a') {
            if ($value['type'] == 'ISBN') {
                $document->addField('dpla.isbn', $value['id']);
                //print "\ndpla.isbn = {$value['id']} \n\n";
            }
            if ($value['type'] == 'LCCN') {
                $document->addField('dpla.lccn', $value['id']);
                //print "\ndpla.lccn = {$value['id']} \n\n";
            }
            if ($value['type'] == 'OCLC') {
                $document->addField('dpla.oclc', $value['id']);
                //print "\ndpla.oclc = {$value['id']} \n\n";
            }
        }
    }
}
