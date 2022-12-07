<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

$pgtitle = [gtext("Extensions"), gtext('CBSD'),gtext('Pubkey')];

$sphere_array = [];
$sphere_record = [];
$pconfig = [];

if(!(isset($pconfig['pubkey']))):
	if(file_exists("{$rootfolder}/pubkey/default")):
		$pconfig['pubkey'] = file_get_contents("{$rootfolder}/pubkey/default");
	else:
		$pconfig['pubkey'] = '';
	endif;
endif;

function get_pub_list() {
	global $rootfolder;
	$result = [];

	if(file_exists("{$rootfolder}/pubkey/default")):
		$r['pubkey'] = 'default';
		$result[] = $r;
	endif;

	return $result;
}
$pub_list = get_pub_list();
$sphere_array = $pub_list;

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
		if (!is_dir("{$rootfolder}/pubkey")):
			mkdir("{$rootfolder}/pubkey");
		endif;

		if (isset($pconfig['pubkey'])):
			$fp=fopen("{$rootfolder}/pubkey/default","w");
			fputs($fp,$pconfig['pubkey']);
			fclose($fp);
		endif;

		if(file_exists("{$rootfolder}/pubkey/default")):
			$pconfig['pubkey'] = file_get_contents("{$rootfolder}/pubkey/default");
			if(strlen($pconfig['pubkey'])<10):
				unlink("{$rootfolder}/pubkey/default");
			endif;
		endif;
		header('Location: cbsd_manager_gui.php');
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
<form action="cbsd_manager_pubkey.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
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
			html_titleline2(gettext('Manage pubkey string'));
?>
		</thead>
		<tbody>
<?php
if(!empty($cbsd_version)):
			html_inputbox2('pubkey',gettext('pubkey string:'),$pconfig['pubkey'],'',true,130);
endif;
?>
		</tbody>
	</table>

<?php
if(!empty($cbsd_version)):
?>
	<div id="submit">
		<input name="Apply" type="submit" class="formbtn" value="<?=gtext("Apply");?>" onclick="enable_change(true)" />
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
	</div>
		<?php html_remark("note", gtext("Note"), sprintf(gtext("Current version supports only one public key")));?>
	</div>
<?php
endif;
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<?php
include 'fend.inc';
?>
