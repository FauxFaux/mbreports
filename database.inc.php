<?php

pg_connect('host=brainzvm user=postgres dbname=musicbrainz_db');

$desc = array('artistnotype.php' => 'Artists with no type',
	'barcodes999.php' => '13 digit barcodes starting with 99',
	'catasins.php' => 'Catalogue numbers that look like asins or label codes',
	'index.php' => 'This page',
	'sillybarcodes.php' => 'Barcodes matching /^0?0946[0-9]{8}$/ (don\'t ask)',
	'upcnoasin.php' => 'Releases with an UPC but not an ASIN',
	'badasins.php' => '"Bad" amazon asin links',
	'badbadasins.php' => '[fully static] "Really Bad" (tm) amazon asin links',
	'badurltypes.php' => 'Bad url types',
	'badurltypes2.php' => 'Bad url types for eeeverything',
	'missingdiscs.php' => 'Releases with possibly missing discs',
	'mergeproblems2.php' => 'Merge problems take 2 (regeneratable cache)',
	'neweditsof.php' => 'Add release edits of a certain type',
	'discseq.php' => 'WIP',
	'cueread.php' => 'Attempt to guess the album from a series of times (ie. a cue file)',
	'cueread2.php' => 'Lookup by times (static cache)',
	'usefulannotations.php' => '[fully static] Possibly useful annotations.',
	'nonimgurls.php' => 'Coverart urls that don\'t end in image extensions',
	'emusicva2trackparse.php' => 'Paste eMusic VA to get something the trackparser doesn\'t sulk about'
	);

function my_desc()
{
	global $desc;
	return $desc[preg_replace('#^.*/#', '', $_SERVER['SCRIPT_NAME'])];
}

function my_title()
{
	$age = pg_fetch_array(pg_query("select date_trunc('minute', now()-last_replication_date) from replication_control"));
	?>
<<?='?'?>xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN"
	"http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html>
<head>
<title><?=my_desc()?></title>
</head>
<body>
<h1><?=my_desc()?> (data age: <?=$age[0]?>)</h1>
<?
}

$start = time();

