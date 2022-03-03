<?php

$saldiManager = new SaldiManager();

class SaldiManager {
    

    function getVistaCruscotto($codCommessa='') {
        global $matrice_conti, $panthera;

        if ($panthera->mock) {
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
            $conti_transitori_imploded = "'" . implode("','", array_keys($matrice_conti)) .  "'";
            $conti_ricavi_imploded = "'" . implode("','", array_values($matrice_conti)) .  "'";
            $sql1 = "SELECT
                        RTRIM(S.GPV0CD) as COD_CONTO,
                        RTRIM(VOC.VOCDSNOR) as DES_CONTO,
                        RTRIM(S.GPD0CD) as COD_COMMESSA,
                        RTRIM(CD.DESCRIZIONE) as DES_COMMESSA,
                        RTRIM(S.T36CD) as COD_DIVISIONE,
                        RTRIM(D.T36DSNOR) as DES_DIVISIONE,
                        CASE WHEN RTRIM(S.CLICD) !='' THEN RTRIM(S.CLICD) ELSE RTRIM(S.GPS4CD) END as COD_CLIENTE,
                        RTRIM(CLI.RAGIONE_SOCIALE) as CLI_RA_SOC,
                        SUM(S.GSL0AUCA-S.GSL0DUCA) as SALDO,
                        RTRIM(S.GPC0CD) as CENTRO_COSTO,
                        FAT.TOT_FATTURATO,
                        CASE WHEN S.GPV0CD in ($conti_transitori_imploded) THEN 'TRANSITORIO' ELSE 'RICAVI' END AS TIPO_CONTO
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN FINANCE.BBVOCPT VOC on VOC.T01CD = S.T01CD and VOC.VOCCD = S.GPV0CD
                    LEFT JOIN FINANCE.BBT36PT D on D.T01CD = S.T01CD and D.T36CD = S.T36CD
                    LEFT JOIN THIP.ARTICOLI AR on AR.ID_AZIENDA = S.T01CD and AR.ID_ARTICOLO = S.GPS2CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    LEFT JOIN (
                        SELECT ID_AZIENDA, R_COMMESSA,
                            sum(CASE WHEN(TP_DOC_VEN = 1) THEN IMPORTO_VP ELSE -IMPORTO_VP END) as TOT_FATTURATO
                        FROM THIP.YSTAT_FATVEN_V01
                        where ID_AZIENDA = '001' and R_COMMESSA is not null and ID_ANNO_DOC > 2020
                        group by ID_AZIENDA, R_COMMESSA
                    ) FAT on FAT.ID_AZIENDA = S.T01CD and FAT.R_COMMESSA = S.GPD0CD
                    WHERE S.GT01CD = 'BASE'
                        and S.T01CD = '$ID_AZIENDA'
                        and S.GT02CD = 'CONS'
                        and S.GSL0TPSL = 1
                        and S.GS02CD = '*****'
                        and DATEPART(yy, S.GAT0CD) > 2020     -- paracadute
                        and S.GPV0CD not like 'ZZ%'           -- contropartita
                        and S.GPV0CD in ($conti_transitori_imploded, $conti_ricavi_imploded)
                        and ('$codCommessa'='' or S.GPD0CD='$codCommessa')
                        -- and CD.WF_NODE_ID='$STATO_WF_START'
                    GROUP BY
                        S.GPV0CD,S.GPD0CD,S.T36CD,S.GPC0CD,S.GSL0AUCA,S.GSL0DUCA,
                        VOC.VOCDSNOR,
                        CD.DESCRIZIONE,
                        D.T36DSNOR,
                        CASE WHEN RTRIM(CLICD) !='' THEN RTRIM(CLICD) ELSE RTRIM(GPS4CD) END,
                        CLI.RAGIONE_SOCIALE,
                        FAT.TOT_FATTURATO
                    ORDER BY COD_COMMESSA ";
            $objects = $panthera->select_list($sql1);

            // ora ho più righe per ogni commessa, 1 riga per ogni conto
            // invece ne voglio una sola

            // tutto questo potrebbe essere fatto in SQL se ci fosse la funzione STRING_AGG
            if (count($objects) > 0) {
                $groups = array_group_by($objects, ['COD_COMMESSA']);
                $result = [];
                foreach($groups as $codCommessa => $conti) {
                    $contoTransitorio = [];
                    $desContoTransitorio = [];
                    $saldoTransitorio = 0.0;
                    $contoRicavi = [];
                    $desContoRicavi = [];
                    $saldoRicavi = 0.0;
                    foreach($conti as $row) {
                        if ($row['TIPO_CONTO'] == 'TRANSITORIO') {
                            if (!in_array($row['COD_CONTO'], $contoTransitorio)) {
                                $contoTransitorio[] = $row['COD_CONTO'];
                                $desContoTransitorio[] = $row['DES_CONTO'];
                            }
                            $saldoTransitorio += (float)$row['SALDO'];
                        } else {
                            if (!in_array($row['COD_CONTO'], $contoRicavi)) {
                                $contoRicavi[] = $row['COD_CONTO'];
                                $desContoRicavi[] = $row['DES_CONTO'];
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
                    unset($firstRow['DES_CONTO']);
                    unset($firstRow['SALDO']);
                    $firstRow['CONTO_TRANSITORIO'] = implode(';', $contoTransitorio);
                    $firstRow['DES_CONTO_TRANSITORIO'] = implode(';', $desContoTransitorio);
                    $firstRow['SALDO_CONTO_TRANSITORIO'] = $saldoTransitorio;
                    $firstRow['CONTO_RICAVI'] = implode(';', $contoRicavi);
                    $firstRow['DES_CONTO_RICAVI'] = implode(';', $desContoRicavi);
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
        global $matrice_conti, $panthera;

        if ($panthera->mock) {
            $objects = [ [ 'COD_COMMESSA' => 'C36140M01', 'DES_COMMESSA' => 'Fixture for seed attachment (n° 2)', 'COD_CLIENTE' => '006409', 'CLI_RA_SOC' => 'STMicroelectronics Silicon Carbide', 'COD_DIVISIONE' => 'SMP', 'COD_ARTICOLO' => 'F101010', 'DES_ARTICOLO' => '.', 'COD_ARTICOLO_RIF' => '', 'CENTRO_COSTO' => 'A51', 'DARE' => 0, 'AVERE' => 50000, 'SALDO' => 50000, 'COD_CONTO' => '606004', 'ESERCIZIO' => '2022', 'TIPO_CONTO' => 'TRANSITORIO' ],
                      [ 'COD_COMMESSA' => 'C36140M01', 'DES_COMMESSA' => 'Fixture for seed attachment (n° 2)', 'COD_CLIENTE' => '006409', 'CLI_RA_SOC' => 'STMicroelectronics Silicon Carbide', 'COD_DIVISIONE' => 'SMP', 'COD_ARTICOLO' => 'F101010', 'DES_ARTICOLO' => '.', 'COD_ARTICOLO_RIF' => '', 'CENTRO_COSTO' => 'A51', 'DARE' => 50000, 'AVERE' => 0, 'SALDO' => -50000, 'COD_CONTO' => '901002', 'ESERCIZIO' => '2022', 'TIPO_CONTO' => 'RICAVI'  ]
                     ];
        } else {
            $conti_transitori_imploded = "'" . implode("','", array_keys($matrice_conti)) .  "'";
            $conti_ricavi_imploded = "'" . implode("','", array_values($matrice_conti)) .  "'";
            $sql1 = "SELECT
                        RTRIM(S.GPV0CD) as COD_CONTO,
                        RTRIM(VOC.VOCDSNOR) as DES_CONTO,
                        RTRIM(S.GPD0CD) as COD_COMMESSA,
                        RTRIM(CD.DESCRIZIONE) as DES_COMMESSA,
                        RTRIM(S.T36CD) as COD_DIVISIONE,
                        RTRIM(D.T36DSNOR) as DES_DIVISIONE,
                        CASE WHEN CLICD !='' THEN RTRIM(CLICD) ELSE RTRIM(GPS4CD) END as COD_CLIENTE,
                        RTRIM(CLI.RAGIONE_SOCIALE) as CLI_RA_SOC,
                        RTRIM(S.GPS2CD) as COD_ARTICOLO,
                        RTRIM(AR.DESCR_ESTESA) as DES_ARTICOLO,
                        RTRIM(S.GPS3CD) as COD_ARTICOLO_RIF,
                        RTRIM(AR2.DESCR_ESTESA) as DES_ARTICOLO_RIF,
                        RTRIM(S.GPC0CD) as CENTRO_COSTO,
                        S.GSL0DUCA as DARE,
                        S.GSL0AUCA as AVERE,
                        (S.GSL0AUCA-S.GSL0DUCA) as SALDO,
                        DATEPART(yy, S.GAT0CD) as ESERCIZIO,
                        CASE WHEN S.GPV0CD in ($conti_transitori_imploded) THEN 'TRANSITORIO' ELSE 'RICAVI' END AS TIPO_CONTO
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN FINANCE.BBT36PT D on D.T01CD = S.T01CD and D.T36CD = S.T36CD
                    LEFT JOIN FINANCE.BBVOCPT VOC on VOC.T01CD = S.T01CD and VOC.VOCCD = S.GPV0CD
                    LEFT JOIN THIP.ARTICOLI AR on AR.ID_AZIENDA = S.T01CD and AR.ID_ARTICOLO = S.GPS2CD
                    LEFT JOIN THIP.ARTICOLI AR2 on AR2.ID_AZIENDA = S.T01CD and AR2.ID_ARTICOLO = S.GPS3CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    WHERE S.GT01CD = 'BASE'
                        and S.T01CD = '$ID_AZIENDA'
                        and S.GT02CD = 'CONS'
                        and S.GSL0TPSL = 1
                        and S.GS02CD = '*****'
                        and DATEPART(yy, S.GAT0CD) > 2020     -- paracadute
                        and S.GPV0CD not like 'ZZ%'           -- contropartita
                        and S.GPC0CD = 'CR001'
                        and not (S.GSL0DUCA = 0 and S.GSL0AUCA = 0)
                        and S.GPD0CD = '$codCommessa'
                        and S.GPV0CD in ($conti_transitori_imploded, $conti_ricavi_imploded)
                    ORDER BY COD_CONTO";
            $objects = $panthera->select_list($sql1);
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
        global $logged_user, $panthera;

        if (!$panthera->mock) {
            $sql = "UPDATE THIP.COMMESSE SET WF_NODE_ID='$STATO_WF_END' WHERE ID_COMMESSA='$codCommessa' ";
            $panthera->execute_update($sql);

            // transazione per incrementare il numeratore
            sqlsrv_begin_transaction($panthera->conn);
            $sql = "UPDATE THERA.NUMERATOR SET LAST_NUMBER=LAST_NUMBER+1 WHERE NUMERATOR_ID='WF_LOG'";
            $panthera->execute_update($sql);
            $sql = "SELECT LAST_NUMBER FROM THERA.NUMERATOR WHERE NUMERATOR_ID='WF_LOG'";
            $id = $panthera->select_single_value($sql);
            sqlsrv_commit($panthera->conn);

            $utente = $logged_user->nome_utente . '_' . $ID_AZIENDA;
            $sql = "INSERT INTO THERA.WF_LOG(
                        ID,
                        WF_CLASS_ID,WF_ID,WF_ARC_ID,
                        INITIAL_NODE,INITIAL_SUB_NODE,
                        FINAL_NODE,FINAL_SUB_NODE,
                        OBJECT_KEY,
                        USER_ID,
                        USER_NOTE)
                    VALUES (
                        $id,
                        51,'COM_WF','-',
                        '$STATO_WF_START','-',
                        '$STATO_WF_END','-',
                        '$ID_AZIENDA'+CHAR(22)+'$codCommessa',
                        '$utente',
                        'Avanzamento via piattaforma Ricavi Commesse') ";
            $panthera->execute_update($sql);
        }
    }

    function preparaGiroconto($codCommessa) {
        global $panthera, $logged_user;
        
        if ($panthera->mock) {
            return;
        }

        print_error(500, 'Funzione non implementata');

        $conti_transitori_imploded = "'" . implode("','", array_keys($matrice_conti)) .  "'";

        $decode_conto = 'CASE ';
        foreach ($matrice_conti as $t => $r) {
            $decode_conto .= "WHEN S.GPV0CD='$t' THEN '$r' ";
        }
        $decode_conto .= "ELSE '' END";

        $utente = $logged_user->nome_utente . '_' . $ID_AZIENDA;

        $query1 = "INSERT INTO FINANCE.BETRAPT(
                        T01CD,      -- azienda
                        TRANUREG,   -- num.reg.
                        TRANRIRE,   -- num.riga
                        --TRANPRGT,   -- num progressivo (per imm. massa?)
                        --WTRNRLOG,   -- nr. log (per imm. massa?)
                        TRASTATO,
                        T09CD,      -- numeratore
                        TRACPESE,   -- esercizio
                        TRATPRIM,   -- tipo riga imm.massa
                        T02CD,      -- causale contabile
                        TRADSCAU,   -- descr. causale contabile
                        TRADSAGG,   -- descr. aggiuntiva
                        VOCCD,      -- voce contabile
                        TRADTRCO,   -- data reg.
                        TRADTOPE,   -- data operazione
                        TRADTIVA,   -- data comp. IVA
                        TRADTDOC,   -- data doc.
                        TRADTVAL,   -- data valuta
                        TRADTSPA,   -- data scad. pagamento
                        TRAAAPAR,   -- anno partita
                        TRANRPAR,   -- nr. rif. partita
                        TRANPIVA,   -- nr. protocollo IVA
                        TRATPVAL,   -- tipo valuta
                        --MOVNAPAG,   -- natura pagamento
                        --TRACAMBVP -- cambio val. prim.     
                        --TRACAMBVS -- cambio val. sec.
                        --T05CD     -- assogg. IVA
                        --T22CD     -- mod.pag.
                        --TRATPPAG,   -- tipo pagamento
                        TRASEGNO,
                        TRAIMPVP,   -- importo in val. prim.
                        TRAIIVVP,   -- Imponibile IVA val primaria
                        TRAIVAVP,   -- Imposta IVA val primaria
                        MOVT62CD    -- Commessa_REF
                        )
                    SELECT
                        S.T01CD as COD_AZIENDA,
                        0 as NUM_REG,
                        (ROW_NUMBER() OVER(ORDER BY S.T01CD)) * 2 - 1 as NUM_RIGA,
                        '1' as STATO,
                        '$NUMERATORE' as NUMERATORE,
                        DATEPART(yy, S.GAT0CD) as TRACPESE,
                        '3' as TRATPRIM,
                        '$CAU_CONTABILE' as CAUSALE_CONTABILE,
                        'Giroconto Ricavi' as TRADSCAU, -- teoricamente ricavabile da BBT02PT
                        '' as TRADSAGG,
                        S.GPV0CD as COD_CONTO,
                        GETDATE() as DATA_REG,
                        '0001-01-01' as DATA_OPERAZ,
                        '0001-01-01' as DATA_IVA,
                        GETDATE() as DATA_DOC,
                        '0001-01-01' as DATA_VALUTA,
                        '0001-01-01' as DATA_SCAD_PAG,
                        PARTITE.MAPAAPAR as ANNO_PARTITA,
                        PARTITE.MAPNRPAR as NR_PARTITA,
                        '0'as TRANPIVA,
                        '1' as TRATPVAL,
                        '1' as TRASEGNO,
                        (S.GSL0AUCA-S.GSL0DUCA) as TRAIMPVP,
                        '0' as TRAIIVVP,
                        '0' as TRAIVAVP,
                        S.GPD0CD as MOVT62CD
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    LEFT JOIN (
                        SELECT P.T01CD, P.MAPAAPAR, P.MAPNRPAR, S.R_COMMESSA,
                            ROW_NUMBER()OVER(PARTITION BY R_COMMESSA ORDER BY MAPAAPAR DESC,MAPNRPAR DESC) AS NUM
                        FROM FINANCE.BCMAPPT P
                        JOIN THIPPERS.YSTAT_FATVEN_V01 S on S.ID_AZIENDA = P.T01CD and P.MAPAAPAR = S.ANNO_FAT
                            and P.MAPNRPAR = S.NUMERO_FATTURA 
                        WHERE P.MAPSTAPA = 1 -- solo partite aperte
                        ) PARTITE ON PARTITE.T01CD=S.T01CD and PARTITE.R_COMMESSA=S.GPD0CD and NUM=1
                    WHERE GT01CD = 'BASE'
                        and S.T01CD = '$ID_AZIENDA'
                        and S.GT02CD = 'CONS'
                        and S.GSL0TPSL = 1
                        and S.GS02CD = '*****'
                        and S.GPV0CD not like 'ZZ%'
                        --and S.GPC0CD = 'CR001'
                        and S.GSL0DUCA <> S.GSL0AUCA
                        and S.GPD0CD = '$codCommessa'
                        and S.GPV0CD in ($conti_transitori_imploded)
                    GROUP BY S.T01CD,S.GPV0CD,S.GPD0CD,S.GAT0CD,S.GSL0AUCA,S.GSL0DUCA,PARTITE.MAPAAPAR,PARTITE.MAPNRPAR
                UNION
                    SELECT
                        S.T01CD as COD_AZIENDA,
                        0 as NUM_REG,
                        (ROW_NUMBER() OVER(ORDER BY S.T01CD)) * 2 as NUM_RIGA,
                        '1' as STATO,
                        '$NUMERATORE' as NUMERATORE,
                        DATEPART(yy, S.GAT0CD) as TRACPESE,
                        '3' as TRATPRIM,
                        '$CAU_CONTABILE' as CAUSALE_CONTABILE,
                        'Giroconto Ricavi' as TRADSCAU, -- teoricamente ricavabile da BBT02PT
                        '' as TRADSAGG,
                        $decode_conto as COD_CONTO,
                        GETDATE() as DATA_REG,
                        '0001-01-01' as DATA_OPERAZ,
                        '0001-01-01' as DATA_IVA,
                        GETDATE() as DATA_DOC,
                        '0001-01-01' as DATA_VALUTA,
                        '0001-01-01' as DATA_SCAD_PAG,
                        PARTITE.MAPAAPAR as ANNO_PARTITA,
                        PARTITE.MAPNRPAR as NR_PARTITA,
                        '0'as TRANPIVA,
                        '1' as TRATPVAL,
                        '2' as TRASEGNO,
                        (S.GSL0AUCA-S.GSL0DUCA) as TRAIMPVP,
                        '0' as TRAIIVVP,
                        '0' as TRAIVAVP,
                        S.GPD0CD as MOVT62CD
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    LEFT JOIN (
                        SELECT P.T01CD, P.MAPAAPAR, P.MAPNRPAR, S.R_COMMESSA,
                            ROW_NUMBER()OVER(PARTITION BY R_COMMESSA ORDER BY MAPAAPAR DESC,MAPNRPAR DESC) AS NUM
                        FROM FINANCE.BCMAPPT P
                        JOIN THIPPERS.YSTAT_FATVEN_V01 S on S.ID_AZIENDA = P.T01CD and P.MAPAAPAR = S.ANNO_FAT
                            and P.MAPNRPAR = S.NUMERO_FATTURA 
                        WHERE P.MAPSTAPA = 1 -- solo partite aperte
                        ) PARTITE ON PARTITE.T01CD=S.T01CD and PARTITE.R_COMMESSA=S.GPD0CD and NUM=1
                    WHERE GT01CD = 'BASE'
                        and S.T01CD = '$ID_AZIENDA'
                        and S.GT02CD = 'CONS'
                        and S.GSL0TPSL = 1
                        and S.GS02CD = '*****'
                        and S.GPV0CD not like 'ZZ%'
                        --and S.GPC0CD = 'CR001'
                        and S.GSL0DUCA <> S.GSL0AUCA
                        and S.GPD0CD = '$codCommessa'
                        and S.GPV0CD in ($conti_transitori_imploded)
                    GROUP BY S.T01CD,S.GPV0CD,S.GPD0CD,S.GAT0CD,S.GSL0AUCA,S.GSL0DUCA,PARTITE.MAPAAPAR,PARTITE.MAPNRPAR
                ";
        $panthera->execute_update($query1);

        

        $query2 = "INSERT INTO FINANCE.GIPNPT(
                        GT01CD,     -- dataset
                        DIZSTATO,
                        DIZUTCRE,
                        DIZDTCRE,
                        DIZHHCRE,
                        DIZUTAGG,
                        DIZDTAGG,
                        DIZHHAGG,
                        GIPNNATR,   -- Progressivo di input
                        GT05CD,     -- origine
                        GT14CD,     -- tipo numeratore
                        T01CD,      -- azienda
                        GIPNDTRE,   -- Data Registrazione
                        GIPNDTCM,   -- Data Competenza
                        GT02CD,     -- Subset
                        GT03CD,     -- Versione
                        GEV0CD,     -- Evento
                        GC28CD,     -- Alias
                        GT11CD,     -- Causale
                        GIPNRFOR,   -- nr rif Origine
                        GIPNDTOR,   -- data origine
                        GIPNRIFE,   -- nr rif. doc
                        GIPNDTDC,   -- data doc
                        GIPNRFAB,   -- nr rif abbinamento
                        GIPNDTAB,   -- data abbinamento
                        GIPNDTIC,   -- data inizio competenza
                        GIPNDTFC,   -- data fine competenza
                        GIPNAZCO,   -- azienda origine
                        VOCCD,      -- voce contabile
                        CLICD,      -- cliente
                        FORCD,      -- fornitore
                        GIPNRFL1,
                        GIPNRFL2,
                        GIPNRFL3,
                        GIPNDTL1,
                        GIPNDTL2,
                        GIPNDTL3,
                        T36CD,      -- divisione
                        GPV0CD,
                        GPC0CD,
                        GPD0CD,
                        GPS1CD,
                        GPS2CD,
                        GPS3CD,
                        GPS4CD,
                        GPS5CD,
                        GIPNDIV1,   -- divisione CP
                        VOCCPQ,     -- voce CP
                        CENCPQ,     -- centro CP
                        COMCPQ,     -- commessa CP
                        SEG1CPQ,    -- segmento CP
                        SEG2CPQ,
                        SEG3CPQ,
                        SEG4CPQ,
                        SEG5CPQ,
                        GIPNDSNO,   -- descrizione normale
                        GIPNIUCT,   -- importo UCT
                        GIPNIUCA,   -- importo UCA
                        GIPNIUCG,   -- importo UCG
                        GIPNQUNT,   -- quantita
                        GIPNSECO,   -- segno
                        GT18CD,     -- valuta
                        GIPNDCAT,   -- data cambio
                        GT12CD,     -- tipo cambio
                        GIPNCMBT,   -- cambio
                        GIPNDCAG,   -- data cambio gruppo
                        GIPNCAGR,   -- tipo cambio gruppo
                        GIPNCAMB,   -- cambio gruppo
                        GT04CD,     -- unita misura
                        GT15CD,     -- tipo unitario
                        GIPNUNIT,   -- unitario
                        GIPNSER1,
                        GIPNSER2,
                        GIPNSER3,
                        GIPNFLG1,
                        GIPNFLG2,
                        GIPNFLG3,
                        GIPNIMS1,
                        GIPNIMS2,
                        GIPNIMS3,
                        GIPNIMS4,
                        GIPNIMS5,
                        GIPNQSR1,
                        GIPNQSR2,
                        GIPNUNM1,
                        GIPNUNM2,
                        GIPNUNI1,
                        GIPNUNI2,
                        GIPNUNI3,
                        GIPNUNI4,
                        GIPNUNI5,
                        GIPNNAOR,       -- Numero assoluto Origine
                        GIPNPROR,       -- Riga origine
                        GIPNA256,       -- Criterio accorpamento
                        GIPNSEPA,       -- carattere separatore
                        GIPNTIOR,       -- Tipo Origine
                        GIPNTPIN,       -- Tipo Inserimento
                        GIPNTPPE,       -- Tipo Periodo
                        GIPNNLOG,       -- prog.elab.
                        T97CD,          -- Oggetto applicativo
                        GIPNDTEL,       -- DT Elaborazione
                        GIPNCHC1,       -- Check Elaborato
                        GIPNCHC2)       -- Check da Eliminare
                    SELECT
                        '$DATASET' as DATASET,
                        '1' as STATO,
                        '$utente' as UTENTE_CRZ,
                        CURRENT_DATE as DATA_CRZ,
                        CURRENT_TIME as ORA_CRZ,
                        '$utente' as UTENTE_AGG,
                        CURRENT_DATE as DATA_AGG,
                        CURRENT_TIME as ORA_AGG,
                        (ROW_NUMBER() OVER(ORDER BY S.T01CD)) * 2 as PROGRESSIVO,         -- GIPNPT ha un progressivo “univoco” (GIPNNATR) che va gestito riprendendolo e incrementandolo dal file GIPNNPT 
                        '$ORIGINE' as ORIGINE,
                        '$TP_NUMERATORE_AN' as TIPO_NUMERATORE,
                        S.T01CD as COD_AZIENDA,
                        GETDATE() as DATA_REG,
                        GETDATE() as DATA_COMPETENZA,
                        '$SUBSET' as SUBSET,
                        '' as VERSIONE,
                        '$EVENTO' as EVENTO,
                        '' as ALIAS,
                        '$CAU_AN' as CAUSALE,
                        '0001-01-01' as DATA_REG_ORIGINE,
                        '' as NUMERO_DOC,
                        '0001-01-01' as DATA_DOC,
                        '' as ABBINAMENTO,
                        '0001-01-01' as DATA_ABBINAMENTO,
                        '0001-01-01' as DATA_INIZIO_COMPETENZA,
                        '0001-01-01' as DATA_FINE_COMPETENZA,
                        S.T01CD as COD_AZIENDA_ORIGINE,
                        S.GPV0CD as VOCE_CONTABILE,
                        CASE WHEN CLICD !='' THEN S.CLICD ELSE S.GPS4CD END as COD_CLIENTE,
                        '' as COD_FORNITORE,
                        '' as RIF_LIB1,
                        '' as RIF_LIB2,
                        '' as RIF_LIB3,
                        '0001-01-01' as DATA_LIB1,
                        '0001-01-01' as DATA_LIB2,
                        '0001-01-01' as DATA_LIB3,
                        S.T36CD as COD_DIVISIONE,
                        S.GPV0CD as VOCE_GESTIONALE,
                        S.GPC0CD as CENTRO_COSTO,
                        S.GPD0CD as COD_COMMESSA,
                        '' as SEGM1,
                        '' as SEGM2,
                        '' as SEGM3,
                        '' as SEGM4,
                        '' as SEGM5,
                        S.T36CD as COD_DIVISIONE_PQ,
                        S.GPV0CD as VOCE_CONTABILE_PQ,
                        S.GPC0CD as CENTRO_COSTO_PQ,
                        S.GPD0CD as COD_COMMESSA_PQ,
                        '' as SEGM1_PQ,
                        '' as SEGM2_PQ,
                        '' as SEGM3_PQ,
                        '' as SEGM4_PQ,
                        '' as SEGM5_PQ,
                        'Giroconto Ricavi' as GIPN_DESCRIZIONE,
                        GSL0AUCA-GSL0DUCA as IMPORTO_VAL_TRANSAZ,
                        GSL0AUCA-GSL0DUCA as IMPORTO_VAL_AZ,
                        0 as IMPORTO_VAL_GRP,
                        0 as QTY,
                        '1' as SEGNO,   -- Impostare uguale a segno di BETRAPT
                        '' as VALUTA,
                        '0001-01-01' as DATA_CAMBIO_TRANSAZ,
                        '' as TIPO_CAMBIO_TRANSAZ,
                        1 as CAMBIO_TRANSAZ,
                        '0001-01-01' as DATA_CAMBIO_GRP,
                        '' as TIPO_CAMBIO_GRP,
                        0 as CAMBIO_GRP,
                        '' as UNITA_MISURA,
                        '' as TIPO_UNITARIO,
                        0 as UNITARITO,
                        '' as CAMPO_SERV1,
                        '' as CAMPO_SERV2,
                        '' as CAMPO_SERV3,
                        '' as FLAG_SERV1,
                        '' as FLAG_SERV2,
                        '' as FLAG_SERV3,
                        0 as IMP_SERV1,
                        0 as IMP_SERV2,
                        0 as IMP_SERV3,
                        0 as IMP_SERV4,
                        0 as IMP_SERV5,
                        0 as QTY_SERV1,
                        0 as QTY_SERV2,
                        '' as UNITA_MIS_SERV1,
                        '' as UNITA_MIS_SERV2,
                        0 as UNITARIO_SERV1,
                        0 as UNITARIO_SERV2,
                        0 as UNITARIO_SERV3,
                        0 as UNITARIO_SERV4,
                        0 as UNITARIO_SERV5,
                        NUM_REG, -- come quello di BETRAPT ???
                        NUM_RIGA, -- come quello di BETRAPT ???
                        '' as CHIAVE_ORIGINE,
                        '' as SEPARATORE,
                        '' as TIPO_ORIGINE,
                        '' as TIPO_INSERIMENTO,
                        '' as TIPO_PERIODO,
                        0 as PROGR_ELAB,
                        '' as OGGETTO_APPLICATIVO,
                        '0001-01-01' as DATA_ELAB,
                        '' as CHECK_ELABORATO,
                        '' as CHECK_DA_ELIMINARE
                        -- DOVE IMPOSTARE ARTICOLO E ARTICOLO RIF ?
                    FROM FINANCE.GSL0PT S
                    JOIN THIPPERS.YCOMMESSE C on C.ID_AZIENDA = S.T01CD and C.ID_COMMESSA = S.GPD0CD
                    JOIN THIP.COMMESSE CD on CD.ID_AZIENDA = S.T01CD and CD.ID_COMMESSA = S.GPD0CD
                    LEFT JOIN THIP.ARTICOLI AR on AR.ID_AZIENDA = S.T01CD and AR.ID_ARTICOLO = S.GPS2CD
                    LEFT JOIN THIP.ARTICOLI AR2 on AR2.ID_AZIENDA = S.T01CD and AR2.ID_ARTICOLO = S.GPS3CD
                    LEFT JOIN THIP.CLI_VEN_V01 CLI on CLI.ID_AZIENDA = S.T01CD and CLI.ID_CLIENTE = (CASE WHEN CLICD !='' THEN CLICD ELSE GPS4CD END)
                    WHERE GT01CD = 'BASE'
                        and T01CD = '$ID_AZIENDA'
                        and GT02CD = 'CONS'
                        and GSL0TPSL = 1
                        and GS02CD = '*****'
                        and GPV0CD not like 'ZZ%'
                        --and GPC0CD = 'CR001'
                        and GSL0DUCA <> GSL0AUCA
                        and S.GPD0CD = '$codCommessa'
                        and S.GPV0CD in ($conti_transitori_imploded)
  -- UNION $decode_conto
                ";
            $panthera->execute_update($query2);
    }
}
?>