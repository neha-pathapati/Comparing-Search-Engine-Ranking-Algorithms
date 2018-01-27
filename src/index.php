<?php 

// make sure browsers see this page as utf-8 encoded HTML 
header('Content-Type: text/html; charset=utf-8'); 

$limit = 10; 
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false; 
$results = false; 

if ($query) { 
    // The Apache Solr Client library should be on the include path 
    // which is usually most easily accomplished by placing in the 
    // same directory as this script ( . or current directory is a default 
    // php include path entry in the php.ini) 
    require_once('solr-php-client-master/Apache/Solr/Service.php'); 
    
    // create a new solr service instance - host, port, and corename 
    // path (all defaults in this example) 
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/irhw4/'); 
    
    // if magic quotes is enabled then stripslashes will be needed 
    if (get_magic_quotes_gpc() == 1) { 
        $query = stripslashes($query); 
    } 
    
    // in production code you'll always want to use a try /catch for any 
    // possible exceptions emitted by searching (i.e. connection 
    // problems or a query parsing error)
    try { 
        $radio_selected = $_REQUEST['rankingtype'];
        echo("<script>console.log('Rank Algorithm: ".$radio_selected."');</script>"); 
            
        if($_REQUEST['rankingtype']=="pagerank") {
            $param = array('sort' => 'pageRankFile desc');
            $results = $solr->search($query, 0, $limit, $param);
        }
        else
        {
            $results = $solr->search($query, 0, $limit);
        }
    } 
    catch (Exception $e) { 
        // in production you'd probably log or email this error to an admin 
        // and then show a special message to the user but for this example 
        // we're going to show the full exception 
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>"); 
    } 
} 
?>

<html> 
    <head> 
        <title>PHP Solr Client Example</title> 
        <style type="text/css">
            table {
                width: 100%;
            }
            .title_col {
                width: 10%;
            }
            input[type="radio"] {
                display: inline;
            }
        </style>
    </head> 
    <body> 
        <form accept-charset="utf-8" method="get"> 
            <label for="q">Search:</label> 
            <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
            <br>
            <div>
            <label for="rankingtype">Ranking Strategy:
            <input type="radio" name="rankingtype" value="lucene" <?php if(isset($_GET['rankingtype']) && $_GET['rankingtype'] == 'lucene')  echo ' checked="checked"';?> > Lucene Results
            <input type="radio" name="rankingtype" value="pagerank" <?php if(isset($_GET['rankingtype']) && $_GET['rankingtype'] == 'pagerank')  echo ' checked="checked"';?> > Page Rank Results
            </label>
            </div>
            <input type="submit"/> 
                
        </form> 
        
        <?php
        // display results 
        if ($results) 
        { 
            // Load the CSV map file
            $map = array();
            $csv_file = fopen('/Users/nehapathapati/Sites/Boston_Global_Map.csv', 'r');
            while ($line = fgetcsv($csv_file))
            {
                $key = array_shift($line);
                $map[$key] = $line;
            }
            fclose($csv_file);

            // Process results
            $total = (int) $results->response->numFound; 
            $start = min(1, $total); 
            $end = min($limit, $total); 
        ?> 
              
        <ol> 
            <?php  
            foreach ($results->response->docs as $doc) { 
                $display_data = array();
                foreach ($doc as $field => $value) {
                    if ($field == "id" || $field == "description" || $field == "title" || $field == "og_url") {
                        $display_data[$field] = $value;
                    }
                }
                ?>
            <li> 
                <table style="border: 1px solid black; text-align: left">  
                    <!-- Title -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("Title", ENT_NOQUOTES, 'utf-8'); ?></th>
                        <td><?php echo htmlspecialchars($display_data["title"], ENT_NOQUOTES, 'utf-8'); ?></td>
                    </tr>
                    <!-- URL -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("URL", ENT_NOQUOTES, 'utf-8'); ?></th>
                        <?php if ($display_data["og_url"]) { ?>
                        <td><a target="_blank" href="<?php echo htmlspecialchars($display_data["og_url"], ENT_NOQUOTES, 'utf-8'); ?>"><?php echo htmlspecialchars($display_data["og_url"], ENT_NOQUOTES, 'utf-8'); ?></a></td>
                        <?php } 
                        else { 
                            $id = $display_data["id"];
                            $base_id = substr($id, strrpos($id, '/') + 1);
                            echo('<script>console.log("'.$base_id.'");</script>');
                            $url = $map[$base_id][0];
                            ?>
                           <td><a target="_blank" href="<?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?>"><?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?></a></td>
                        <?php } ?>
                    </tr>
                    <!-- ID -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("ID", ENT_NOQUOTES, 'utf-8'); ?></th>
                        <td><?php echo htmlspecialchars($display_data["id"], ENT_NOQUOTES, 'utf-8'); ?></td>
                    </tr>
                    <!-- DESCRIPTION -->
                    <tr>
                        <th class="title_col"><?php echo htmlspecialchars("DESCRIPTION", ENT_NOQUOTES, 'utf-8'); ?></th>   
                        <?php if ($display_data["description"]) { ?>
                        <td><?php echo htmlspecialchars($display_data["description"], ENT_NOQUOTES, 'utf-8'); ?></td>
                        <?php } 
                        else { ?>
                        <td><?php echo htmlspecialchars("NA", ENT_NOQUOTES, 'utf-8'); ?></td>
                        <?php } ?>
    
                    </tr>
                </table> 
            </li> 
        <?php } ?>  
     </ol> 
     <?php } ?> 
    </body> 
</html>