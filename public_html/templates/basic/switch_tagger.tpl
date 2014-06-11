{assign var="page_title" value="Submission Method"}
{include file="_std_begin.tpl"}

<div class="tabHolder" style="text-align:right">
        <a href="/profile.php" class="tab">Back to Profile</a>
        <a href="/profile.php?edit=1" class="tab">General Settings</a>
        <a href="/profile.php?notifications=1" class="tab">Email Notifications</a>
        <a href="/choose-search.php" class="tab">Site Search Engine</a>
        <a href="/choose-preview.php" class="tab">Preview Method</a>
        <span class="tabSelected">Tagging Box</span>
        <a href="/switch.php" class="tab">Submission Method</a>
</div>
<div style="position:relative;" class="interestBox">
	<h2>Tag Box Update</h2>
</div>

<br/><br/>

{dynamic}
<div class="interestBox" style="padding-left:20px">
{if $new}
	<h4>You are currently opted into use the new style Tagging box!</h4>

	<p>For a short while you can revert to old version, by <a href="?new=0">clicking here</a>
{else}
	<h4>You have <u>not</u> yet opted to use the new Tagging box.</h4>

	<p>Please consider switching, do so by <a href="?new=1">clicking here</a>.</p>
{/if}
</div>

<p>Continue to the submission processes:</p>
<ul>
	<li><a href="/submit.php?new={$new}&amp;redir=false">Original Submission method</a></li>
	<li><a href="/submit2.php?new={$new}">Submission v2</a></li>
	<li><a href="/submit2.php?display=tabs&amp;new={$new}">Submission v2 (tabs)</a></li>
	<li><a href="/submit-multi.php?new={$new}">Multi Submission</a></li>
</ul>


{/dynamic}

<p>This is a preview of what the tagger box looks like for your current preference.<br/>
you can try it out, but of course its not adding tags to any real images.</p>
<iframe src="/tags/tagger.php" height="300" width="100%" id="tagframe"></iframe>


{include file="_std_end.tpl"}
