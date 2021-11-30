<?php 

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,PUT,POST,DELETE");
header("Access-Control-Allow-Headers: Accept,Content-Type,Authorization,Origin,X-Auth-Token");
# Chrome funziona anche con Access-Control-Allow-Headers: *  invece Firefox no

require 'vendor/autoload.php';

include("config.php");
include("functions.php");
include("class_panthera.php");
include("class_ldap.php");
