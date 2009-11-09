{assign var="page_title" value="Submission Processes"}
{include file="_std_begin.tpl"}

<h2>Geograph Submission Processes</h2>

<div style="background-color:pink; color:black; border:2px solid red; padding:10px; width:200px; float:right"><b>First time here?</b><br/> please checkout the following resources: <br/>
<a href="/faq.php">FAQ</a>,<br/> <a href="/article/Geograph-Introductory-letter">Introduction</a> and<br/> <a href="/article/Geograph-Quickstart-Guide">Quickstart Guide</a>.</div>

 <p>We have a number of ways to submit images to Geograph, a summary:</p>

<hr/>

 <h3><a href="/submit.php">Submit</a></h3>

 <p>The original submission process - the most complete and tested version. <b>Recommended for first time users.</b></p>
 
 <ul><li>Includes submission via {external href="http://www.picnik.com/" text="Picnik"} - an online photo manipulation service</li></ul>

<hr/>

 <h3><a href="/submit2.php?v=2">Submit v2</a> <sup style="color:red">New!</sup></h3>
 
 <p>More streamlined version - all one one page. If you familiar with Geograph submission you might like this.</p>

<hr/>

 <h3>{external href="http://www.nearby.org.uk/geograph/upload/" text="Multi-Upload"} <sup style="color:red">Experimental!</sup></h3>
 
 <p>Upload multiple images at once, then progress though the normal submission process one by one.</p>

<hr/>

 <h3><a href="/submit-nofrills.php">No-Frills Submit</a> <sup style="color:red">New!</sup></h3>
 
 <p>The bare minimum required to submit a image - recommended for seasoned contributors only!</p>

<hr/>

 <h3><a href="/juppy.php">JUppy Java&trade; Client</a></h3>
 
 <p>Downloadable application to batch submit from your desktop. Works but a little rough around the edges.</p>
 
 <p style="font-size:0.7em"><a href="/juppy.php">JUppy</a> is coded in cross-platform Java, and is a solution to upload many images, allowing you to prepare the images without an internet connection. <b><a href="/juppy.php">Read More, and Get it Now!</a></b></p>

<hr/>

 <h3><a href="picasa://importbutton/?url=http://{$http_host}/stuff/geograph-for-picasa.pbz.php/geograph-for-picasa.pbz">Picasa Plugin</a></h3>
 
 <p>Plugin for the popular {external href="http://picasa.google.com/" text="Picasa"} image mananagement program.</p>
 
 <p style="font-size:0.7em">With this button installed can use the selection tools in Picasa to upload photos in bulk, the submission process matches the online upload allowing selection with maps etc. Picasa automatically resizes the photo to Geograph specifications before upload, EXIF data is preserved however its only provided to Geograph at the end so it can't be used to find geolocation or dates embedded in the file. <br/>
 <b><a href="picasa://importbutton/?url=http://{$http_host}/stuff/geograph-for-picasa.pbz.php/geograph-for-picasa.pbz">Install the Geograph Uploader, Picasa Button</a></b>.<br/> (You will be asked to confirm this action, <b>only works if have Picasa installed!)</b></p>
 <p style="font-size:0.7em">Note while JUppy is an Offline Application, with which you can prepare the upload in advance of connecting; the Picasa button requires a Internet Connection to work as it integrates the interactive maps and other aids from the Geograph website.</p>
 
<hr/>
<br/>
<br/>

 
<h2>Feature Matrix</h2>
 
<table style="font-size:0.9em" border=1 cellspacing=0 cellpadding=3>
 <tr>
  <th>.</th>
  <th>Submit</th>
  <th>Submit v2</th>
  <th>Multi-Upload</th>
  <th>No Frills Submit</th>
  <th>Juppy</th>
  <th>Picasa</th>
 </tr>
 <tr>
  <td>JavaScript Required</td>
  <td style="background-color:lightgreen" align="center">NO</td>
  <td align="center">yes</td>
  <td align="center">yes</td>
  <td align="center">yes</td>
  <td align="center">na</td>
  <td align="center">yes</td>
 </tr> <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Upload via Website</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>Upload via Picnik</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td >Upload via Application</td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Multiple Image Upload</td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">10</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">hundreds</td>
  <td style="background-color:lightgreen" align="center">10</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>GPS Exif Extraction</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>GR from Filename</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
 </tr>
 <tr>
  <td>Enter Grid Square</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Select Grid Square</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr>
  <td>Find Square on Map</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Subject/Photographer on 50k Map</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:red"></td>
  <td style="background-color:red"></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>EXIF Date Extraction</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:red" align="center"></td>
  <td style="background-color:red"></td>
 </tr>
 <tr>
  <td>EXIF Preservation[1]</td>
  <td align="center">resized</td>
  <td align="center">resized</td>
  <td align="center">yes</td>
  <td align="center">resized</td>
  <td align="center">resized</td>
  <td align="center">yes</td>
 </tr>
 <tr>
  <td>Image Dimensions Checked</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td></td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Style Guide for Title/Description</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Shared Descriptions</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <td>Category Dropdown</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr>
  <td>Category Auto-Complete</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td style="background-color:lightgreen" align="center">*</td>
  <td></td>
  <td></td>
  <td style="background-color:lightgreen" align="center">*</td>
 </tr>
 <tr><td colspan="7" style="background-color:gray;line-height:3px">.</td></tr>
 <tr>
  <th>.</th>
  <th>Submit</th>
  <th>Submit v2</th>
  <th>Multi-Upload</th>
  <th>No Frills Submit</th>
  <th>Juppy</th>
  <th>Picasa</th>
 </tr>
</table>


<b>Notes</b>
<ol>
	<li>'resized' - EXIF perserved if image is resized to 640px before upload. If left to application, EXIF data lost from image file itself.<br/>
	However with all methods we still store the EXIF data for future use.</li>
</ol>

<br/><br/><br/>

{include file="_std_end.tpl"}
