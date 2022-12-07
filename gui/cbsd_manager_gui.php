<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once 'cbsd_manager-lib.inc';

$sphere_scriptname = basename(__FILE__);
$sphere_scriptname_child = 'cbsd_manager_util.php';
$sphere_header = 'Location: '.$sphere_scriptname;
$sphere_header_parent = $sphere_header;
$sphere_array = [];
$sphere_record = [];
$checkbox_member_name = 'checkbox_member_array';
$checkbox_member_array = [];
$checkbox_member_record = [];
$gt_record_add = gtext('Create new VM');
$gt_record_mod = gtext('Utilities');
$gt_selection_start = gtext('Start Selected');
$gt_selection_stop = gtext('Stop Selected');
$gt_selection_restart = gtext('Restart Selected');
$gt_record_conf = gtext('VM Configuration');
$gt_record_inf = gtext('Information');
$gt_selection_start_confirm = gtext('Do you really want to start selected VM(s)?');
$gt_selection_stop_confirm = gtext('Do you want to stop the selected VM(s)?');
$gt_selection_restart_confirm = gtext('Do you want to restart the selected VM(s)?');
$img_path = [
	'add' => 'images/add.png',
	'mod' => 'images/edit.png',
	'del' => 'images/delete.png',
	'loc' => 'images/locked.png',
	'unl' => 'images/unlocked.png',
	'mai' => 'images/maintain.png',
	'inf' => 'images/info.png',
	'ena' => 'images/status_enabled.png',
	'dis' => 'images/status_disabled.png',
	'mup' => 'images/up.png',
	'mdn' => 'images/down.png'
];

$jls_list = get_jail_infos();
$sphere_array = $jls_list;

if(empty($cbsd_version)):
	$errormsg = gtext('cbsd Initial Configuration:')
			. ' '
			. '<a href="' . 'cbsd_manager_config.php' . '">'
			. gtext('Please check and configure CBSD workdir/network first.')
			. '</a>'
			. '</br>';
endif;

if($_POST):
	if(isset($_POST['apply']) && $_POST['apply']):
		$ret = array('output' => [], 'retval' => 0);
		if(!file_exists($d_sysrebootreqd_path)):
			// Process notifications
		endif;
		$savemsg = get_std_save_message($ret['retval']);
		if($ret['retval'] == 0):
			updatenotify_delete($sphere_notifier);
			header($sphere_header);
			exit;
		endif;
		updatenotify_delete($sphere_notifier);
		$errormsg = implode("\n", $ret['output']);
	endif;

	if(isset($_POST['start_selected_jail']) && $_POST['start_selected_jail']):
		$checkbox_member_array = isset($_POST[$checkbox_member_name]) ? $_POST[$checkbox_member_name] : [];
		foreach($checkbox_member_array as $checkbox_member_record):
			if(false !== ($index = array_search_ex($checkbox_member_record, $sphere_array, 'jailname'))):
				if(!isset($sphere_array[$index]['protected'])):
					$cmd = ("/usr/local/bin/cbsd bstart {$checkbox_member_record} > {$logevent} 2>&1");
					$return_val = mwexec($cmd);
					if($return_val == 0):
						//$savemsg .= gtext("Jail(s) started successfully.");
						header($sphere_header);
					else:
						$errormsg .= gtext("Failed to start VM(s).");
					endif;
				endif;
			endif;
		endforeach;
	endif;

	if(isset($_POST['stop_selected_jail']) && $_POST['stop_selected_jail']):
		$checkbox_member_array = isset($_POST[$checkbox_member_name]) ? $_POST[$checkbox_member_name] : [];
		foreach($checkbox_member_array as $checkbox_member_record):
			if(false !== ($index = array_search_ex($checkbox_member_record, $sphere_array, 'jailname'))):
				if(!isset($sphere_array[$index]['protected'])):
					$cmd = ("/usr/local/bin/cbsd bstop {$checkbox_member_record}");
					$return_val = mwexec($cmd);
					if($return_val == 0):
						//$savemsg .= gtext("Jail(s) stopped successfully.");
						header($sphere_header);
					else:
						$errormsg .= gtext("Failed to stop VM(s).");
					endif;
				endif;
			endif;
		endforeach;
	endif;

	if(isset($_POST['restart_selected_jail']) && $_POST['restart_selected_jail']):
		$checkbox_member_array = isset($_POST[$checkbox_member_name]) ? $_POST[$checkbox_member_name] : [];
		foreach($checkbox_member_array as $checkbox_member_record):
			if(false !== ($index = array_search_ex($checkbox_member_record, $sphere_array, 'jailname'))):
				if(!isset($sphere_array[$index]['protected'])):
					$cmd = ("/usr/local/bin/cbsd brestart {$checkbox_member_record}");
					$return_val = mwexec($cmd);
					if($return_val == 0):
						//$savemsg .= gtext("Jail(s) restarted successfully.");
						header($sphere_header);
					else:
						$errormsg .= gtext("Failed to restart VM(s).");
					endif;
				endif;
			endif;
		endforeach;
	endif;
