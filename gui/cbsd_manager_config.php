<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

$pgtitle = [gtext("Extensions"), gtext('CBSD'),gtext('Pubkey')];

$sphere_array = [];
$sphere_record = [];
$pconfig = [];

if(!(isset($pconfig['cbsd_rdr']))):
	if(file_exists("{$rootfolder}/conf/rdr")):
		$pconfig['cbsd_rdr'] = trim(file_get_contents("{$rootfolder}/conf/rdr"));
		if ( $pconfig['cbsd_rdr'] == 'no' ):
			$pconfig['cbsd_rdr']='0';
		endif;
	else:
		$pconfig['cbsd_rdr']='1';
	endif;
endif;

if(!(isset($pconfig['cbsd_nat']))):
	if(file_exists("{$rootfolder}/conf/nat")):
		$pconfig['cbsd_nat'] = trim(file_get_contents("{$rootfolder}/conf/nat"));
		if ( $pconfig['cbsd_nat'] == 'no' ):
			$pconfig['cbsd_nat']='0';
		endif;
	else:
		$pconfig['cbsd_nat']='1';
	endif;
endif;

if(!(isset($pconfig['cbsd_iface']))):
	if(file_exists("{$rootfolder}/conf/iface")):
		$pconfig['cbsd_iface'] = file_get_contents("{$rootfolder}/conf/iface");
	else:
		$pconfig['cbsd_iface'] = 'cbsd0';
	endif;
endif;

if(!(isset($pconfig['cbsd_workdir']))):
	if(file_exists("{$rootfolder}/conf/workdir")):
		$pconfig['cbsd_workdir'] = file_get_contents("{$rootfolder}/conf/workdir");
	else:
		$pconfig['cbsd_workdir'] = '';
	endif;
endif;

if(!(isset($pconfig['cbsd_gw4']))):
	if(file_exists("{$rootfolder}/conf/gw4")):
		$pconfig['cbsd_gw4'] = file_get_contents("{$rootfolder}/conf/gw4");
	else:
		$pconfig['cbsd_gw4'] = '10.0.0.1';
	endif;
endif;

if(!(isset($pconfig['cbsd_net']))):
	if(file_exists("{$rootfolder}/conf/net")):
		$pconfig['cbsd_net'] = file_get_contents("{$rootfolder}/conf/net");
	else:
		$pconfig['cbsd_net'] = '10.0.0.0/24';
	endif;
endif;

if($_POST):
	unset($input_errors);
	unset($errormsg);
	unset($savemsg);
	$pconfig = $_POST;

	if(isset($_POST['Cancel']) && $_POST['Cancel']):
		header('Location: cbsd_manager_gui.php');
		exit;
	endif;

	if (isset($_POST['Apply']) && $_POST['Apply']):
		if (!is_dir("{$rootfolder}/conf")):
			mkdir("{$rootfolder}/conf");
		endif;

		if (isset($pconfig['cbsd_workdir'])):
			$fp=fopen("{$rootfolder}/conf/workdir.new","w");
			fputs($fp,$pconfig['cbsd_workdir']);
			fclose($fp);
		endif;
		if (isset($pconfig['cbsd_iface'])):
			$fp=fopen("{$rootfolder}/conf/iface.new","w");
			fputs($fp,$pconfig['cbsd_iface']);
			fclose($fp);
		endif;
		if (isset($pconfig['cbsd_gw4'])):
			$fp=fopen("{$rootfolder}/conf/gw4.new","w");
			fputs($fp,$pconfig['cbsd_gw4']);
			fclose($fp);
		endif;
		if (isset($pconfig['cbsd_net'])):
			$fp=fopen("{$rootfolder}/conf/net.new","w");
			fputs($fp,$pconfig['cbsd_net']);
			fclose($fp);
		endif;
		if (isset($pconfig['cbsd_nat'])):
			$fp=fopen("{$rootfolder}/conf/nat.new","w");
			fputs($fp,$pconfig['cbsd_nat']);
			fclose($fp);
		else:
			$fp=fopen("{$rootfolder}/conf/nat.new","w");
			fputs($fp,'no');
			fclose($fp);
		endif;
		if (isset($pconfig['cbsd_rdr'])):
			$fp=fopen("{$rootfolder}/conf/rdr.new","w");
			fputs($fp,$pconfig['cbsd_rdr']);
			fclose($fp);
		else:
			$fp=fopen("{$rootfolder}/conf/rdr.new","w");
			fputs($fp,'no');
			fclose($fp);
		endif;

		$return_val = 0;
		$output = [];
		exec("{$rootfolder}/utils/init",$output,$return_val);
		if($return_val == 0):
			header('Location: cbsd_manager_gui.php');
			exit;
		else:
			unset($input_errors);
			unset($errormsg);
			$errormsg = "";
			ob_start();
			include('/var/log/cbsd-init.log');
			$ausgabe = ob_get_contents();
			$ausgabe = preg_replace('/\e[[][A-Za-z0-9];?[0-9]*m?/', '', $ausgabe);
			ob_end_clean();
			$errormsg .= str_replace("\n", "<br />", $ausgabe)."<br />";
		endif;
	endif;
