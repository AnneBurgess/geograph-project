{include file="_std_begin.tpl"}


<h2>Administrative Tools</h2>
<ul>
<li><a href="/admin/moderation.php">Moderate</a> new photo submissions</li>

<li><a title="Map Fixer" href="/admin/mapfixer.php">Map Fixer</a> allows the land percentage
for each 1km grid squares to be updated, which allows "that square is all at sea' to be 
corrected</li>
      
<li><a title="Category Consolidation" href="/admin/categories.php">Category Consolidation</a> - 
   Organise the user submitted categories</li>
 
<li><a title="API Keys" href="/admin/apikeys.php">API Keys</a> - 
   setup who has access to the API</li>

</ul>

{dynamic}
<h2>Basic Stats</h2>
<ul>
<li>Total registered users: {$users_total} ({$users_thisweek} new users in past 7 days)</li>
<li>Users who have submitted: {$users_submitted}</li>
<li>{$users_pending} registrations awaiting email address confirmation</li>
<li>Total images: {$images_total} ({$images_thisweek} new images in past 7 days)</li>

  <li>Breakdown:
  {foreach key=key item=item from=$images_status}
    {$item} {$key}
  {/foreach}
  </li>

</ul>
{/dynamic}    

<h2>Total Submissions</h2>
<img src="/img/submission_graph.png" width="480" height="161"/>

<h2>Daily Submission Rate</h2>
<img src="/img/rate.png" width="480" height="161"/>

<h2>Developer / Sysadmin Tools</h2>
<ul>

<li><a title="Recreate Maps" href="/admin/recreatemaps.php">Recreate Maps</a> - 
   force recreation of the most urgent maps</li>
   
<li><a title="Web Stats" href="http://www.geograph.co.uk/logs/">Web Stats</a> - 
   check the apache activity logs</li>

<li><a title="Server Stats" href="/admin/server.php">Server Stats</a> - 
   check server status</li>
   
<li><a title="Map Maker" href="/admin/mapmaker.php">Map Maker</a> is a simple tool for checking
the internal land/sea map</li>

<li><a title="DB Check" href="/admin/dbcheck.php">Database Check</a> analyse database for
database or application problems</li>

<li>Rebuild <a title="Rebuild wordnet" href="/admin/buildwordnet.php">WordNet</a>/<a 
title="Rebuild gridimage_search" href="/admin/buildgridimage_search.php">Search Cache</a> - use if
tables become corrupted</li>

<li><a title="Search Stats" href="/admin/viewsearches.php">Search Statistics</a> - See the recent Search Activity</li>

<li><a title="Custom Search" href="/search.php?form=advanced&Special=1">Create Custom Search</a> - create a one off special search (sql required)</li>


</ul>

    
{include file="_std_end.tpl"}
