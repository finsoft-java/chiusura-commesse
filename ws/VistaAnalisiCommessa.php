<?php

include("include/all.php");
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$codCommessa = isset($_GET['codCommessa']) ? $panthera->escape_string($_GET['codCommessa']) : null;
$aggregato = isset($_GET['aggregato']) ? $panthera->escape_string($_GET['aggregato']) : false;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($codCommessa == null) {
        print_error(400, "Missing codCommessa");
    }
    if (!$aggregato) {
        [$list, $count] = $saldiManager->getVistaAnalisiCommessa($codCommessa);
    } else {
        [$list, $count] = $saldiManager->getVistaAnalisiCommessaAggregata($codCommessa);
    }
        
    header('Content-Type: application/json');
    echo json_encode(['data' => $list, 'count' => $count]);
    
} else {
    //==========================================================
    print_error(405, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>