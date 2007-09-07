<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dupscan 1.95.</title>
<style type="text/css">
table tr td { border: 1px solid black; padding: .5em }
table tr td:first-child { text-align: right }
td.tranny { background-color: #ddd }
td.error { background-color: #fcc }
</style>
</head><body>
<?

ini_set('max_execution_time', 0);

$req_acc = 1500;

/*
create table dupscan_cache as
select track_count,simple_cd_hash(tracklist) as hash, tracklist, album
from album_tracklist
where tracklist!='{0}'
AND sum_array(tracklist)!=0
order by track_count,hash;
create index ponies on dupscan_cache(track_count);
*/

function breakup($thing)
{
	return explode(',', trim($thing, '{}'));
}

function equal(array $left, array $right)
{
	global $req_acc;
	foreach ($left as $ind => $val)
		if (!$right[$ind] || !$val || abs($right[$ind] - $val) > $req_acc)
			return false;
	return true;
}

require_once('../database.inc.php');
//and not array_search(tracklist, 0)
$res = pg_query('select distinct track_count from dupscan_cache where track_count > 4');
$tcs = array();
while ($row = pg_fetch_assoc($res))
	$tcs[] = (int)$row['track_count'];

unset($row);

$collisions = array();
$interestingids = array();

$n = 0;
foreach ($tcs as $track_count)
{
	$pri = pg_query("select * from dupscan_cache where track_count = $track_count");
	{
		$temp = pg_fetch_assoc($pri);
		$last_album = $temp['album'];
		$last_broken = breakup($temp['tracklist']);
	}

	while ($row = pg_fetch_assoc($pri))
	{
		$this_broken = breakup($row['tracklist']);
		$this_album = $row['album'];

		if (equal($last_broken, $this_broken))
			@$collisions[$last_album] = $this_album;

		$last_album = $this_album;
		$last_broken = $this_broken;
	}
	//echo "<p>PONY$track_count (" . count($collisions) . ")</p>\n";
}

$lines = array();

$res = pg_query("select album.id,album.name as album,artist.name as artist from album join artist on artist.id=album.artist where album.id in (" . ($ids = implode(',', array_merge(array_keys($collisions), $collisions))) . ")");
while ($row = pg_fetch_assoc($res))
	@$lines[$row['id']] = array("<a href=\"http://musicbrainz.org/show/release/?releaseid={$row['id']}\">{$row['album']}</a>", "({$row['artist']})");


$tranny = array();
$res = pg_query("select link0,link1 from l_album_album where link_type = 15 and link0 in ($ids)");
while ($row = pg_fetch_array($res))
	$tranny[$row[0]] = $tranny[$row[1]] = true;

function side($id, $left = false)
{
	global $tranny, $lines;
	if (!isset($lines[$id]))
			return '<td class="error">Album #' . $id . ' went missing! :o</td>';
	$ret = '<td' . (@$tranny[$id] ? ' class="tranny"' : '') . '>';
	if (!$left)
		return "$ret{$lines[$id][0]} {$lines[$id][1]}</td>";
	else
		return "$ret{$lines[$id][1]} {$lines[$id][0]}</td>";
}

?>
<h1>Guessed duplicate releases take 2!</h1>
<p>Grey means some kind of dirty trans*ation is going on.</p>
<p><?=count($collisions)?> hits. <?=$req_acc?>ms max difference per track. Longer cds (better matches) at the bottom.</p>
<table>
<?
$prev = 0;
foreach ($collisions as $from => $to)
{
	if ($from != $prev)
		echo '<tr>' . side($from, true) . side($to) . "</tr>\n";
	else
		echo "<tr><td></td>" . side($to) . "</tr>\n";
	$prev = $to;
}
?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds (plus three minutes cache, plus twenty minutes pre-cache).</p>";?>
</body>
</html>