<?php 

/* L'applicazione non prevede db locale */

define('JWT_SECRET_KEY', 'OSAISECRET2021');

/* LDAP / ACTIVE DIRECTORY */
define('AD_SERVER', 'ldap://osai.loc');
define('AD_DOMAIN', 'OSAI.LOC');
define('AD_BASE_DN', "dc=OSAI,dc=LOC");
define('AD_FILTER_READONLY', '(objectclass=person)');
define('AD_FILTER_READWRITE', '(objectclass=person)');
// define('AD_FILTER', '(&(|(objectclass=person))(|(|(memberof=CN=OSAI-IT Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=1202))(|(memberof=CN=OSAI-DE Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=2625))(|(memberof=CN=OSAI-CN Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=3233))(|(memberof=CN=OSAI-US Users,OU=OU Osai Groups,DC=osai,DC=loc)(primaryGroupID=4426))))');

/* DATABASE PANTHERA */
define('MOCK_PANTHERA', 'true');
define('DB_PTH_HOST', 'tcp:myserver.database.windows.net,1433');
define('DB_PTH_USER', 'my_user');
define('DB_PTH_PASS', 'my_pwd');
define('DB_PTH_NAME', 'PANTH01');

/*produzione
define('MOCK_PANTHERA', 'false');
define('DB_PTH_HOST', 'tcp:svr-pth,1433');
define('DB_PTH_USER', 'finsoft');
define('DB_PTH_PASS', '**************');
define('DB_PTH_NAME', 'PANTH01');
*/

$matrice_conti = [
	// conto transitorio => conto ricavi
    '606002' => '901001',
    '606004' => '901002',
	'606005' => '901003'
];

/* stati del workflow da gestire */
$STATO_WF_START = '030';
$STATO_WF_END = '040';

/* altre costanti Panthera */
$ID_AZIENDA = '001';
$CENTRO_COSTO = 'CR001';
$NUMERATORE = 'GEN';
$CAU_CONTABILE = 'GCR';
$DATASET = 'BASE';
$SUBSET = 'CONS';
$ORIGINE = 'PRIM';
$CAU_AN = 'GE';
$TP_NUMERATORE_AN = 'CG';
$NUMERATORE_AN = 'GC';
$EVENTO = 'COGE_E';
$CENTRO_COSTO_AN = 'CC999';
$CONTO_Z = 'ZZCONTR';
$CENTRO_COSTO_Z = 'ZZCONTR';

?>