endif;

include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load",function() {
	// Init action buttons
	$("#iform").submit(function() { spinner(); });
	$(".spin").click(function() { spinner(); });
});
function enable_change(enable_change) {
	document.iform.name.disabled = !enable_change;
}
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
			ins_tabnav_record('cbsd_manager_maintenance.php',gettext('Maintenance'),gettext('Reload page'),true)->
			pop()->add_tabnav_lower()->
				ins_tabnav_record('cbsd_manager_config.php',gettext('CBSD Configuration'),gettext('Reload page'),true)->
				ins_tabnav_record('cbsd_manager_golds.php',gettext('Gold Images'),gettext('Reload page'),true)->
				ins_tabnav_record('cbsd_manager_pubkey.php',gettext('Pubkey'),gettext('Reload page'),true);
$document->render();
?>
<form action="cbsd_manager_config.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
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
			html_separator();
			html_titleline2(gettext('CBSD extension global settings'));
?>
		</thead>
		<tbody>
<?php
			html_filechooser("cbsd_workdir", gettext("CBSD workdir"),  $pconfig['cbsd_workdir'], sprintf(gettext("The %s MUST be set to a directory below: %s. Make sure you have enough space fo VM."), gettext("CBSD workdir"), "<b>'{$pconfig['cbsd_workdir']}'</b>"), true, 60);
			html_combobox2('cbsd_iface',gettext('VM interface name'),$pconfig['cbsd_iface'] ?? '',[ 'cbsd0' => 'cbsd0' ],'(current version supports only one network interface)',true,false,'type_change()');
			html_inputbox2('cbsd_gw4',gettext('Init IP on cbsd_iface (GW4 for VM)'),$pconfig['cbsd_gw4'],'',true,15);
			html_inputbox2('cbsd_net',gettext('Network for VM, e.g: 10.0.0.0/24 or 10.0.0.1-50'),$pconfig['cbsd_net'],'',true,30);
			html_checkbox2('cbsd_nat',gettext('Enable NAT via CBSD/pf ?'),!empty($pconfig['cbsd_nat']) ? true : false,'','(learn: <a href="cbsd_manager_info.php">Arhcitecture Info</a>)',false);
			html_checkbox2('cbsd_rdr',gettext('Redirect 22 (when SSH) and/or 3389 (when RDP) port from XigmaNAS external IP / free port (auto) to VM'),!empty($pconfig['cbsd_rdr']) ? true : false,'','(learn: <a href="cbsd_manager_info.php">Arhcitecture Info</a>)',false);
?>
		</tbody>
	</table>
	<div id="submit">
		<input name="Apply" type="submit" class="formbtn" value="<?=gtext("Apply");?>" onclick="enable_change(true)" />
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
	</div>
	</div>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<?php
include 'fend.inc';
?>
