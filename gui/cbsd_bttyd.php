<?php
//TODO: Implement STREAM proxy to ttyd.sock unix socket instead of BASIC AUTH
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

if($_GET):
	if(isset($_GET['jid'])):
		$jid=$_GET['jid'];
	else:
		die();
	endif;
	if(isset($_GET['jname'])):
		$jname=$_GET['jname'];
	else:
		$jname="JID${jid}";
	endif;
else:
	echo "No jid";
	die();
//	endif;
endif;

$secret=md5(uniqid(rand(), true));
$cmd="ttyd -o -m 1 -t titleFixed=\"jail:${jname}\" -c ${secret}:${secret} --writable -p 7681 /usr/local/bin/cbsd blogin jname=${jname}";

header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: post-check=0, pre-check=0",false);
header("Pragma: no-cache");

exec("/bin/pkill -9 ttyd; /usr/sbin/daemon -f ${cmd}; sleep 2;",$output,$return_val);
if ( $return_val == 0 ):

	//$server_name=$_SERVER['SERVER_NAME'];
	$http_host=$_SERVER['HTTP_HOST'];
	$url="http://${secret}:${secret}@${http_host}:7681";
	echo "<meta http-equiv=\"refresh\" content=\"0; url=${url}\" />";
else:
	print_r($output);
endif;

die();
include 'fend.inc';
