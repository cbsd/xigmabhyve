<?php
require("auth.inc");
require("guiconfig.inc");
require_once("cbsd_manager-lib.inc");

$pgtitle = array(gtext("Extensions"), "CBSD", "Maintenance");

if ($_POST) {
	global $retval;
	global $zfs_activated;
	global $backup_path_cbsd;
	global $configfile_cbsd;

	// Remove only extension related files during cleanup.
	if (isset($_POST['uninstall']) && $_POST['uninstall']) {
		if(isset($_POST['delete_confirm']) && $_POST['delete_confirm']):
			bindtextdomain("xigmanas", $textdomain);
			mwexec("/usr/local/www/ext/cbsd-bhyve/utils/uninstall", true);
			header("Location:index.php");
		else:
			$input_errors[] = gtext('Confirmation is required for extension removal.');
		endif;
	}
}

function get_version_ext() {
	global $extension_version;
	//todo: extract from file?
	return ($extension_version);
}

if (is_ajax()) {
	$getinfo['cbsd'] = $cbsd_version;
	$getinfo['ext'] = get_version_ext();
	render_ajax($getinfo);
}

bindtextdomain("xigmanas", $textdomain);
include("fbegin.inc");
bindtextdomain("xigmanas", $textdomain_cbsd);
?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 2000, 'cbsd-gui.php', null, function(data) {
		$('#getinfo').html(data.info);
		$('#getinfo_cbsd').html(data.cbsd);
		$('#getinfo_ext').html(data.ext);
	});
});
//]]>
</script>
<!-- The Spinner Elements -->
<script src="js/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<script type="text/javascript">
<!--
}
//-->
</script>
<form action="cbsd_manager_maintenance.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabinact"><a href="cbsd_manager_gui.php"><span><?=gettext("VM");?></span></a></li>
				<li class="tabact"><a href="cbsd_manager_info.php"><span><?=gettext("Information");?></span></a></li>
			<li class="tabact"><a href="cbsd_manager_maintenance.php"><span><?=gettext("Maintenance");?></span></a></li>
		</ul>
	</td></tr>
		<tr><td class="tabnavtbl">
<?php
if(!empty($cbsd_version)):
?>
		<ul id="tabnav2">
			<li class="tabact"><a href="cbsd_manager_config.php"><span><?=gettext("CBSD Configuration");?></span></a></li>
			<li class="tabact"><a href="cbsd_manager_golds.php"><span><?=gettext("Gold Images");?></span></a></li>
			<li class="tabact"><a href="cbsd_manager_pubkey.php"><span><?=gettext("Pubkey");?></span></a></li>
		</ul>
<?php
endif;
?>
		</td></tr>
		<tr><td class="tabcont">
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php
				if(!empty($errormsg)): print_error_box($errormsg); endif;
				if(!empty($savemsg)): print_info_box($savemsg); endif;
				if(!empty($input_errors)): print_input_errors($input_errors); endif;
				if(file_exists($d_sysrebootreqd_path)): print_info_box(get_std_save_message(0)); endif;
				?>
				<?php html_titleline(gtext("CBSD"));?>
				<?php html_text("installation_directory", gtext("Installation directory"), sprintf(gtext("The extension is installed in %s"), $rootfolder));?>
				<tr>
					<td class="vncellt"><?=gtext("CBSD version");?></td>
					<td class="vtable"><span name="getinfo_cbsd" id="getinfo_cbsd"><?=get_version_cbsd()?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><?=gtext("Extension version");?></td>
					<td class="vtable"><span name="getinfo_ext" id="getinfo_ext"><?=get_version_ext()?></span></td>
				</tr>
			</table>
			<div id="remarks">
				<?php html_remark("note", gtext("Info"), sprintf(gtext("For general information visit the following link(s):")));?>
				<div id="enumeration"><ul><li><a href="https://www.bsdstore.ru/en/" target="_blank" ><?=gtext("CBSD framework.")?></a></li></ul></div>
			</div>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_separator();?>
				<?php html_titleline(gtext("Uninstall"));?>
				<?php html_checkbox2('delete_confirm',gtext('Uninstall confirm'),'' ? true : false,gtext('Check to confirm extension uninstall. Note: all your virtual machines will be destroyed!'),'',false);?>
				<?php html_separator();?>
			</table>
			<div id="submit1">
				<input name="uninstall" type="submit" class="formbtn" title="<?=gtext("Uninstall Extension");?>" value="<?=gtext("Uninstall");?>" onclick="return confirm('<?=gtext("CBSD Extension and packages will be completely removed, CBSD containers and child directories will not be touched, really to proceed?");?>')" />
			</div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
