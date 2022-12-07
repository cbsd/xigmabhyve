<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

global $workdir;

$gt_selection_delete_confirm = gtext('Do you really want to destroy this image?');
$pgtitle = [gtext("Extensions"), gtext('CBSD'),gtext('Images')];

$sphere_array = [];
$sphere_record = [];
$pconfig = [];

$prerequisites_ok = "true";

if(!file_exists("{$workdir}/cmd.subr")):
	$errormsg = gtext('CBSD workdir not initialized yet.')
			. ' '
			. '<a href="' . 'cbsd_manager_config.php' . '">'
			. gtext('Please init CBSD workdir first.')
			. '</a>';
			$prerequisites_ok = false;
			unset($cbsd_version);
endif;

if(!empty($cbsd_version)):

	exec("/bin/echo; /usr/local/bin/cbsd show_profile_list search_profile=vm-\*-cloud show_cloud=1 show_bhyve=1 uniq=1 display=path header=0 | /usr/bin/sed -e 's:.conf::g' -e \"s:{$workdir}/etc/defaults/vm-::g\" | /usr/bin/tr -s ' ' '\n';",$profileinfo);
	array_shift($profileinfo);
	$profilelist = [];
	foreach($profileinfo as $profile):
		$profilelist[$profile] = $profile;
	endforeach;
	$a_action = $profilelist;
else:
	$prerequisites_ok = false;
	$a_action = [];
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

	if (isset($_POST['Download']) && $_POST['Download']):
		$lib32 = "";
		$ports = "";
		$src = "";
		$get_release = $pconfig['release_item'];
		$cmd = sprintf('/usr/local/bin/cbsd fetch_iso path="/usr/local/cbsd/etc/defaults/vm-%1$s.conf" conv2zvol=1 keepname=0 dstdir=default fastscan=1 cloud=1 > %2$s 2>&1',$get_release,$logevent);

		if ($_POST['Download']):
			$savemsg = "";
			$errormsg = "";
			$return_val = 0;
			$output = [];
			exec($cmd,$output,$return_val);
			if($return_val == 0):
				ob_start();
				include("{$logevent}");
				$ausgabe = ob_get_contents();
				$ausgabe = preg_replace('/\e[[][A-Za-z0-9];?[0-9]*m?/', '', $ausgabe);
				ob_end_clean();
				$savemsg .= str_replace("\n", "<br />", $ausgabe)."<br />";
			else:
				$errpart = trim(file_get_contents($logevent));
				$errormsg .= sprintf(gtext('%s Failed to download and/or extract image: [%s].'),$get_release,$errpart);
			endif;
		endif;
	endif;

	if (isset($_POST['Destroy']) && $_POST['Destroy']):
		if ($_POST['Destroy']):
			$get_release = $pconfig['release_item'];
			$iso_img = exec("/usr/bin/grep '^iso_img=' /usr/local/cbsd/etc/defaults/vm-{$get_release}.conf 2>/dev/null | /usr/bin/grep \.raw | /usr/bin/tr -d \"'\\\"\" | /usr/bin/cut -d'=' -f2");

			if (empty($iso_img)):
				$savemsg .= sprintf(gtext('%s unable to get iso_img.'),$get_release);
				$retval = 1;
			else:
				// IS ZFS?
				$is_zfs = exec("/sbin/zfs list 2>/dev/null | /usr/bin/grep \"{$iso_img} \" | /usr/bin/awk '{printf $1}'");
				if(!empty($is_zfs)):
					// try to destroy via zfs
					unset($output,$retval);mwexec2("/sbin/zfs destroy {$is_zfs} 2>&1",$output,$retval);
					if( $retval != 0 ):
						$errormsg = $output[0];
					endif;
				else:
					if (file_exists("{$workdir}/src/iso/cbsd-cloud-{$iso_img}")):
						unlink("{$workdir}/src/iso/cbsd-cloud-{$iso_img}");
					elseif(file_exists("{$workdir}/src/iso/cbsd-{$iso_img}")):
						unlink("{$workdir}/src/iso/cbsd-{$iso_img}");
					elseif(file_exists("{$workdir}/src/iso/{$iso_img}")):
						unlink("{$workdir}/src/iso/{$iso_img}");
					endif;
					$retval = 0;
				endif;
			endif;
			// Delete the FreeBSD base release/directory.
			if ($_POST['Destroy']):
				//unset($output,$retval);mwexec2($cmd,$output,$retval);
				if($retval == 0):
					//$savemsg .= sprintf(gtext('%s base deleted successfully.'),$get_release);
					header('Location: cbsd_manager_golds.php');
				else:
					$errormsg .= sprintf(gtext('%s failed to delete.'),$get_release);
				endif;
			endif;
		endif;
	endif;
else:
	if(!empty($a_release)):
		$savemsg = "Init:<br>";
		$savemsg .= implode("<br>", $a_release);
	endif;
endif;

include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load",function() {
	// Init action buttons
	$("#Destroy").click(function () {
		return confirm('<?=$gt_selection_delete_confirm;?>');
	});
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
<form action="cbsd_manager_golds.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
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
			html_titleline2(gettext('Init CBSD Gold images'));
?>
		</thead>
		<tbody>
<?php
			html_combobox2('release_item',gettext('Select Image'),!empty($pconfig['release_item']),$a_action,'',true,false);
?>
		</tbody>
	</table>
<?php
	if($prerequisites_ok != false ):
?>
	<div id="submit">
		<input name="Download" type="submit" class="formbtn" value="<?=gtext("Download");?>" onclick="enable_change(true)" />
		<input name="Destroy" id="Destroy" type="submit" class="formbtn" value="<?=gtext("Destroy");?>"/>
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
	</div>
	<div id="remarks">
		<?php html_remark("note", gtext("Note"), sprintf(gtext("Slow Internet connections may render the Web GUI unresponsive until download completes.")));?>
	</div>
<?php
endif;
?>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<?php
include 'fend.inc';
?>
