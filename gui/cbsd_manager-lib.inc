<?php
putenv("NOCOLOR=1");

require_once 'super_fun.inc';
require_once 'globals.inc';
require_once 'array.inc';
require_once 'system.inc';

// Initialize some variables.
// TODO: Some infos can be gathered with-
// internal PHP functions rather than external shell commands.

//$rootfolder = dirname($config['rc']['postinit']['cmd'][$i]);
$prdname = "cbsd";

if(empty($cbsd_version)):
	$return_val = 0;
	$output = [];
	exec("/usr/local/bin/cbsd version",$output,$return_val);
	if ( $return_val == 0 ):
		$cbsd_version = $output[0] ?? '';
	else:
		$cbsd_version = "";
	endif;
endif;

$extension_version = "13.1.16.0";
$application = "cbsd Manager";
$confdir = "/var/etc/cbsd_conf";
$rootfolder = "/usr/local/www/ext/cbsd-bhyve";
$configfile = "$rootfolder/conf/cbsd_config";
$configfile_cbsd = "$rootfolder/cbsd-dist/usr/local/etc/cbsd/cbsd.conf";
$date = date('D M d h:i:s Y', time());   // Equivalent date replacement for the previous strftime function.
$logfile = "$rootfolder/log/cbsd_ext.log";
$logevent = "$rootfolder/log/cbsd_last_event.log";
$cbsd_config = "$rootfolder/conf/cbsd_config";
$config_path = exec("/usr/bin/grep 'cbsd_CONFIG=' $configfile | /usr/bin/cut -d'\"' -f2");
$default_distfiles = exec("/usr/bin/grep 'cbsd_bootstrap_archives=' $config_path | /usr/bin/cut -d'\"' -f2");
$image_dir = "ext/cbsd-bhyve/images";
$host_version = exec("/bin/cat /etc/prd.version | tr -d '.'");
$workdir = exec("/usr/bin/grep '^cbsd_workdir=' /etc/rc.conf | /usr/bin/cut -d'\"' -f2");

// Ensure the root directory is configured.
if ($rootfolder == "")
	$input_errors[] = gtext("Extension installed with fault");
else {
	// Initialize locales.
	$textdomain = "/usr/local/share/locale";
	$textdomain_cbsd = "/usr/local/share/locale-cbsd";
	if (!is_link($textdomain_cbsd)) { mwexec("ln -s {$rootfolder}/locale-cbsd {$textdomain_cbsd}", true); }
	bindtextdomain("xigmanas", $textdomain_cbsd);
}
if (is_file("{$rootfolder}/postinit")) unlink("{$rootfolder}/postinit");

if ( !is_dir( "$rootfolder/log" ) ) {
	mkdir( "$rootfolder/log" );
}

// Check releases dir.
function is_dir_empty($reldir) {
	if (!is_readable($reldir)) return NULL; 
	return (count(scandir($reldir)) == 2);
}

// return cbsd version
function get_version_cbsd() {
	global $cbsd_version;
	return $cbsd_version;
}

// Get all base releases list.
function get_all_release_list() {
	global $workdir;

	exec("/bin/echo; /usr/local/bin/cbsd show_profile_list search_profile=vm-\*-cloud show_cloud=1 show_bhyve=1 uniq=1 display=path header=0 has_init=1 | /usr/bin/sed -e 's:.conf::g' -e \"s:{$workdir}/etc/defaults/vm-::g\" | /usr/bin/tr -s ' ' '\n';",$relinfo);
	array_shift($relinfo);
	$rellist = [];
	foreach($relinfo as $rel):
		$rellist[$rel] = $rel;
	endforeach;

	return $rellist;
}

// Get all pubkey list.
function get_all_pubkey_list() {
	global $rootfolder;
	global $g;

	exec("/bin/echo; /bin/ls {$rootfolder}/pubkey 2>/dev/null | /usr/bin/tr -s ' ' '\n'",$pubinfo);
	array_shift($pubinfo);
	$publist = [];
	foreach($pubinfo as $pub):
		$apub = preg_split("/\s+/",ltrim($pub));
		$pubname = chop($apub[0]);
		if(substr($pubname,-1) === '*'):
			$pubname = substr($pubname,0,strlen($pubname) - 1);
		endif;
		$publist[$pubname] = [];
	endforeach;
	return $publist;
}


