{assign var="page_title" value="Geograph Admin"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent li { padding-bottom:10px;}
</style>{/literal}
{dynamic}

<h2>Administrative Tools</h2>
<ul>
{if $is_mod} 

<li><a href="/admin/moderation.php">Moderate</a> new photo submissions<br/>
<b>[{$images_pending} pending, {dynamic}{$images_pending_available}{/dynamic} available to moderate]</b></li>
{/if}

{if $is_tickmod} 
<li><a title="Trouble Tickets" href="/admin/tickets.php">Trouble Tickets</a> <small>(Sidebar: <a title="Trouble Tickets" href="/admin/tickets.php?sidebar=1" target="_search">IE &amp; Firefox</a>, <a title="Trouble Tickets" href="/admin/tickets.php?sidebar=1" rel="sidebar" title="Tickets">Opera</a>)</small> - 
   Deal with image problems<br/> <b>[{$tickets_new} new, {$tickets_yours} open by you]</b></li>
{/if}



{if $is_mod} 

{if $articles_ready}
<li><a href="/article/">Articles</a><br/>
<b>[{$articles_ready} ready to be approved]</b></li>
{/if}

<li{if $gridsquares_sea_test > 0} style="color:lightgrey">
<b>Map-fixing in Progress</b> - please come back later.<br/>
{else}>{/if}


<div style="position:relative;float:right; border:1px solid silver">
<form method="get" action="/admin/mapfixer.php" style="display:inline">
<label for="gridref">Grid Reference:</label><br/>
<input type="text" size="6" name="gridref" id="gridref" value="{$gridref|escape:'html'}"/>
<span class="formerror">{$gridref_error}</span>
<input type="submit" name="show" value="Check"/>
</form>
</div>

<a title="Map Fixer" href="/admin/mapfixer.php">Map Fixer</a> allows the land percentage
for each 1km grid squares to be updated, which allows "square is all at sea" to be 
corrected<br/> <b>{if $gridsquares_sea.1 || $gridsquares_sea.2}[GB:{$gridsquares_sea.1},I:{$gridsquares_sea.2} in queue]{/if}</b> - <a href="/mapfixer.php">add to queue</a><br/>
</li>

<li><a title="Recreate Maps" href="/recreatemaps.php">Recreate Maps</a> - 
   request update for map tiles</li>

<li><a title="Picture of the day" href="/admin/pictureoftheday.php">Picture of the Day</a> - 
   choose daily picture selections</li>
{/if}

<li>Stats: <a href="/statistics/admin_turnaround.php">Turn Around</a> - 
   rough estimate at moderation times</li>
</ul>

<h2>Total Submissions</h2>
<img src="http://www.geograph.org.uk/img/submission_graph.png" width="480" height="161"/>

<h2>Daily Submission Rate</h2>
<img src="http://www.geograph.org.uk/img/rate.png" width="480" height="161"/>

{if $is_mod} 
<br/><br/>
<h4>Remoderate a Square</h4>
<div style="position:relative;padding:10px">
<form method="get" action="/search.php" style="display:inline">
<label for="gridref">Grid Reference:</label>
<input type="text" size="6" name="gridref" id="gridref" value="{$gridref|escape:'html'}"/>
<span class="formerror">{$gridref_error}</span>
<input type="submit" name="do" value="Moderate"/>
<input type="hidden" name="distance" value="1"/>
<input type="hidden" name="orderby" value="submitted"/>
<input type="hidden" name="displayclass" value="moremod"/>
<input type="hidden" name="resultsperpage" value="100"/>
</form>
</div>
{/if}

{if $is_admin}
<br/><br/>
<h2>Admin Tools - use with care</h2>
<ul>

<li><a title="Moderators" href="/admin/moderator_admin.php">Moderator Admin</a> - 
   grant/revoke moderator rights to users</li>

<li><a title="API Keys" href="/admin/apikeys.php">API Keys</a> - 
   setup who has access to the API</li>

<li><a title="Category Consolidation" href="/admin/categories.php">Category Consolidation</a> - 
   Organise the user submitted categories</li>

</ul>
<h3>Statistics</h3>
<ul>  

<li><a title="Web Stats" href="/statistics/pulse.php">Geograph Pulse</a> - 
   upto the minute general site status</li>

<li><a title="Web Stats" href="http://www.geograph.org.uk/logs/">Web Stats</a> - 
   check the apache activity logs (outdated)</li>

<li><a title="Forum Stats" href="/discuss/?action=stats">Forum Stats</a> - 
   view forum activity stats</li>


   
<li><a title="Search Stats" href="/admin/viewsearches.php">Search Statistics</a> - See the recent Search Activity (very slow)</li>

<li><a title="Events" href="events.php">Event Diagnostics</a> - see what the event system is doing</li>

<li><a title="Server Stats" href="/admin/server.php">Server Stats</a> - 
   check server status (very slow)</li>


</ul>
<h3>Database Update/Repair</h3>
<ul>

<li><a title="Recreate Maps" href="/admin/recreatemaps.php">Recreate Maps</a> - 
   force recreation of the most urgent maps</li>

<li><a title="DB Check" href="/admin/dbcheck.php">Database Check</a> analyse database for
database or application problems</li>

<li>Rebuild <a title="Rebuild wordnet" href="/admin/buildwordnet.php">WordNet</a>/<a 
title="Rebuild gridimage_search" href="/admin/buildgridimage_search.php">Search Cache</a> - use if
tables become corrupted</li>

</ul>
<h3>Developer Tools</h3>
<ul>

<li><a title="Custom Search" href="/search.php?form=advanced&Special=1">Create Custom Search</a> - create a one off special search (sql required)</li>

<li><a title="Map Maker" href="/admin/mapmaker.php">Map Maker</a> is a simple tool for checking
the internal land/sea map</li>

</ul>
{/if}
    
{/dynamic}

{include file="_std_end.tpl"}
