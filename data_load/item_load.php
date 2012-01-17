<?php

namespace org\librarycloud\data_load;

/**
* This script loads the RDB staged item data into Solr. We 
* should be doing little to no massaging of the data at this point.
*
* @author     Matt Phillips <mphillips@law.harvard.edu>
* @license    http://www.gnu.org/licenses/lgpl.txt GNU Lesser Public License
*/

define('LC_HOME', dirname(dirname(__FILE__)).'/' );
$lc_config = parse_ini_file(LC_HOME . 'etc/data_load.ini');

require_once LC_HOME . 'lib/SolrPhpClient/Apache/Solr/Service.php';
require_once LC_HOME . 'data_load/classes/util.php';

use org\librarycloud\data_load\utils as utils;


// Let's report some basic load times
$start_time = time();

echo "Throwing a crapload of records at Solr. Hold tight...\n";

// Get the total number of rows in our DB so that we can have some 
// pagination controls
try {
    $dbh = new \PDO("mysql:host=" . $lc_config['load_mysql_host'] .";dbname=" . 
        $lc_config['load_mysql_db_name'], $lc_config['load_mysql_uid'], 
        $lc_config['load_mysql_pass'], 
        array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    $dbh->setAttribute (\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $rows = $dbh->query('select count(record_id) from item');
    $total_rows = $rows->fetchColumn();
} catch(\PDOException $e) {
    echo 'Error connecting to MySQL!: ' .$e->getMessage();
} catch(\Exception $e) {
    echo 'DB exception: ' . $e->getMessage();
    exit();
}

// Setup our pagination controls
$limit = 200;
$num_pages = ceil($total_rows / $limit);

// Get the our start record
try {
    $rows = $dbh->query('select min(record_id) from item');
    $min = $rows->fetchColumn();
} catch(\Exception $e) {
    echo 'DB exception: ' . $e->getMessage();
    exit();
}

$start = $min;
$end = $start + $limit;

// These numbers aren't right if we have gaps between the first and the last records:
echo "Found a total of $total_rows to index. Breaking into $num_pages pages, starting at record id $start...\n";

$solr = new \Apache_Solr_Service($lc_config['solr_host_item'], $lc_config['solr_port_item'], $lc_config['solr_path_item']);
if ( ! $solr->ping() ) {
    echo 'Solr service not responding';
    exit;
} else {
    echo "Connected to Solr...\n";
}

// We'll paginate over all records in our DB
for ($i = 0; $i <= $num_pages; $i++) {
    $sql = "SELECT * 
        FROM item i
        WHERE i.record_id >= $start AND i.record_id <= $end";

    try {
        $sth = $dbh->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll();
    } catch(\PDOException $e) {
        echo $e->getMessage();
        exit();
    }

    // The docs we'll send to Solr
    $documents = array();

    // We'll loop through each set of rows in our page and create a Solr
    // out of each one
    foreach ($rows as $row) {
        $document = new \Apache_Solr_Document();

        add_value($document, 'id', $row['id'], 'single');
        add_value($document, 'title', $row['title'], 'single');
        add_value($document, 'title_sort', $row['title_sort'], 'single');
        add_value($document, 'creator', $row['creator'], 'multi');
        add_value($document, 'publisher', $row['publisher'], 'single');
        add_value($document, 'date', $row['date'], 'single');
        add_value($document, 'format', $row['format'], 'single');
        add_value($document, 'language', $row['language'], 'single');
        add_value($document, 'page_count', $row['page_count'], 'single');
        add_value($document, 'height', $row['height'], 'single');
        add_value($document, 'description', $row['description'], 'multi');
        add_value($document, 'subject', $row['subject'], 'multi');
        add_value($document, 'call_num', $row['call_num'], 'multi');
        add_value($document, 'id_inst', $row['id_inst'], 'single');
        add_value($document, 'id_isbn', $row['id_isbn'], 'multi');
        add_value($document, 'id_lccn', $row['id_lccn'], 'single');
        add_value($document, 'id_oclc', $row['id_oclc'], 'single');    
        add_value($document, 'content_link', $row['content_link'], 'multi');
        add_value($document, 'relation', $row['relation'], 'multi');
        add_value($document, 'rights', $row['rights'], 'multi');
        add_value($document, 'checkouts', $row['checkouts'], 'single');
        add_value($document, 'data_source', $row['data_source'], 'single');
        add_value($document, 'dataset_tag', $row['dataset_tag'], 'single');
        add_value($document, 'resource_type', $row['resource_type'], 'single');

        $documents[] = $document;
    }

    // Send the docs to Solr
    try {
        $solr->addDocuments( $documents );
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit();
    }

    // Print out an update
    if (($end % 100000) == 0 && $end != 0) {
        echo "Working hard. I've inserted a total of $end records so far\n";
    }

    // Adjust our pagination values
    $start = $end;
    $end += $limit;
}

// Close the database connection
$dbh = null;

echo "About $total_rows documents indexed in a total of " . (time() - $start_time) . " seconds.\n";
$solr->commit();
$solr->optimize();

echo "Finished in " . time() - $start_time . " seconds.";

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
