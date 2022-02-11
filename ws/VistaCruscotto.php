<?php

include("include/all.php");
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$codCommessa = isset($_GET['codCommessa']) ? $panthera->escape_string($_GET['codCommessa']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($codCommessa == null) {
        [$list, $count] = $panthera->getVistaCruscotto();
          
        header('Content-Type: application/json');
        echo json_encode(['data' => $list, 'count' => $count]);

    } else {
        [$list, $count] = $panthera->getVistaCruscotto($codCommessa);
        if ($count == 0) {
            print_error(404, "Commessa non trovata: $codCommessa");
        }
          
        header('Content-Type: application/json');
        echo json_encode(['value' => $list[0]]);
    }
    
} else {
    //==========================================================
    print_error(405, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>