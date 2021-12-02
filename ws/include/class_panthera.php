<?php

$panthera = new PantheraManager();

class PantheraManager {

    function __construct() {
        $this->mock = (MOCK_PANTHERA == 'true');
        $this->conn = null;
    }
    
    function escape_string($s) {
        // there is no conn->escape_string() in sql server
        // see https://stackoverflow.com/questions/574805
        if (!isset($s) or $s === null) return null;
        if (empty($s)) return $s;
        if (is_numeric($s)) return $s;
        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ($non_displayables as $regex) {
            $s = preg_replace($regex, '', $s);
        }
        $s = str_replace("'", "''", $s);
        return $s;
    }

    function fmt_errors() {
        $errors = sqlsrv_errors();
        if (count($errors) >= 1) {
            $error = $errors[0]; // ne prendo uno a caso
            return "[SQLSTATE $error[SQLSTATE]] [SQLCODE $error[code]] $error[message]"; 
        } else {
            return "No error";
        }
    }

    function connect() {
        if (!$this->mock) {
            // echo "Connecting..." . DB_PTH_HOST;
            $this->conn = sqlsrv_connect(DB_PTH_HOST, array(
                                    "Database" => DB_PTH_NAME,  
                                    "UID" => DB_PTH_USER,
                                    "PWD" => DB_PTH_PASS));
            // echo "Done.";
            // var_dump($this->conn);
            if ($this->conn == false) {
                print_error(500, "Failed to connect: " . $this->fmt_errors());
            }
        }
    }

