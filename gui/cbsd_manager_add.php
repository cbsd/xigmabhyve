<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

global $configfile;
global $workdir;

$prerequisites_ok = "true";
$pgtitle = array(gtext("Extensions"), "CBSD", "Create");
$pconfig = [];

if(!(isset($pconfig['jailname']))):
	$pconfig['jailname'] = 'vm1';
endif;
if(!(isset($pconfig['ipaddress']))):
	$pconfig['ipaddress'] = '';
endif;

if(!(isset($pconfig['cpu']))):
	if(file_exists( $configfile )):
		$pconfig['cpu'] = exec("/usr/bin/grep '^last_cpu_created=' {$configfile} 2>/dev/null | /usr/bin/cut -d'\"' -f2");
		if(empty($pconfig['cpu'])):
			$pconfig['cpu'] = '1';
		endif;
	else:
		$pconfig['cpu'] = '1';
	endif;
endif;

if(!(isset($pconfig['ram']))):
	if(file_exists( $configfile )):
		$pconfig['ram'] = exec("/usr/bin/grep '^last_ram_created=' {$configfile} 2>/dev/null | /usr/bin/cut -d'\"' -f2");
		if(empty($pconfig['ram'])):
			$pconfig['ram'] = '1g';
		endif;
	else:
		$pconfig['ram'] = '1g';
	endif;
endif;

if(!(isset($pconfig['imgsize']))):
	if(file_exists( $configfile )):
		$pconfig['imgsize'] = exec("/usr/bin/grep '^last_imgsize_created=' {$configfile} 2>/dev/null | /usr/bin/cut -d'\"' -f2");
		if(empty($pconfig['imgsize'])):
			$pconfig['imgsize'] = '14g';
		endif;
	else:
		$pconfig['imgsize'] = '14g';
	endif;
endif;

if(!(isset($pconfig['vnc_bind']))):
	$pconfig['vnc_bind'] = '127.0.0.1';
endif;

if(!file_exists("{$workdir}/cmd.subr")):
	$errormsg = gtext('CBSD workdir not initialized yet.')
			. ' '
			. '<a href="' . 'cbsd_manager_config.php' . '">'
			. gtext('Please init CBSD workdir first.')
			. '</a>';
		$prerequisites_ok = false;
else:
	if(!get_all_release_list()):
		$errormsg = gtext('No gold images downloaded yet.')
				. ' '
				. '<a href="' . 'cbsd_manager_golds.php' . '">'
				. gtext('Please download a image first.')
				. '</a>';
			$prerequisites_ok = false;
	endif;

	if(!get_all_pubkey_list()):
		$errormsg = gtext('No public key added yet.')
				. ' '
				. '<a href="' . 'cbsd_manager_pubkey.php' . '">'
				. gtext('Please add public key first.')
				. '</a>';
			$prerequisites_ok = false;
	endif;
endif;

if($_POST):
	global $jail_dir;
//	global $configfile;
	unset($input_errors);
	$pconfig = $_POST;
	if(isset($_POST['Cancel']) && $_POST['Cancel']):
		header('Location: cbsd_manager_gui.php');
		exit;
	endif;
	if(isset($_POST['Create']) && $_POST['Create']):
		$jname = $pconfig['jailname'];
		$ipaddr = $pconfig['ipaddress'];
		$release = $pconfig['release'];
		$cpu = $pconfig['cpu'];
		$ram = $pconfig['ram'];
		$imgsize = $pconfig['imgsize'];
		$vnc_bind = $pconfig['vnc_bind'];
		$options = "";
		if ($_POST['interface'] == 'Config'):
			$interface = "";
		else:
			$interface = $pconfig['interface'];
		endif;

		$profile_path = sprintf('/usr/local/cbsd/etc/defaults/vm-%1$s.conf',$release);
		$vm_os_type = exec("/usr/bin/grep '^vm_os_type=' {$profile_path} | /usr/bin/cut -d'\"' -f2");
		$vm_os_profile = exec("/usr/bin/grep '^vm_profile=' {$profile_path} | /usr/bin/cut -d'\"' -f2");
		exec("/usr/sbin/sysrc -f {$configfile} last_release_created=\"{$release}\" last_cpu_created=\"{$cpu}\" last_ram_created=\"{$ram}\" last_imgsize_created=\"{$imgsize}\"");

		if (isset($_POST['autostart'])):
			$astart="1";
		else:
			$astart="0";
		endif;

		if (isset($_POST['nowstart'])):
			$cmd = ("/usr/local/bin/cbsd bcreate jname={$jname} astart={$astart} vm_ram={$ram} vm_cpus={$cpu} vm_os_type={$vm_os_type} vm_os_profile={$vm_os_profile} imgsize={$imgsize} bhyve_vnc_tcp_bind={$vnc_bind} ci_ip4_addr={$ipaddr} ci_user_pubkey=\"{$rootfolder}/pubkey/default\" runasap=1");
		else:
			$cmd = ("/usr/local/bin/cbsd bcreate jname={$jname} astart={$astart} vm_ram={$ram} vm_cpus={$cpu} vm_os_type={$vm_os_type} vm_os_profile={$vm_os_profile} imgsize={$imgsize} bhyve_vnc_tcp_bind={$vnc_bind} ci_ip4_addr={$ipaddr} ci_user_pubkey=\"{$rootfolder}/pubkey/default\"");
		endif;

		if ($_POST['Create']):
			if(get_all_release_list()):
				unset($output,$retval);mwexec2($cmd,$output,$retval);
				if($retval == 0):
					header('Location: cbsd_manager_gui.php');
					exit;
				else:
					$errormsg .= gtext("Failed to create VM.");
				endif;
			else:
				$errormsg .= gtext(" <<< Failed to create VM.");
			endif;
		endif;
	endif;
