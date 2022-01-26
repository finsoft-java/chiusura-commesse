<?php

include("include/all.php");
$panthera->connect();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //do nothing, HTTP 200
    exit();
}

require_logged_user_JWT();

$codCommessa = isset($_GET['codCommessa']) ? $panthera->escape_string($_GET['codCommessa']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($codCommessa == null) {
        print_error(400, "Missing codCommessa");
    }
    [$list, $count] = $panthera->avanzamentoWorkflow($codCommessa);
    
} else {
    //==========================================================
    print_error(405, "Unsupported method in request: " . $_SERVER['REQUEST_METHOD']);
}


?>