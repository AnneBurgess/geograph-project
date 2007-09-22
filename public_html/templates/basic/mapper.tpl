{assign var="page_title" value="Geograph :: Great Britain Map"}
{include file="_std_begin.tpl"}

<h3>Draggable Geograph Map of Great Britain <sup>(beta)</sup></h3>

{dynamic}{if $user->registered}
<div style="text-align:right; width:660px; font-size:0.7em; color:gray;">If you are getting messages "Quota Exceeded", then <a href="/mapper/captcha.php?token={$token}" style="color:gray;">visit this page to continue</a></div>{/if}{/dynamic}
<iframe src="/mapper/?inner&amp;token={$token}" width="700" height="850" frameborder="0"></iframe>


<br/><br/>


{include file="_std_end.tpl"}