endif;

$pgtitle = [gtext("Extensions"), gtext('cbsd')];
include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load", function() {
	// Init action buttons
	$("#start_selected_jail").click(function () {
		return confirm('<?=$gt_selection_start_confirm;?>');
	});
	$("#stop_selected_jail").click(function () {
		return confirm('<?=$gt_selection_stop_confirm;?>');
	});
	$("#restart_selected_jail").click(function () {
		return confirm('<?=$gt_selection_restart_confirm;?>');
	});
	// Disable action buttons.
	disableactionbuttons(true);

	// Init member checkboxes
	$("input[name='<?=$checkbox_member_name;?>[]']").click(function() {
		controlactionbuttons(this, '<?=$checkbox_member_name;?>[]');
	});
	// Init spinner onsubmit()
	$("#iform").submit(function() { spinner(); });
	$(".spin").click(function() { spinner(); });
});
function disableactionbuttons(ab_disable) {
	$("#start_selected_jail").prop("disabled", ab_disable);
	$("#stop_selected_jail").prop("disabled", ab_disable);
	$("#restart_selected_jail").prop("disabled", ab_disable);
}

function controlactionbuttons(ego, triggerbyname) {
	var a_trigger = document.getElementsByName(triggerbyname);
	var n_trigger = a_trigger.length;
	var ab_disable = true;
	var i = 0;
	for (; i < n_trigger; i++) {
		if (a_trigger[i].type == 'checkbox') {
			if (a_trigger[i].checked) {
				ab_disable = false;
				break;
			}
		}
	}
	disableactionbuttons(ab_disable);
}
//]]>
</script>
<?php
$document = new co_DOMDocument();
$document->
	add_area_tabnav()->
		push()->
		add_tabnav_upper()->
			ins_tabnav_record('cbsd_manager_gui.php',gettext('VM'))->
			ins_tabnav_record('cbsd_manager_info.php',gettext('Information'))->
			ins_tabnav_record('cbsd_manager_maintenance.php',gettext('Maintenance'));
$document->render();
?>
<form action="cbsd_manager_gui.php" method="post" name="iform" id="iform"><table id="area_data"><tbody><tr><td id="area_data_frame">
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
?>
		</thead>
		<tbody>
<?php
?>
		</tbody>
	</table>
	<table class="area_data_selection">
		<colgroup>
			<col style="width:3%">
			<col style="width:3%">
			<col style="width:5%">
			<col style="width:5%">
			<col style="width:5%">
			<col style="width:3%">
			<col style="width:3%">
			<col style="width:15%">
			<col style="width:5%">
			<col style="width:2%">
			<col style="width:2%">
			<col style="width:3%">
		</colgroup>
		<thead>
<?php
			html_separator2();
			html_titleline2(gettext('Overview'), 12);
?>
			<tr>
				<th class="lhelc"><?=gtext('Select');?></th>
				<th class="lhell"><?=gtext('PID');?></th>
				<th class="lhell"><?=gtext('IP Address');?></th>
				<th class="lhell"><?=gtext('Name');?></th>
				<th class="lhell"><?=gtext('CPUs');?></th>
				<th class="lhell"><?=gtext('RAM');?></th>
				<th class="lhell"><?=gtext('OS');?></th>
				<th class="lhell"><?=gtext('SSH string');?></th>
				<th class="lhell"><?=gtext('VNC');?></th>
				<th class="lhell"><?=gtext('Boot');?></th>
				<th class="lhell"><?=gtext('Active');?></th>
				<th class="lhebl"><?=gtext('Toolbox');?></th>
			</tr>
		</thead>
		<tbody>
