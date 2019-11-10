<?php

define('IN_SCRIPT',1);
define('HESK_PATH','./');

// Get all the required files and functions
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');

// Feature enabled?
if ( ! $hesk_settings['detect_typos'])
{
	die('');
}

// Print XML header
header('Content-Type: text/html; charset='.$hesklang['ENCODING']);
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Get the search query composed of the subject and message
$address = hesk_REQUEST('e') or die('');
$div = 1;

// Do we allow multiple emails? If yes, check all
if ($hesk_settings['multi_eml'])
{
	// Make sure the format is correct
	$address = preg_replace('/\s/','',$address);
	$address = str_replace(';',',',$address);

	// Loops through emails and check for typos
	$div = 1;
	$all = explode(',',$address);
	foreach ($all as $address)
	{
		if ( ($suggest = hesk_emailTypo($address)) !== false )
		{
			hesk_emailTypoShow($address, $suggest, $div);
			$div++;
		}
	}
}
// If multiple emails are not allowed, check just first one
elseif ( ($suggest = hesk_emailTypo($address)) !== false )
{
	hesk_emailTypoShow($address, $suggest);
}

exit();


function hesk_emailTypoShow($address, $suggest, $div = '')
{
	global $hesk_settings, $hesklang;
	?>
	<div id="emailtypo<?php echo $div; ?>" style="display:block">
		<table border="0" width="100%">
		<tr>
		<td width="150">&nbsp;</td>
		<td width="80%">
			<div class="notice">
			<?php echo sprintf($hesklang['didum'], str_replace('@', '@<b>', $suggest . '</b>') ); ?><br /><br />
			<a href="javascript:void();" onclick="javascript:var eml=document.form1.email.value;document.form1.email.value=eml.replace(/<?php echo preg_quote($address, '/'); ?>/gi, '<?php echo addslashes($suggest); ?>' );hesk_toggleLayerDisplay('emailtypo<?php echo $div; ?>');"><?php echo $hesklang['yfix']; ?></a>
			|
			<a href="javascript:void();" onclick="javascript:hesk_toggleLayerDisplay('emailtypo<?php echo $div; ?>');"><?php echo $hesklang['nole']; ?></a>
			</div>
		</td>
		</tr>
		</table>
	</div>
	<?php
} // END hesk_emailTypoShow()


function hesk_emailTypo($address)
{
	global $hesk_settings;

	// Remove anything more than a single address
	$address = str_replace(strstr($address,','),'',$address);
	$address = str_replace(strstr($address,';'),'',$address);
	$address = strtolower(trim($address));

	// Get email domain
    $domain = substr(strrchr($address, '@'), 1);

	// If no domain return false
	if ( ! $domain)
    {
    	return false;
    }

	// If we have an exact match return false
    if ( in_array($domain, $hesk_settings['email_providers']) )
    {
    	return false;
    }


	$shortest = -1;
    $closest  = '';

	foreach ($hesk_settings['email_providers'] as $provider)
    {
		$similar =  levenshtein($domain, $provider, 2, 1, 3);

		if ($similar < 1)
        {
        	return false;
        }

	    if ($similar < $shortest || $shortest < 0)
        {
	        $closest  = $provider;
	        $shortest = $similar;
	    }
    }

    if ($shortest < 4)
    {
    	return str_replace($domain, $closest, $address);
    }
    else
    {
    	return false;
    }
}  // END hesk_emailTypo()
?>
