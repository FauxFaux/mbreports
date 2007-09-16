<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dupscan 1.97.</title>
<style type="text/css">
table tr td { border: 1px solid black; padding: .5em }
table tr td:first-child { text-align: right }
td.tranny { background-color: #ddd }
td.error { background-color: #fcc }
</style>
</head><body><iframe style="display: none" name="secret"></iframe>
<?

ini_set('max_execution_time', 0);

$req_acc = 100;

/*
begin;
drop table if exists dupscan_cache cascade;
create table dupscan_cache as
select track_count,simple_cd_hash(tracklist) as hash, tracklist, album
from album_tracklist
where tracklist!='{0}'
AND sum_array(tracklist)!=0
AND track_count > 4
order by track_count,hash;
create index ponies on dupscan_cache(track_count);
commit;
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
$res = pg_query("select link0,link1 from l_album_album where (link_type = 15 or link_type = 2) and link0 in ($ids)");
while ($row = pg_fetch_array($res))
	$tranny[$row[0]] = $tranny[$row[1]] = true;

function merge_button($id)
{
	return "<td><a href=\"http://musicbrainz.org/edit/albumbatch/done.html?releaseid{$id}=on\" class=\"mergebutton\" target=\"secret\">m</a></td>";
}

function side($id, $left = false)
{
	global $tranny, $lines;
	if (!isset($lines[$id]))
		return '<td colspan="2" class="error">Album #' . $id . ' went missing! :o</td>';
	$ret = '<td' . (@$tranny[$id] ? ' class="tranny"' : '') . '>';
	if (!$left)
		return merge_button($id) . "$ret" . "{$lines[$id][0]} {$lines[$id][1]}</td>";
	else
		return "$ret{$lines[$id][1]} {$lines[$id][0]}</td>" . merge_button($id);
}

$ignore_url = 'http://wiki.musicbrainz.org/FauxFaux/NotDuplicateReleases';

$ignored = array();
preg_match_all('/releaseid=([0-9]+).+releaseid=([0-9]+)/',
	file_get_contents("{$ignore_url}?action=raw", false,
		stream_context_create(array('http' => array('method' => 'GET', 'header' => 'Authorization: Basic ' . base64_encode('mbwiki:mbwiki'))))
	),
$regs);

foreach ($regs[1] as $ind => $left)
	@$ignored[$left] = $regs[2][$ind];

foreach ($collisions as $from => $to)
	if (@$ignored[$from] == $to || @$ignored[$to] == $from)
		unset($collisions[$from]);

?>
<h1>Guessed duplicate releases take 2!</h1>
<p>Grey means you should probably ignore the release due to ARs.</p>
<p><?=count($collisions)?> hits:<ul>
	<li><?=count($tranny)?> albums (not lines) have excuses.</li>
	<li><?=count($ignored)?> on the <a href="<?=$ignore_url?>">ignore list</a>.</li>
</ul></p>
<p><?=$req_acc?>ms max difference per track.</p>
<p>Usage hint: The "m" button will add the release to the <a href="http://musicbrainz.org/edit/albumbatch/done.html">Release batch operations</a> page (no need to wait for whatever you browser tells you it&apos;s loading). Middle(or shift)-clicking the "m" button will add the release, <i>and</i> open the <a href="http://musicbrainz.org/edit/albumbatch/done.html">batch operations page</a> in a new tab/window.</p>
<table>
<?
$prev = 0;

foreach ($collisions as $from => $to)
{

	if ($from != $prev)
		echo '<tr>' . side($from, true) . side($to) . "</tr>\n";
	else
		echo "<tr><td></td><td></td>" . side($to) . "</tr>\n";
	$prev = $to;
}
?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds (plus ten minutes or so of cache).</p>";?>
</body>
</html>