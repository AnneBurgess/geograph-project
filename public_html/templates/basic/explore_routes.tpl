{assign var="page_title" value="$h2title :: Explore"}
{include file="_std_begin.tpl"}
<script src="/sorttable.js"></script>

{if $filter}
    <form method="get" action="{$script_name}">
    <p>{if $references}In <select name="ri">
    	{html_options options=$references selected=$ri}
    </select>{/if}
    {if $i}
    	<input type="checkbox" name="i" value="{$i}" checked="checked" 
    	id="i"/><label for="i">Limited to <a href="/search.php?i={$i}">Search</a></label>
    {/if}
    {foreach from=$extra key=name item=value}
    	<input type="hidden" name="{$name}" value="{$value}"/>
    {/foreach}
    <input type="submit" value="Go"/></p></form>
 {/if}  

	<h3>{$h2title}</h3>
	
	<p style="font-size:0.8em">The routes accessible from this page simply show the first geograph in the same 1km gridsquares that the route passes through. In many cases the image wont be approriate for the route, or indeed very close to the actual route (but in allcases should be within about 1.4km)</p>
	
{if $total > 0}
	
	<table class="report sortable" id="reportlist" border="1" bordercolor="#dddddd" cellspacing="0" cellpadding="5">
	<thead><tr>
	{foreach from=$table.0 key=name item=value}
	<td>{$name}</td>
	{/foreach}

	</tr></thead>
	<tbody>


	{foreach from=$table item=row}
	<tr>
		{foreach from=$row key=name item=value}
			<td align="right">{$value}</td>
		{/foreach}
	</tr>
	{/foreach}
	
	

	</tbody>
	</table>
	
	{if $footnote}
		{$footnote}
	{/if}
{else}
	<p><i>No Results to Display</i></p>
{/if}

<p style="background-color:#eeeeee;padding:10px">For more information on National Trails see {external href="http://www.nationaltrail.co.uk/" text="nationaltrail.co.uk"}, <br/>and for details of Cycle Routes see {external href="http://www.systrans.org.uk/" text="sustrans.org.uk"}.</p>

<p>Searches generated by this page only return first geographs, so prevent highly saturated squares taking over the search, in the future hope to 'choose' the best picture based on the relevence to the route.</p>

<div class="copyright">Where applicable routes featured on this page, Based upon Ordnance Survey&reg Landranger&reg; 1:50 000 scale map with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright. Educational licence 100045616. National Cycle Routes reproduced with permission of Sustrans Ltd, &copy; copyright 2006.</div>


{include file="_std_end.tpl"}
