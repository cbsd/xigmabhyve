<?php
require_once 'auth.inc';
require_once 'guiconfig.inc';
require_once("cbsd_manager-lib.inc");

function cbsd_summary(string $entity_name = NULL) {
	$cmd = '/usr/local/bin/cbsd summary && /usr/local/bin/cbsd cpu-topology && /usr/local/bin/cbsd bls';
	unset($output);
	mwexec2($cmd,$output);
	return implode(PHP_EOL,$output);
}

$entity_name = NULL;
$pgtitle = [gtext("Extensions"), gtext('CBSD'),gtext('Information')];
include 'fbegin.inc';
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

<?php
	if(!empty($errormsg)):
		print_error_box($errormsg);
	endif;
	if(!empty($input_errors)):
		print_input_errors($input_errors);
	endif;
	if(file_exists($d_sysrebootreqd_path)):
		print_info_box(get_std_save_message(0));
	endif;
?>

<table id="area_data"><tbody><tr><td id="area_data_frame">
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			html_titleline2(gettext('CBSD Summary'));
?>
		</thead>
		<tbody>
			<tr>
				<td class="celltag"><?=gtext('Summary');?></td>
				<td class="celldata">
					<pre><span id="cbsd_summary"><?=cbsd_summary($entity_name);?></span></pre>
				</td>
			</tr>
		</tbody>
		<tfoot>
<?php
			html_separator2();
?>
		</tfoot>
	</table>
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			html_titleline2(gettext('Errata/Requirements'));
?>
		</thead>
		<tbody>
			<tr>
				<td class="celltag"><?=gtext('Arch Overview');?></td>
				<td class="celldata">
					<span id="errata">
<h2>This is an alpha version with some limitations</h2>
<br>
<p><strong>1</strong>: The current implementation is designed to work with cloud images.
Cloud images are the image of the operating system already ready for use (you do not need for installation stage every time), 
which makes these images attractive, since they save your time.
</p>
<br>
<p>The general flow is as follows:</p>
<br>
<a href="/ext/cbsd-bhyve/images/xigmanas_gen1.png"><img src="/ext/cbsd-bhyve/images/xigmanas_gen1.png" alt="" width="600" height="400"></a>
<br>
<br>
<p>
Thus, one of the main requirements is the availability (for XigmaNAS instance) of resources from the Internet using the HTTP(s) protocol;
</p>
<br><br>
<p><strong>2</strong>: Under the hood, network settings and VM configuration looks like this:</p>
<br>
<a href="/ext/cbsd-bhyve/images/xigmanas_gen2.png"><img src="/ext/cbsd-bhyve/images/xigmanas_gen2.png" alt="" width="600" height="400"></a>
<br>
<br>
<p>CBSD extension creates a bridge (if_bridge) named 'cbsd0' and assign IPv4 (ci_gw4 params) address on it. 
Virtual machines receive network configuration from CBSD via `cloud-init`.
In this case, the address on the 'cbsd0' interface will act as the default gateway for virtual machines.
Of course, the network must be the same for the virtual machine addresses and the gateway. E.g.:
</p>
<table border=1>
	<tr><td>Network for VM (example)</td><td>Init IP on cbsd_iface (GW4 for VM) (example)</td></tr>
	<tr><td><strong>10.0.0.0/24</strong></td><td><strong>10.0.0.1</strong></td></tr>
	<tr><td><strong>172.16.0.0/24</strong></td><td><strong>172.16.0.1</strong></td></tr>
	<tr><td><strong>192.168.0.2-30</strong> <em>(use range from 192.168.0.2 to 192.168.0.30)</em></td><td><strong>192.168.0.1</strong></td></tr>
</table>
<br>
<p>
You can turn off the automatic CBSD NAT and redirects through 'pf' via settings:
</p>
<ul>
	<li>[ ] Enable NAT via CBSD/pf ?</li>
	<li>[ ] Redirect 22 (when SSH) and/or 3389 (when RDP) port from XigmaNAS external IP / free port (auto) to VM</li>
</ul>
<br>
<p>But in this case, you must configure NAT/RDR for VM in the XigmaNAS by yourself.
</p>
<br><br>
<p><strong>3</strong>: Errata</p>
<br>
<p>
If you regularly update the CBSD, you get new versions of profiles, while previously created virtual machines can use outdated 'gold' images.
You must periodically control the images and if you are no longer needed (and are not used) - delete them. E.g, on ZFS-based system:
</p>
<pre>
1) zfs list |grep cbsd |grep raw
2) zfs destroy name_of_orphaned_zvol.raw
</pre>
<p>For non-ZFS system:</p>
<pre>
1) ls -la ~cbsd/src/iso/
2) rm -f ~cbsd/src/iso/name_of_orphaned_zvol.raw
</pre>
					</span>
				</td>
			</tr>
		<tbody>
	</table>
<tbody>	
</td></tr></tbody></table>
<?php
include 'fend.inc';
?>