// Get all interface list.
function get_all_interface_list() {
	global $g;
	exec("/bin/echo; /sbin/ifconfig -l | /usr/bin/tr -s ' ' '\n'; /bin/echo 'Config'",$linkinfo);
	array_shift($linkinfo);
	$iflist = [];
	foreach($linkinfo as $link):
		$alink = preg_split("/\s+/",ltrim($link));
		$ifname = chop($alink[0]);
		if(substr($ifname,-1) === '*'):
			$ifname = substr($ifname,0,strlen($ifname) - 1);
		endif;
		$iflist[$ifname] = [];
	endforeach;
	return $iflist;
}

//	list base releases
$a_release = get_all_release_list();
$l_release = [];
foreach($a_release as $k_release => $release):
	$l_release[$k_release] = $k_release;
endforeach;

//	list of configured interfaces
$a_interface = get_all_interface_list();
$l_interfaces = [];
foreach($a_interface as $k_interface => $ifinfo):
	$l_interfaces[$k_interface] = $k_interface;
endforeach;


//	list pubkeys
$a_pubkey = get_all_pubkey_list();
$l_pubkey = [];
foreach($a_pubkey as $k_pubkey => $pubkey):
	$l_pubkey[$k_pubkey] = $k_pubkey;
endforeach;

// list cpu
$l_cpu = [
	'1' => '1',
	'2' => '2',
	'3' => '3',
	'4' => '4',
	'5' => '5',
	'6' => '6',
	'7' => '7',
	'8' => '8',
	'9' => '9',
	'10' => '10',
	'11' => '11',
	'12' => '12',
	'13' => '13',
	'14' => '14',
	'15' => '15',
	'16' => '16',
];

$l_vnc_bind = [
	'0.0.0.0' => gettext('0.0.0.0'),
	'127.0.0.1' => gettext('127.0.0.1'),
];

// Get jail infos.
function get_jail_infos() {
	global $workdir;
	global $img_path;
	global $image_dir;
	global $configfile;
	global $jail_dir;
	$result = [];
	$cmd = "/usr/local/bin/cbsd bls display=jid,jname,ip4_addr,vm_os_type,status,astart,vm_ram,vm_cpus,vnc header=0";
	mwexec2($cmd,$rawdata);
	foreach($rawdata as $line):
		$line = preg_replace('/\s+/', ' ', $line);

		list($jid,$name,$ip4_addr,$vm_os_type,$status,$astart,$vm_ram,$vm_cpus,$vnc)=explode(' ',$line);

		$r = [];
		$r['name'] = $name;
		$r['jailname'] = $name;
		$r['id'] = $jid;
		$item = $r['jailname'];
		$r['ip'] = $ip4_addr;
		$r['rel'] = $vm_os_type;
		// Display interfaces.
		$r['nic'] = "-";
		// Display path.
		$r['path'] = "-";
		// Display auto-start settings.
		if ($astart == '1') {
			$r['boot'] = $img_path['ena'];
		} else {
			$r['boot'] = $img_path['dis'];
		}
		$r['status'] = $status;
		if ( $r['status'] == 'On' ) {
			$cloud_user = exec("/usr/bin/grep '^ci_user_add=' {$workdir}/jails-system/{$name}/rc.conf_{$name} | /usr/bin/tr -d \"'\" | /usr/bin/cut -d'=' -f2");
			$r['ssh_string'] = "ssh {$cloud_user}@{$ip4_addr}";
			if(file_exists("{$workdir}/jails-system/{$name}/vnc_port2")):
				$ip2=trim(file_get_contents("{$workdir}/jails-system/{$name}/vnc_port2"));
				$r['ssh_string2'] = "ssh {$cloud_user}@{$ip2}";
			else:
				$r['ssh_string2'] = "nop";
			endif;
			$r['stat'] = $img_path['ena'];
		} else {
			$r['ssh_string'] = "";
			$r['stat'] = $img_path['dis'];
		}
		$r['ram'] = $vm_ram;
		$r['cpus'] = $vm_cpus;
		$r['vnc'] = $vnc;
		// Display custom template icons if available.
		$r['logo'] = "{$image_dir}/{$vm_os_type}_icon.png";

		$result[] = $r;
	endforeach;
	return $result;
}
?>
