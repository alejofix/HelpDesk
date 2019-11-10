<?php

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

$tmp = intval( hesk_GET('limit') );
$maxresults = ($tmp > 0) ? $tmp : $hesk_settings['max_listings'];

$tmp = intval( hesk_GET('page', 1) );
$page = ($tmp > 1) ? $tmp : 1;

/* Acceptable $sort values and default asc(1)/desc(0) setting */
$sort_possible = array();
foreach (array_keys($hesk_settings['possible_ticket_list']) as $key)
{
	$sort_possible[$key] = 1;
}
$sort_possible['priority'] = 1;
$sort_possible['dt'] = 0;
$sort_possible['lastchange'] = 0;

/* These values should have collate appended in SQL */
$sort_collation = array(
'name',
'subject',
'custom1',
'custom2',
'custom3',
'custom4',
'custom5',
'custom6',
'custom7',
'custom8',
'custom9',
'custom10',
'custom11',
'custom12',
'custom13',
'custom14',
'custom15',
'custom16',
'custom17',
'custom18',
'custom19',
'custom20',
);

/* Acceptable $group values and default asc(1)/desc(0) setting */
$group_possible = array(
'owner' 		=> 1,
'priority' 		=> 1,
'category' 		=> 1,
'custom1'		=> 1,
'custom2'		=> 1,
'custom3'		=> 1,
'custom4'		=> 1,
'custom5'		=> 1,
'custom6'		=> 1,
'custom7'		=> 1,
'custom8'		=> 1,
'custom9'		=> 1,
'custom10'		=> 1,
'custom11'		=> 1,
'custom12'		=> 1,
'custom13'		=> 1,
'custom14'		=> 1,
'custom15'		=> 1,
'custom16'		=> 1,
'custom17'		=> 1,
'custom18'		=> 1,
'custom19'		=> 1,
'custom20'		=> 1,
);

/* Start the order by part of the SQL query */
$sql .= " ORDER BY ";

/* Group tickets? Default: no */
if (isset($_GET['g']) && ! is_array($_GET['g']) && isset($group_possible[$_GET['g']]))
{
	$group = hesk_input($_GET['g']);

    if ($group == 'priority' && isset($_GET['sort']) && ! is_array($_GET['sort']) && $_GET['sort'] == 'priority')
    {
		// No need to group by priority if we are already sorting by priority
    }
    elseif ($group == 'owner')
    {
		// If group by owner place own tickets on top
		$sql .= " CASE WHEN `owner` = '".intval($_SESSION['id'])."' THEN 1 ELSE 0 END DESC, `owner` ASC, ";
    }
    else
    {
	    $sql .= ' `'.hesk_dbEscape($group).'` ';
	    $sql .= $group_possible[$group] ? 'ASC, ' : 'DESC, ';
    }
}
else
{
    $group = '';
}


/* Show critical tickets always on top? Default: yes */
$cot = (isset($_GET['cot']) && intval($_GET['cot']) == 1) ? 1 : 0;
if (!$cot)
{
	$sql .= " CASE WHEN `priority` = '0' THEN 1 ELSE 0 END DESC , ";
}

/* Sort by which field? */
if (isset($_GET['sort']) && ! is_array($_GET['sort']) && isset($sort_possible[$_GET['sort']]))
{
	$sort = hesk_input($_GET['sort']);

    $sql .= $sort == 'lastreplier' ? " CASE WHEN `lastreplier` = '0' THEN 0 ELSE 1 END DESC, COALESCE(`replierid`, NULLIF(`lastreplier`, '0'), `name`) " : ' `'.hesk_dbEscape($sort).'` ';

    // Need to set MySQL collation?
    if ( in_array($_GET['sort'], $sort_collation) )
    {
    	$sql .= " COLLATE '" . hesk_dbEscape($hesklang['_COLLATE']) . "' ";
    }
}
else
{
	/* Default sorting by ticket status */
    $sql .= ' `status` ';
    $sort = 'status';
}

/* Ascending or Descending? */
if (isset($_GET['asc']) && intval($_GET['asc'])==0)
{
    $sql .= ' DESC ';
    $asc = 0;
    $asc_rev = 1;

    $sort_possible[$sort] = 1;
}
else
{
    $sql .= ' ASC ';
    $asc = 1;
    $asc_rev = 0;
    if (!isset($_GET['asc']))
    {
    	$is_default = 1;
    }

    $sort_possible[$sort] = 0;
}

/* In the end same results should always be sorted by priority */
if ($sort != 'priority')
{
	$sql .= ' , `priority` ASC ';
}

# Uncomment for debugging purposes
# echo "SQL: $sql<br>";
