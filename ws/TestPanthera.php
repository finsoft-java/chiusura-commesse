<?php

include("include/all.php");


/*define('DB_PTH_HOST', '172.18.0.15\\PANTH01');
define('DB_PTH_USER', 'finsoft');
define('DB_PTH_PASS', 'Th3R4$0FtTh!p');
define('DB_PTH_PASS', 't3st-f1N$');
define('DB_PTH_NAME', 'PANTH01');
*/

$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}
    
// DO NOT require_logged_user_JWT();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //==========================================================



$panthera->execute_update("SET ANSI_WARNINGS OFF");

    $query_exec = "
INSERT INTO.CM_DOC_TRASF
  
";
	//$panthera->execute_update($query_exec);

    $query = "
SELECT * from INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'THIP' ORDER BY TABLE_TYPE, TABLE_NAME
--SELECT TOP 100 * from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'THIP' and TABLE_NAME='SALDI_UBICAZIONE_V01'

                    
";
//echo $query;die();
    $result = $panthera->select_list($query);

    //header('Content-Type: application/json');
    //echo json_encode(['data' => $result]);
	echo "<html>" . print_query_html($result) . "</html>";

} else {
    //==========================================================
    print_error(400, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}

?>