<?php
			global $identifier;
			foreach ($sphere_array as $sphere_record):
				$notificationmode = updatenotify_get_mode($sphere_notifier, $identifier);
				$notdirty = (UPDATENOTIFY_MODE_DIRTY != $notificationmode) && (UPDATENOTIFY_MODE_DIRTY_CONFIG != $notificationmode);
				$notprotected = !isset($sphere_record['protected']);
				if ( $sphere_record['status'] == 'On' ):
					echo "<tr style=\"background-color:#d2ffe1\">";
				else:
					echo "<tr style=\"background-color:#eeeeee\">";
				endif;
?>
					<td class="lcelc">
<?php
						if ($notdirty && $notprotected):
?>
							<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$sphere_record['jailname'];?>" id="<?=$sphere_record['jailname'];?>"/>
<?php
						else:
?>
							<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$sphere_record['jailname'];?>" id="<?=$sphere_record['jailname'];?>" disabled="disabled"/>
<?php
						endif;

					$ssh1_len=strlen($sphere_record['ssh_string']);
					$ssh2_len=strlen($sphere_record['ssh_string2']);
					$ssh1_len--;
					$ssh2_len--;
?>
					</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['id']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['ip']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['name']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['cpus']);?>&nbsp;</td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['ram']);?>&nbsp;</td>
					<td class="lcell"><img src="<?=$sphere_record['logo'];?>"><?=htmlspecialchars($sphere_record['rel']);?>&nbsp;</td>
					<td class="lcell"><input type="text" minlength="<?=$ssh1_len;?>" maxlength="<?=$ssh1_len;?>" size="<?=$ssh1_len;?>"  value="<?=htmlspecialchars($sphere_record['ssh_string']);?>" readonly> / <input type="text" minlength="<?=$ssh2_len;?>" maxlength="<?=$ssh2_len;?>" size="<?=$ssh2_len;?>" value="<?=htmlspecialchars($sphere_record['ssh_string2']);?>" readonly></td>
					<td class="lcell"><?=htmlspecialchars($sphere_record['vnc']);?>&nbsp;</td>
					<td class="lcell"><img src="<?=$sphere_record['boot'];?>"></td>
					<td class="lcell"><img src="<?=$sphere_record['stat'];?>"></td>
					<td class="lcebld">
						<table class="area_data_selection_toolbox"><tbody><tr>
							<td>
<?php
								if($notdirty && $notprotected):
?>
									<a href="<?=$sphere_scriptname_child;?>?jailname=<?=urlencode($sphere_record['jailname']);?>"><img src="<?=$img_path['mai'];?>" title="<?=$gt_record_mod;?>" alt="<?=$gt_record_mod;?>"  class="spin oneemhigh"/></a>
<?php
								else:
									if ($notprotected):
?>
										<img src="<?=$img_path['del'];?>" title="<?=$gt_record_del;?>" alt="<?=$gt_record_del;?>"/>
<?php
									else:
?>
										<img src="<?=$img_path['loc'];?>" title="<?=$gt_record_loc;?>" alt="<?=$gt_record_loc;?>"/>
<?php
									endif;
								endif;
?>
						</tr></tbody></table>
					</td>
				</tr>
<?php
			endforeach;
?>
		</tbody>
		<tfoot>
			<tr>
				<td class="lcenl" colspan="11"></td>
<?php
				if(empty($cbsd_version)):
?>
					&nbsp;
<?php
				else:
?>
					<td class="lceadd">
						<a href="cbsd_manager_add.php"><img src="<?=$img_path['add'];?>" title="<?=$gt_record_add;?>" border="0" alt="<?=$gt_record_add;?>" class="spin oneemhigh"/></a>
					</td>
<?php
				endif;
?>
			</tr>
		</tfoot>
	</table>
	<div id="submit">
		<input name="start_selected_jail" id="start_selected_jail" type="submit" class="formbtn" value="<?=$gt_selection_start;?>"/>
		<input name="stop_selected_jail" id="stop_selected_jail" type="submit" class="formbtn" value="<?=$gt_selection_stop;?>"/>
		<input name="restart_selected_jail" id="restart_selected_jail" type="submit" class="formbtn" value="<?=$gt_selection_restart;?>"/>
	</div>
<?php
	include 'formend.inc';
?>
</td></tr></tbody></table></form>
<?php
include 'fend.inc';
