<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

if(isset($_GET['uuid'])):
	$uuid = $_GET['uuid'];
endif;
if(isset($_POST['uuid'])):
	$uuid = $_POST['uuid'];
endif;

$pgtitle = [gtext("Extensions"), gtext('CBSD'),gtext('Utilities')];

if(isset($_GET['jailname'])):
	$container = $_GET['jailname'];
endif;
if(isset($_POST['jailname'])):
	$container = $_POST['jailname'];
endif;

$cnid = FALSE;
if(isset($container) && !empty($container)):
	$pconfig['uuid'] = uuid();
	$pconfig['jailname'] = $container;
	if(preg_match('/^([^\/\@]+)(\/([^\@]+))?\@(.*)$/', $pconfig['jailname'], $m)):
		$pconfig['name'] = $m[''];
	else:
		$pconfig['name'] = 'unknown';
	endif;
else:
	// not supported
	$pconfig = [];
endif;

if($_POST):
	global $configfile;
	global $backup_path;
	global $rootfolder;
	unset($input_errors);
	$pconfig = $_POST;
	if(isset($_POST['Cancel']) && $_POST['Cancel']):
		header('Location: cbsd_manager_gui.php');
		exit;
	endif;
	if(isset($_POST['action'])):
		$action = $_POST['action'];
	endif;
	if(empty($action)):
		$input_errors[] = sprintf(gtext("The attribute '%s' is required."), gtext("Action"));
	else:
		switch($action):
			case 'autoboot':
				// Input validation not required
				if(empty($input_errors)):
					$container = [];
					$container['uuid'] = $_POST['uuid'];
					$container['jailname'] = $_POST['jailname'];
					$confirm_name = $pconfig['confirmname'];
					$item = $container['jailname'];
					$cmd = ("/usr/local/bin/cbsd bset astart=1 jname={$item}");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($retval == 0):
						header('Location: cbsd_manager_gui.php');
						exit;
					else:
						$input_errors[] = gtext("Failed to set auto-boot.");
					endif;
				endif;
				break;

			case 'noauto':
				// Input validation not required
				if(empty($input_errors)):
					$container = [];
					$container['uuid'] = $_POST['uuid'];
					$container['jailname'] = $_POST['jailname'];
					$confirm_name = $pconfig['confirmname'];
					$item = $container['jailname'];
					$cmd = ("/usr/local/bin/cbsd bset astart=0 jname={$item}");
					unset($output,$retval);mwexec2($cmd,$output,$retval);
					if($retval == 0):
						header('Location: cbsd_manager_gui.php');
						exit;
					else:
						$input_errors[] = gtext("Failed to set no-auto.");
					endif;
				endif;
				break;

			case 'delete':
				// Delete a contained
				if(empty($input_errors)):
					$container = [];
					$container['uuid'] = $_POST['uuid'];
					$container['jailname'] = $_POST['jailname'];
					$confirm_name = $pconfig['confirmname'];
					$item = $container['jailname'];
					$plugin_icon = "{$image_dir}/{$item}_icon.png";

					if(strcmp($confirm_name, $item) !== 0):
						$input_errors[] = gtext("Failed to destroy VM, name confirmation is required.");
						break;
					else:
						$cmd = ("/usr/local/bin/cbsd bdestroy {$item}");
						unset($output,$retval);mwexec2($cmd,$output,$retval);
						if($retval == 0):
							header('Location: cbsd_manager_gui.php');
							exit;
						else:
							$input_errors[] = gtext("Failed to destroy VM, make sure this container is stopped.");
						endif;
					endif;
				endif;
				break;
			default:
				$input_errors[] = sprintf(gtext("The attribute '%s' is invalid."), 'action');
				break;
		endswitch;
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
function enable_change(enable_change) {
	document.iform.name.disabled = !enable_change;
}
function action_change() {
	showElementById('confirmname_tr','hide');
	showElementById('nowstop_tr', 'hide');
	showElementById('source_path_tr', 'hide');
	showElementById('target_path_tr', 'hide');
	showElementById('path_check_tr', 'hide');
	showElementById('advanced_tr', 'hide');
	showElementById('readonly_tr', 'hide');
	showElementById('createdir_tr', 'hide');
	showElementById('automount_tr', 'hide');
	showElementById('jail_release_tr', 'hide');
	showElementById('release_tr','hide');
	showElementById('update_base_tr','hide');
	showElementById('update_jail_tr','hide');
	showElementById('newname_tr', 'hide');
	showElementById('newipaddr_tr', 'hide');
	showElementById('clonestop_tr', 'hide');
	showElementById('auto_boot_tr', 'hide');
	showElementById('no_autoboot_tr', 'hide');
	showElementById('backup_tr', 'hide');
	showElementById('format_tr', 'hide');
	showElementById('safemode_tr', 'hide');
	//showElementById('dateadd_tr','hide');
	var action = document.iform.action.value;
	switch (action) {
		case "autoboot":
			showElementById('confirmname_tr','hide');
			showElementById('nowstop_tr','hide');
			showElementById('auto_boot_tr', 'show');
			break;
		case "noauto":
			showElementById('confirmname_tr','hide');
			showElementById('nowstop_tr','hide');
			showElementById('no_autoboot_tr', 'show');
			break;
		case "delete":
			showElementById('confirmname_tr','show');
			showElementById('nowstop_tr','show');
			break;
		default:
			break;
	}
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
			ins_tabnav_record('cbsd_manager_maintenance.php',gettext('Maintenance'),gettext('Reload page'),true);
$document->render();
?>
<form action="cbsd_manager_util.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
<?php
	global $sphere_notifier;
	if(!empty($errormsg)):
		print_error_box($errormsg);
	endif;
	if(!empty($savemsg)):
		print_info_box($savemsg);
	endif;
	if(!empty($input_errors)):
		print_input_errors($input_errors);
	endif;
	if(updatenotify_exists($sphere_notifier)):
		print_config_change_box();
	endif;
?>
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			html_titleline2(gettext('Utilities'));
?>
		</thead>
		<tbody>
<?php
			$b_action = $l_release;
			html_text2('jailname',gettext('Container name:'),htmlspecialchars($pconfig['jailname']));
			$a_action = [
				'autoboot' => gettext('Autoboot'),
				'noauto' => gettext('Noauto'),
				'delete' => gettext('Destroy'),
			];

			html_combobox2('action',gettext('Action'),!empty($pconfig['action']),$a_action,'',true,false,'action_change()');
			html_inputbox2('confirmname',gettext('Enter name for confirmation'),!empty($pconfig['confirmname']),'',true,30);
			html_inputbox2('newname',gettext('Enter a name for the new container'),!empty($pconfig['newname']),'',true,30);
			html_inputbox2('newipaddr',gettext('Enter a IP address for the new container'),!empty($pconfig['newipaddr']),'',true,30);
			html_text2('auto_boot',gettext('Enable container auto-startup'),htmlspecialchars("This will cause the VM to automatically start each time the system restart."));
			html_text2('no_autoboot',gettext('Disable container auto-startup'),htmlspecialchars("This will disable the VM automatic startup."));
?>
		</tbody>
	</table>
	<div id="submit">
		<input name="Submit" type="submit" class="formbtn" value="<?=gtext("Execute");?>" onclick="enable_change(true)" />
		<input name="Cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>" />
		<input name="uuid" type="hidden" value="<?=$pconfig['uuid'];?>" />
		<input name="jailname" type="hidden" value="<?=$pconfig['jailname'];?>" />
		<input name="name" type="hidden" value="<?=$pconfig['name'];?>" />
	</div>
	<div id="remarks">
		<?php html_remark("note", gtext("Note"), sprintf(gtext("Some tasks such as backups may render the WebGUI unresponsive until task completes.")));?>
	</div>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<script type="text/javascript">
<!--
enable_change(true);
action_change();
//-->
</script>
<?php
include 'fend.inc';
?>
