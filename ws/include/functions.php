<?php 
use Firebase\JWT\JWT;

function connect() {
  $connect = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (mysqli_connect_errno()) {
    die("Failed to connect:" . mysqli_connect_error());
  }
  mysqli_set_charset($connect, "utf8");
  mysqli_query($connect, "SET lc_messages = 'it_IT';");
  
  return $connect;
}

function require_logged_user() {
    global $logged_user;
    $logged_user = $_SESSION['logged_user'];
    if (!$logged_user) {
        print_error(401, 'User must be logged for this page');
    }
}

function require_logged_user_JWT() {
    global $logged_user;
    $token = getBearerToken();
    if (!isset($token)) {
        print_error(401, 'Missing authentication token');
    }
    try {
        $logged_user = JWT::decode($token, JWT_SECRET_KEY, array_keys(JWT::$supported_algs));
        if (!$logged_user) {
            print_error(401, 'User must be logged for this page');
        }
    }  catch(Exception $e) {
        print_error(500, $e->getMessage());
    } catch (Error $e) {
        print_error(500, $e->getMessage());
    }
    return $logged_user;
}

/** 
 * Get header Authorization
 * @see https://stackoverflow.com/questions/40582161
 * */
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}
    
/**
 * Get access token from header
 * @see https://stackoverflow.com/questions/40582161
 * */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function print_error($http_err_code, $msg) {
    http_response_code($http_err_code);
    header('Content-Type: application/json');
    echo json_encode(["code" => $http_err_code, "error" => ["value" => $msg]]);
    die();
}

/*
Esegue un comado SQL SELECT e lo ritorna come array di oggetti, oppure lancia un print_error
*/
function select_list($sql, $connessione=null) {
    global $con;
    
    // SE TI SERVE FARE DEBUG: print_r($sql); print("\n");
    
    if ($connessione == null) {
        $connessione = $con;
    }
    if ($result = mysqli_query($connessione, $sql)) {
        $arr = array();
        while ($row = mysqli_fetch_assoc($result))
        {
            $arr[] = $row;
        }
        return $arr;
    } else {
        print_error(500, $connessione ->error);
    }
}

/*
Esegue un comado SQL SELECT ritorna solo la prima colonna come array, oppure lancia un print_error
*/
function select_column($sql, $connessione=null) {
    global $con;
    if ($connessione == null) {
        $connessione = $con;
    }
    if ($result = mysqli_query($connessione, $sql)) {
        $arr = array();
        while ($row = mysqli_fetch_array($result))
        {
            $arr[] = $row[0];
        }
        return $arr;
    } else {
        print_error(500, $connessione ->error);
    }
}

/*
Esegue un comado SQL SELECT e lo ritorna come singolo oggetto, oppure lancia un print_error
*/
function select_single($sql, $connessione=null) {
    global $con;
    if ($connessione == null) {
        $connessione = $con;
    }
    if ($result = mysqli_query($connessione, $sql)) {
        if ($row = mysqli_fetch_assoc($result))
        {
            return $row;
        } else {
            return null;
        }
    } else {
        print_error(500, $connessione ->error);
    }
}

/*
Esegue un comado SQL SELECT e si aspetta una singola cella come risultato, oppure lancia un print_error
*/
function select_single_value($sql, $connessione=null) {
    global $con;
    if ($connessione == null) {
        $connessione = $con;
    }
    if ($result = mysqli_query($connessione, $sql)) {
        if ($row = mysqli_fetch_array($result))
        {
            return $row[0];
        } else {
            return null;
        }
    } else {
        print_error(500, $connessione ->error);
    }
}


/*
Esegue un comado SQL UPDATE/INSERT/DELETE e se serve lancia un print_error
*/
function execute_update($sql, $connessione=null) {
    global $con;
    if ($connessione == null) {
        $connessione = $con;
    }
    mysqli_query($connessione, $sql);
    if ($connessione ->error) {
        print_error(500, $connessione ->error);
    }
    return mysqli_affected_rows($connessione);
}

// funzioni per creare comandi SQL
function sql_str_or_null($s) {
    if ($s || $s === 0 || $s === '0'){
        return "'$s'";
    }else{
        return "NULL";
    }
}

function sql_eq_str_or_is_null($s) {
    if ($s || $s === 0 || $s === '0')
        return "='$s'";
    else
        return "IS NULL";
}

function insert($table, $mappa_valori) {
    $sql = "INSERT INTO $table (";
    $arr_valori = [];
    foreach($mappa_valori as $colname => $valore) {
        $sql .= "$colname, ";
        array_push($arr_valori, $valore);
    }
    $sql = substr($sql, 0, -2);
    $sql .= ") VALUES (";
    foreach($arr_valori as $valore) {
        $sql .= sql_str_or_null($valore) .", ";
    }
    $sql = substr($sql, 0, -2);
    $sql .= ")";
    return $sql;
}

function update($table, $mappa_valori_set, $mappa_valori_where) {
    $sql = "UPDATE $table SET ";
    foreach($mappa_valori_set as $colname => $valore) {
        $sql .= "$colname = " . sql_str_or_null($valore) . ", ";
    }
    $sql = substr($sql, 0, -2);
    $sql .= " WHERE ";
    foreach($mappa_valori_where as $colname => $valore) {
        $sql .= "$colname " . sql_eq_str_or_is_null($valore) ." AND ";
    }
    $sql = substr($sql, 0, -4);
    return $sql;
}

function insert_select($table, $lista_tutte_le_colonne, $mappa_valori_da_modificare, $mappa_valori_where) {
    $sql = "INSERT INTO $table (";
    foreach($lista_tutte_le_colonne as $colname) {
        $sql .= "$colname, ";
    }
    $sql = substr($sql, 0, -2);
    $sql .= ") SELECT ";
    foreach($lista_tutte_le_colonne as $colname) {
        if (array_key_exists($colname, $mappa_valori_da_modificare)) {
            $sql .= sql_str_or_null($mappa_valori_da_modificare[$colname]) .", ";
        } else {
            $sql .= "$colname, ";
        }
    }
    $sql = substr($sql, 0, -2);
    $sql .= " FROM $table WHERE ";
    foreach($mappa_valori_where as $colname => $valore) {
        $sql .= "$colname " . sql_eq_str_or_is_null($valore) ." AND ";
    }
    $sql = substr($sql, 0, -4);
    return $sql;
}


?>