    /*
    Esegue un comado SQL SELECT e lo ritorna come array di oggetti, oppure lancia un print_error
    */
    function select_list($sql) {
        
        // SE TI SERVE FARE DEBUG: print_r($sql); print("\n");

        if ($result = sqlsrv_query($this->conn, $sql)) {
            $arr = array();
            while ($row = sqlsrv_fetch_array($result))
            {
                $arr[] = $row;
            }
            return $arr;
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL SELECT ritorna solo la prima colonna come array, oppure lancia un print_error
    */
    function select_column($sql) {
        if ($result = sqlsrv_query($this->conn, $sql)) {
            $arr = array();
            while ($row = sqlsrv_fetch_array($result))
            {
                $arr[] = $row[0];
            }
            return $arr;
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL SELECT e lo ritorna come singolo oggetto, oppure lancia un print_error
    */
    function select_single($sql) {
        if ($result = sqlsrv_query($this->conn, $sql)) {
            if ($row = sqlsrv_fetch_array($result))
            {
                return $row;
            } else {
                return null;
            }
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL SELECT e si aspetta una singola cella come risultato, oppure lancia un print_error
    */
    function select_single_value($sql) {
        if ($result = sqlsrv_query($this->conn, $sql)) {
            if ($row = sqlsrv_fetch_array($result))
            {
                return $row[0];
            } else {
                return null;
            }
        } else {
            print_error(500, $this->fmt_errors());
        }
    }

    /*
    Esegue un comado SQL UPDATE/INSERT/DELETE e se serve lancia un print_error
    */
    function execute_update($sql) {
        $result = sqlsrv_query($this->conn, $sql);
        if ($result === false) {
            print_error(500, $this->fmt_errors());
        }
    }

    function get_articoli($top=null, $skip=null, $search=null) {
        if ($this->mock) {
            $articoli = [ [ 'ID_ARTICOLO' => 'AAAAA', 'DESCRIZIONE' => 'Raccordo a 90-innesto is', 'DISEGNO' => 'XXX' ],
                      [ 'ID_ARTICOLO' => 'BBBB', 'DESCRIZIONE' => 'Patate', 'DISEGNO' => 'YYY' ],
                      [ 'ID_ARTICOLO' => 'ZZZZZZ', 'DESCRIZIONE' => 'Zucchine', 'DISEGNO' => 'ZZZ' ]
                     ];
            $count = 1000;
        } else {
            $sql0 = "SELECT COUNT(*) AS cnt ";
            # Qui prendo la DESCRIZIONE anzichè DESCR_ESTESA, per ragioni di spazio
            $sql1 = "SELECT ID_ARTICOLO,DESCRIZIONE,DISEGNO ";
            $sql = "FROM THIP.ARTICOLI WHERE ID_AZIENDA='001' ";
            if ($search) {
                $search = strtoupper($search);
                $sql .= "AND UPPER(ID_ARTICOLO) LIKE '%$search%' OR DESCR_ESTESA LIKE UPPER('%$search%')  OR DISEGNO LIKE UPPER('%$search%') ";
            }
            $sql .= "ORDER BY 1 ";
            $count = $this->select_single_value($sql0 . $sql);

            if ($top != null) {
                if ($skip != null) {
					$sql .= " OFFSET $skip ROWS FETCH NEXT $top ROWS ONLY ";
                } else {
					$sql .= " OFFSET 0 ROWS FETCH NEXT $top ROWS ONLY ";
                }
            }

            $articoli = $this->select_list($sql1 . $sql);
        }
        
        return [$articoli, $count];
    }

    function get_articolo($codArticolo) {
        if ($this->mock) {
            return [ 'ID_ARTICOLO' => 'AAAAA', 'DESCRIZIONE' => 'Raccordo a 90-innesto istantaneo bianco-tubo Øe2-filetto maschio M3-acciaio inox', 'DISEGNO' => 'Disegno' ];
        } else {
            $query = "SELECT ID_ARTICOLO,DESCRIZIONE,DESCR_ESTESA AS DESCRIZIONE,DISEGNO FROM THIP.ARTICOLI WHERE ID_AZIENDA='001' AND ID_ARTICOLO='$codArticolo'";
            return $this->select_single($query);
        }
    }

    function getVistaCruscotto() {
        if ($this->mock) {
            $objects = [ [ 'COD_COMMESSA' => 'AAAAA', 'DESCRIZIONE' => 'Piantare patate', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D1', 'TOT_FATTURATO' => 50000, 'SALDO_CONTO_TRANSITORIO' => 50000 , 'SALDO_CONTO_RICAVI' => 0.0 ],
                      [ 'COD_COMMESSA' => 'BBBB', 'DESCRIZIONE' => 'Annaffiare fagiolini', 'COD_CLIENTE' => '4321','COD_DIVISIONE' => 'D1', 'TOT_FATTURATO' => 100000, 'SALDO_CONTO_TRANSITORIO' => 10000 , 'SALDO_CONTO_RICAVI' => 90000 ],
                      [ 'COD_COMMESSA' => 'ZZZZZZ', 'DESCRIZIONE' => 'Pelare zucchine', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D2', 'TOT_FATTURATO' => 50000, 'SALDO_CONTO_TRANSITORIO' => 20000 , 'SALDO_CONTO_RICAVI' => 0.0 ],
                      [ 'COD_COMMESSA' => 'TTTTTT', 'DESCRIZIONE' => 'Potare ortensie', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D2', 'TOT_FATTURATO' => 10000, 'SALDO_CONTO_TRANSITORIO' => 0.0 , 'SALDO_CONTO_RICAVI' => 10000.0 ]
                     ];
            $count = 1000;
        } else {
            $sql0 = "SELECT COUNT(*) AS cnt ";
            $sql1 = "SELECT ID_ARTICOLO,DESCRIZIONE,DISEGNO ";
            $sql = "FROM THIP.BOH WHERE ID_AZIENDA='001' ";
            $sql .= "ORDER BY 1,2,3 ";

            $count = $this->select_single_value($sql0 . $sql);
            $objects = $this->select_list($sql1 . $sql);
        }
        
        return [$objects, $count];
    }

    function getVistaCruscottoById($codCommessa) {
        if ($this->mock) {
            $object = [ 'COD_COMMESSA' => 'AAAAA', 'DESCRIZIONE' => 'Piantare patate', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D1', 'TOT_FATTURATO' => 50000, 'SALDO_CONTO_TRANSITORIO' => 50000 , 'SALDO_CONTO_RICAVI' => 0.0 ];
        } else {
            $sql = "SELECT ID_ARTICOLO,DESCRIZIONE,DISEGNO 
                FROM THIP.BOH WHERE ID_AZIENDA='001' AND COD_COMMESSA='$codCommessa' ";

            $object = $this->select_single($sql);
        }
        
        return $object;
    }

    function getVistaAnalisiCommessa($codCommessa) {
        if ($this->mock) {
            $objects = [ [ 'COD_COMMESSA' => 'AAAAA', 'DESCRIZIONE' => 'Piantare patate', 'COD_CLIENTE' => '1234', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D2', 'COD_ARTICOLO' => 'F101010', 'COD_ARTICOLO_RIF' => 'F202020', 'CENTRO_COSTO' => 'A51', 'DARE' => 0, 'AVERE' => 20000 ],
                      [ 'COD_COMMESSA' => 'BBBB', 'DESCRIZIONE' => 'Annaffiare fagiolini', 'COD_CLIENTE' => '4321', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D2', 'COD_ARTICOLO' => 'F101010', 'COD_ARTICOLO_RIF' => 'F202020', 'CENTRO_COSTO' => 'A51', 'DARE' => 10000, 'AVERE' => 0  ],
                      [ 'COD_COMMESSA' => 'ZZZZZZ', 'DESCRIZIONE' => 'Pelare zucchine', 'COD_CLIENTE' => '1234', 'COD_CLIENTE' => '1234','COD_DIVISIONE' => 'D2', 'COD_ARTICOLO' => 'F101010', 'COD_ARTICOLO_RIF' => 'F202020', 'CENTRO_COSTO' => 'A51', 'DARE' => 10000, 'AVERE' => 0  ]
                     ];
            $count = 1000;
        } else {
            $sql0 = "SELECT COUNT(*) AS cnt ";
            $sql1 = "SELECT ID_ARTICOLO,DESCRIZIONE,DISEGNO ";
            $sql = "FROM THIP.ARTICOLI WHERE ID_AZIENDA='001' AND COD_COMMESSA='$codCommessa' ";
            $sql .= "ORDER BY 1,2,3 ";

            $count = $this->select_single_value($sql0 . $sql);
            $objects = $this->select_list($sql1 . $sql);
        }
        
        return [$objects, $count];
    }
    
    function chiusuraContabileCommessa($codCommessa) {
        if (!$this->mock) {
            $sql = "UPDATE THIP.COMMESSE SET STATO='BOH' WHERE COD_COMMESSA='$codCommessa' ";
            executeUpdate($sql);
        }
    }

    function preparaGiroconto($codCommessa) {
        // TODO
    }
    
}
?>