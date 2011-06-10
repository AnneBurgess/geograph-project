{include file="_std_begin.tpl"}
<script src="{"/sorttable.js"|revision}"></script>

<div style="float:right;position:relative">&middot; <a href="/help/submission">Alternative Submission Methods</a> &middot;</div>

	<h2>Multiple Image Submission <sup>Experimental</sup></h2>

<div style="background-color:pink; color:black; border:2px solid red; padding:20px; margin-bottom:30px;">
	<img src="http://{$static_host}/templates/basic/img/icon_alert.gif" alt="Alert" width="50" height="44" align="left" style="margin-right:10px"/>

	You are using the old Category based submission process. Please consider switching to the new <a href="/switch.php">Tagging based interface</a>.

	<br/><br/>Please <a href="https://spreadsheets.google.com/spreadsheet/viewform?formkey=dFpiTjJsTEZRRXVwZ2pxWXdXczY2ZGc6MQ" target="_blank">tell us why</a> you havent switched yet - Thanks!
</div>


<div style="position:relative;">
	<div class="tabHolder">
		<a class="tab nowrap" id="tab1" href="{$script_name}">A) Add/Upload Images</a>&nbsp;
		<a class="tabSelected nowrap" id="tab2">B) Submit Images</a>
	</div>

	<div class="interestBox">

			<fieldset style="width:300px;float:left">
				<legend>Submission Method</legend>
				<input type="radio" name="meth" value="/submit.php" checked /> Submit v1<br/>
				<span style="color:gray"><input type="radio" name="meth" value="/submit2.php" disabled/> Submit v2 (coming soon)<br/>
				<input type="radio" name="meth" value="/submit2.php?display=tabs" disabled/> Submit v2 Tabs (coming soon)</span>
			</fieldset>

			<fieldset style="width:300px;float:left">
				<legend>Set Location</legend>

				<form action="#" onsubmit="return copytoall(this)">
					Subject Grid Reference: <input type="text" name="grid_reference" size="6"/>
					<input type="submit" value="Copy to all"/>
				</form>
			</fieldset>
		<br style="clear:both"/>

		<table id="upload" class="report sortable">
			<thead>
			<tr style="color:yellow">
				<th>Preview</th>
				<th>Continue</th>
				<th>Uploaded</th>
				<th>Taken</th>
				<th>Done</th>
			</tr>
			</thead>
			<tbody>
			{dynamic}
			{foreach from=$data item=item}

				<tr>
					<td><a href="/submit.php?preview={$item.transfer_id}" target="_blank"><img src="/submit.php?preview={$item.transfer_id}" width="160"/></a></td>
					<td><form action="/submit.php" method="post" target="_blank" style="margin:0; background-color:lightgrey; padding:5px">
						Subject GR: <input type="text" name="grid_reference" size="10" value="{$item.grid_reference}"/> {if $item.grid_reference}<small>{$item.grid_reference} from EXIF</small>{/if}<br/>
						{if $item.photographer_gridref}Photographer: <input type="text" name="photographer_gridref" size="10" value="{$item.photographer_gridref}"/><br/> <small style="font-size:0.7em">{$item.photographer_gridref} from EXIF</small><br/>{/if}

						<br/><input type="hidden" name="gridsquare" value="1">

						<input type="hidden" name="transfer_id" value="{$item.transfer_id}">

						<input type="submit" value="continue &gt;">

					</form></td>
					<td sortvalue="{$item.uploaded}">{$item.uploaded|date_format:"%a, %e %b %Y at %H:%M"}</td>
					<td sortvalue="{$item.imagetaken}">{if $item.imagetaken}{$item.imagetaken|date_format:"%a, %e %b %Y at %H:%M"}{/if}</td>
					<td><input type="checkbox"/></td>

				</tr>
			{foreachelse}
				<tr><td colspan="4">click "Add/Upload Images" above, and send us some images!</td></tr>
			{/foreach}
			{/dynamic}
			</tbody>
		</table>


	</div>
</div>
{literal}
<script type="text/javascript">
        function copytoall(that) {
                f = document.forms;
                for(q=0;q<f.length;q++) {
                        if (f[q] != that && f[q].grid_reference) {
                                f[q].grid_reference.value = that.grid_reference.value;
                        }
                }
                return false;
        }
</script>{/literal}

{include file="_std_end.tpl"}