endif;

include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load",function() {
	$("#iform").submit(function() { spinner(); });
	$(".spin").click(function() { spinner(); });
});
//]]>
</script>
<?php
$document = new co_DOMDocument();
$document->
	add_area_tabnav()->
		push()->
		add_tabnav_upper()->
			ins_tabnav_record('cbsd_manager_gui.php',gettext('VM'),gettext('Reload page'),true)->
			ins_tabnav_record('cbsd_manager_info.php',gettext('Information'),gettext('Reload page'),true)->
			ins_tabnav_record('cbsd_manager_maintenance.php',gettext('Maintenance'),gettext('Reload page'),true);
$document->render();
?>
<form action="cbsd_manager_add.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
<?php
	if(!empty($errormsg)):
		print_error_box($errormsg);
	endif;
	if(!empty($savemsg)):
		print_info_box($savemsg);
	endif;
	if(!empty($input_errors)):
		print_input_errors($input_errors);
	endif;
	if(file_exists($d_sysrebootreqd_path)):
		print_info_box(get_std_save_message(0));
	endif;
?>
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			html_titleline2(gettext('Create new VM'));
?>
		</thead>
		<tbody>
<?php
			if($prerequisites_ok != false ):
				exec("/usr/local/bin/cbsd freejname default_jailname=vm",$jname);
				exec("/usr/local/bin/cbsd dhcpd",$ip4_addr);
			else:
				$ip4_addr="";
				$jname="";
			endif;
//			$a_action = $l_interfaces;
			$a_action = [ 'cbsd0' => 'cbsd0' ];
			$b_action = $l_release;
			$c_action = $l_pubkey;
			$d_action = $l_cpu;
			$e_action = $l_vnc_bind;

			$host_cpu = @exec("/sbin/sysctl -q -n hw.ncpu");

			if(file_exists("{$rootfolder}/pubkey/default")):
				$pubkey = file_get_contents("{$rootfolder}/pubkey/default");
				$pubkey_pieces = explode(' ', $pubkey);
				$pubkey_comment = "(".array_pop($pubkey_pieces).")";
			else:
				$pubkey_comment = '';
			endif;
			html_inputbox2('jailname',gettext('VM name'),$jname[0],'',true,20);

			$cpu_default_option = '1';
			html_combobox2('cpu',gettext("vCPU (Host Core Num: {$host_cpu})"),array_key_exists($pconfig['cpu'] ?? '',$d_action) ? $pconfig['cpu'] : $cpu_default_options ,$d_action,'',true,false,'type_change()');
			html_inputbox2('ram',gettext('VM RAM (1g, 4g, ..)'),$pconfig['ram'],"",true,20);
			html_inputbox2('imgsize',gettext('Disk size (20g, 40g, ..)'),$pconfig['imgsize'],'',true,20);
			html_inputbox2('ipaddress',gettext('IP Address'),$ip4_addr[0],'',true,20);
			html_combobox2('interface',gettext('Network interface'),!empty($pconfig['interface']),$a_action,'',true,false);
			html_combobox2('vnc_bind',gettext('VNC bind'),!empty($pconfig['vnc_bind']),$l_vnc_bind,'',true,false);

			if(file_exists( $configfile )):
				$pconfig['release'] = exec("/usr/bin/grep '^last_release_created=' {$configfile} 2>/dev/null | /usr/bin/cut -d'\"' -f2");
			endif;

			html_combobox2('release',gettext('Profile name'),array_key_exists($pconfig['release'] ?? '',$b_action) ? $pconfig['release'] : $default_options ,$b_action,'<a href="cbsd_manager_golds.php"><span>Warm more (Gold image libraries)</span></a>',true,false,'type_change()');
			html_combobox2('pubkey',  gettext('Pubkey'),!empty($pconfig['pubkey']),$c_action,"{$pubkey_comment}",true,false);
			html_checkbox2('nowstart',gettext('Start after creation'),!empty($pconfig['nowstart']) ? true : false,gettext('Start the VM after creation(May be overridden by later cbsd releases).'),'',false);
			html_checkbox2('autostart',gettext('Auto start on boot'),!empty($pconfig['autostart']) ? true : false,gettext('Automatically start the VM at boot time.'),'',false);
?>
		</tbody>
	</table>
<?php
	if($prerequisites_ok != false ):
?>
	<div id="submit">
		<input name="Create" type="submit" class="formbtn" value="<?=gtext('Create');?>"/>
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext('Cancel');?>" />
	</div>
<?php
	endif;
?>

<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<script type="text/javascript">
<!--
emptyjail_change();
linuxjail_change();
//-->
</script>
<?php
include 'fend.inc';
?>
