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

    function getVistaCruscotto($codCommessa='') {
        global $matrice_conti;
        if ($this->mock) {
            $objects = [ [ 'COD_COMMESSA' => 'C36140M01', 'DES_COMMESSA' => 'Implementazione su Linea a Banchi', 'COD_CLIENTE' => '006416','CLI_RA_SOC'=>'BREMBO SPA','COD_DIVISIONE' => 'AUT', 'TOT_FATTURATO' => 50000, 'SALDO_CONTO_TRANSITORIO' => 50000 , 'SALDO_CONTO_RICAVI' => 0.0, 'CONTO_TRANSITORIO' => '606004' ],
                      [ 'COD_COMMESSA' => 'C36369', 'DES_COMMESSA' => 'SC per NRM', 'COD_CLIENTE' => '008027','CLI_RA_SOC'=>'MB ELETTRONICA S.R.L.','COD_DIVISIONE' => 'ELE', 'TOT_FATTURATO' => 100000, 'SALDO_CONTO_TRANSITORIO' => 10000 , 'SALDO_CONTO_RICAVI' => 90000, 'CONTO_TRANSITORIO' => '606002' ],
                      [ 'COD_COMMESSA' => 'C36640', 'DES_COMMESSA' => 'SC per ATPS - Auto Test & Pretest S', 'COD_CLIENTE' => '003933','CLI_RA_SOC'=>'STMicroelectronics Finance II NV ','COD_DIVISIONE' => 'SEM', 'TOT_FATTURATO' => 50000, 'SALDO_CONTO_TRANSITORIO' => 20000 , 'SALDO_CONTO_RICAVI' => 0.0, 'CONTO_TRANSITORIO' => '606004' ],
                      [ 'COD_COMMESSA' => 'C36723', 'DES_COMMESSA' => 'SC per NME+NME', 'COD_CLIENTE' => '001859','CLI_RA_SOC'=>'BITRON POLAND SP. Z.O.O.','COD_DIVISIONE' => 'ELE', 'TOT_FATTURATO' => 10000, 'SALDO_CONTO_TRANSITORIO' => 0.0 , 'SALDO_CONTO_RICAVI' => 10000.0, 'CONTO_TRANSITORIO' => '606004/606004' ],
                      [ 'COD_COMMESSA' => 'C36910', 'DES_COMMESSA' => 'SC per NME', 'COD_CLIENTE' => '001477','CLI_RA_SOC'=>'Westport Fuel Systems Italia S.r.l.','COD_DIVISIONE' => 'ELE', 'TOT_FATTURATO' => 10000, 'SALDO_CONTO_TRANSITORIO' => 0.0 , 'SALDO_CONTO_RICAVI' => 10000.0, 'CONTO_TRANSITORIO' => '606004' ]
                     ];
                     
            foreach($objects as $id => $obj) {
                $contoTransitorio = $obj['CONTO_TRANSITORIO'];
                if (isset($matrice_conti[$contoTransitorio])) {
                    $objects[$id]['CONTO_RICAVI'] = $matrice_conti[$contoTransitorio];
                }
            }
        } else {
            $statoIniziale = STATO_WF_START;
            $conti_transitori_imploded = "'" . implode("','", array_keys($matrice_conti)) .  "'";
            $conti_ricavi_imploded = "'" . implode("','", array_values($matrice_conti)) .  "'";
            $sql1 = "SELECT
                        RTRIM(S.GPV0CD) as COD_CONTO,
                        RTRIM(S.GPD0CD) as COD_COMMESSA,
                        RTRIM(CD.DESCRIZIONE) as DES_COMMESSA,
                        RTRIM(S.T36CD) as COD_DIVISIONE,
                        CASE WHEN RTRIM(CLICD) !='' THEN RTRIM(CLICD) ELSE RTRIM(GPS4CD) END as COD_CLIENTE,
                        RTRIM(CLI.RAGIONE_SOCIALE) as CLI_RA_SOC,
                        SUM(S.GSL0AUCA-S.GSL0DUCA) as SALDO,
                        FAT.TOT_FATTURATO,
                        RTRIM(S.GPC0CD) as CENTRO_COSTO,
                        CASE WHEN S.GPV0CD in ($conti_transitori_imploded) THEN 'TRANSITORIO' ELSE 'RICAVI' END AS TIPO_CONTO
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN THIP.ARTICOLI AR on AR.ID_AZIENDA = S.T01CD and AR.ID_ARTICOLO = S.GPS2CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    LEFT JOIN (
                        SELECT ID_AZIENDA, R_COMMESSA,
                            sum(CASE WHEN(TP_DOC_VEN = 1) THEN IMPORTO_VP ELSE -IMPORTO_VP END) as TOT_FATTURATO
                        FROM THIP.YSTAT_FATVEN_V01
                        where ID_AZIENDA = '001' and R_COMMESSA is not null and ID_ANNO_DOC > 2020
                        group by ID_AZIENDA, R_COMMESSA
                    ) FAT on FAT.ID_AZIENDA = S.T01CD and FAT.R_COMMESSA = S.GPD0CD
                    WHERE GT01CD = 'BASE'
                        and T01CD = '001'
                        and GT02CD = 'CONS'
                        and GSL0TPSL = 1
                        and GS02CD = '*****'
                        and GPV0CD not like 'ZZ%'
                        and DATEPART(yy, GAT0CD) = 2022
                        and S.GPV0CD in ($conti_transitori_imploded, $conti_ricavi_imploded)
                        and ('$codCommessa'='' or S.GPD0CD='$codCommessa')
                        -- and WF_NODE_ID='$statoIniziale'
                    GROUP BY
                        S.GPV0CD,S.GPD0CD,S.T36CD,S.GPC0CD,S.GSL0AUCA,S.GSL0DUCA,
                        CD.DESCRIZIONE,
                        CASE WHEN RTRIM(CLICD) !='' THEN RTRIM(CLICD) ELSE RTRIM(GPS4CD) END,
                        CLI.RAGIONE_SOCIALE,
                        FAT.TOT_FATTURATO
                    ORDER BY COD_COMMESSA ";
            $objects = $this->select_list($sql1);

            // ora ho più righe per ogni commessa, 1 riga per ogni conto
            // invece ne voglio una sola

            if (count($objects) > 0) {
                $groups = array_group_by($objects, ['COD_COMMESSA']);
                $result = [];
                foreach($groups as $codCommessa => $conti) {
                    $contoTransitorio = [];
                    $saldoTransitorio = 0.0;
                    $contoRicavi = [];
                    $saldoRicavi = 0.0;
                    foreach($conti as $row) {
                        if ($row['TIPO_CONTO'] == 'TRANSITORIO') {
                            if (!in_array($row['COD_CONTO'], $contoTransitorio)) {
                                $contoTransitorio[] = $row['COD_CONTO'];
                            }
                            $saldoTransitorio += (float)$row['SALDO'];
                        } else {
                            if (!in_array($row['COD_CONTO'], $contoRicavi)) {
                                $contoRicavi[] = $row['COD_CONTO'];
                            }
                            $saldoRicavi += (float)$row['SALDO'];
                        }
                    }
                    // il conto transitorio dovrebbe essere unico, e pure il conto ricavi, e
                    // inoltre i due conti dovrebbero essere collegato dalla matrice_conti
                    if (count($contoTransitorio) > 0) {
                        foreach($contoTransitorio as $c)
                        $contoRicaviPrevisto = $matrice_conti[$contoTransitorio[0]];
                        if (!in_array($contoRicaviPrevisto, $contoRicavi)) {
                            $contoRicavi[] = $contoRicaviPrevisto;
                        }
                    }

                    $firstRow = $conti[0];
                    unset($firstRow['COD_CONTO']);
                    unset($firstRow['SALDO']);
                    $firstRow['CONTO_TRANSITORIO'] = implode(';', $contoTransitorio);
                    $firstRow['SALDO_CONTO_TRANSITORIO'] = $saldoTransitorio;
                    $firstRow['CONTO_RICAVI'] = implode(';', $contoRicavi);
                    $firstRow['SALDO_CONTO_RICAVI'] = $saldoRicavi;
                    $firstRow['TOT_FATTURATO'] = (float)$firstRow['TOT_FATTURATO'];
                    $result[] = $firstRow;
                }

                $objects = $result;
            }
        }
        return [$objects, count($objects)];
    }

    function getVistaAnalisiCommessa($codCommessa) {
        global $matrice_conti;
        if ($this->mock) {
            $objects = [ [ 'COD_COMMESSA' => 'C36140M01', 'DES_COMMESSA' => 'Fixture for seed attachment (n° 2)', 'COD_CLIENTE' => '006409', 'CLI_RA_SOC' => 'STMicroelectronics Silicon Carbide', 'COD_DIVISIONE' => 'SMP', 'COD_ARTICOLO' => 'F101010', 'DES_ARTICOLO' => '.', 'COD_ARTICOLO_RIF' => '', 'CENTRO_COSTO' => 'A51', 'DARE' => 0, 'AVERE' => 50000, 'SALDO' => 50000, 'COD_CONTO' => '606004', 'ESERCIZIO' => '2022', 'TIPO_CONTO' => 'TRANSITORIO' ],
                      [ 'COD_COMMESSA' => 'C36140M01', 'DES_COMMESSA' => 'Fixture for seed attachment (n° 2)', 'COD_CLIENTE' => '006409', 'CLI_RA_SOC' => 'STMicroelectronics Silicon Carbide', 'COD_DIVISIONE' => 'SMP', 'COD_ARTICOLO' => 'F101010', 'DES_ARTICOLO' => '.', 'COD_ARTICOLO_RIF' => '', 'CENTRO_COSTO' => 'A51', 'DARE' => 50000, 'AVERE' => 0, 'SALDO' => -50000, 'COD_CONTO' => '901002', 'ESERCIZIO' => '2022', 'TIPO_CONTO' => 'RICAVI'  ]
                     ];
        } else {
            $conti_transitori_imploded = "'" . implode("','", array_keys($matrice_conti)) .  "'";
            $conti_ricavi_imploded = "'" . implode("','", array_values($matrice_conti)) .  "'";
            $sql1 = "SELECT
                        RTRIM(S.GPV0CD) as COD_CONTO,
                        RTRIM(S.GPD0CD) as COD_COMMESSA,
                        RTRIM(CD.DESCRIZIONE) as DES_COMMESSA,
                        RTRIM(S.T36CD) as COD_DIVISIONE,
                        CASE WHEN CLICD !='' THEN RTRIM(CLICD) ELSE RTRIM(GPS4CD) END as COD_CLIENTE,
                        RTRIM(CLI.RAGIONE_SOCIALE) as CLI_RA_SOC,				
                        RTRIM(S.GPS2CD) as COD_ARTICOLO,
                        RTRIM(AR.DESCR_ESTESA) as DES_ARTICOLO,
                        RTRIM(GPS3CD) as COD_ARTICOLO_RIF,
                        GSL0DUCA as DARE,
                        GSL0AUCA as AVERE,
                        (GSL0AUCA-GSL0DUCA) as SALDO,
                        DATEPART(yy, S.GAT0CD) as ESERCIZIO,
                        RTRIM(GPC0CD) as CENTRO_COSTO,
                        CASE WHEN S.GPV0CD in ($conti_transitori_imploded) THEN 'TRANSITORIO' ELSE 'RICAVI' END AS TIPO_CONTO
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN THIP.ARTICOLI AR on AR.ID_AZIENDA = S.T01CD and AR.ID_ARTICOLO = S.GPS2CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    WHERE GT01CD = 'BASE'
                        and T01CD = '001'
                        and GT02CD = 'CONS'
                        and GSL0TPSL = 1
                        and GS02CD = '*****'
                        --and DATEPART(yy, GAT0CD) = 2022
                        and GPV0CD not like 'ZZ%'
                        --and GPC0CD = 'CR001'
                        --and not (GSL0DUCA = 0 and GSL0AUCA = 0)
                        and S.GPD0CD = '$codCommessa'
                        and S.GPV0CD in ($conti_transitori_imploded, $conti_ricavi_imploded)
                    ORDER BY COD_CONTO";
            $objects = $this->select_list($sql1);
        }
        
        if (count($objects) > 0) {
            foreach($objects as $id => $row) {
                if ($row['TIPO_CONTO'] == 'TRANSITORIO') {
                    $objects[$id]['CONTO_RICAVI'] = $matrice_conti[$row['COD_CONTO']];
                } else {
                    $objects[$id]['CONTO_RICAVI'] = null;
                }
            }
        }

        return [$objects, count($objects)];
    }
    
    function avanzamentoWorkflow($codCommessa) {
        if (!$this->mock) {
            $statoFinale = STATO_WF_END;
            $sql = "UPDATE THIP.COMMESSE SET STATO_WF=$statoFinale WHERE COD_COMMESSA='$codCommessa' ";
            executeUpdate($sql);
        }
    }

    function preparaGiroconto($codCommessa) {
        print_error(500, 'Funzione non implementata');
    }
    
}
?>