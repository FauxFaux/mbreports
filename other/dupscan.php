<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dupscan 1.99.</title>
<style type="text/css">
table tr td { border: 1px solid black; padding: .5em }
table tr td:first-child { text-align: right }
table tr th { padding: 2em; font-size: 200% }
.tranny { background-color: #ddd }
.error { background-color: #fcc }
.near { background-color: #ccf; border: 2px dashed black }
span.error,span.near { padding: .1em }
</style>
</head><body><iframe style="display: none" name="secret"></iframe>
<?

ini_set('max_execution_time', 0);

class Set
{
	private $inside;
	public function get($id = -1)
	{
		$t = array_keys($this->inside);
		if ($id != -1)
			return $t[$id];
		return $t;
	}

	function add($key)
	{
		$this->inside[$key] = true;
		ksort($this->inside);
		return $this;
	}

	function remove($key)
	{
		if ($this->contains($key))
			unset($this->inside[$key]);
		return $this;
	}

	function contains($key)
	{
		return isset($this->inside[$key]);
	}

	function empty_()
	{
		return $this->count_() == 0;
	}

	function count_()
	{
		return count($this->inside);
	}
};

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
		{
			if (isset($collisions[$last_album]))
				$collisions[$last_album]->add($this_album);
			else if (isset($collisions[$this_album]))
				$collisions[$this_album]->add($last_album);
			else
			{
				@$collisions[$this_album] = new Set();
				$collisions[$this_album]->add($this_album)->add($last_album);
			}
		}

		$last_album = $this_album;
		$last_broken = $this_broken;
	}
	//echo "<p>PONY$track_count (" . count($collisions) . ")</p>\n";
}

$collisions = array_values($collisions);

$ids = array();

foreach ($collisions as $col)
{
	$ids = array_merge($ids, $col->get());
}

$ids = implode(',', $ids);

$lines = array();
$lang = array();

$res = pg_query("select album.id,album.name as album,artist.name as artist, \"language\", script from album join artist on artist.id=album.artist where album.id in ($ids)");
while ($row = pg_fetch_assoc($res))
{
	@$lines[$row['id']] = array($row['album'], $row['artist']);
	@$lang[$row['id']] = array($row['language'], $row['script']);
}

$tranny = array();
$res = pg_query("select link0,link1 from l_album_album where (link_type = 15 or link_type = 2) and link0 in ($ids)");
while ($row = pg_fetch_array($res))
	$tranny[$row[0]] = $row[1];

function merge_button($id)
{
	return "<td><a href=\"http://musicbrainz.org/edit/albumbatch/done.html?releaseid{$id}=on\" class=\"mergebutton\" target=\"secret\">m</a></td>";
}

function album_link($id)
{
	global $lines;
	return "<a href=\"http://musicbrainz.org/show/release/?releaseid=$id\">{$lines[$id][0]}</a>";
}

function side($id, $left = false)
{
	global $tranny, $lines;
	if (!isset($lines[$id]))
		return '<td colspan="2" class="error">Album #' . $id . ' went missing! :o</td>';
	$ret = '<td' . (@$tranny[$id] ? ' class="tranny"' : '') . '>';
	if (!$left)
		return merge_button($id) . "$ret" . album_link($id) . " ({$lines[$id][1]})</td>";
	else
		return "$ret({$lines[$id][1]}) " . album_link($id) . "</td>" . merge_button($id);
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

function remove_if($col, $left, $right)
{
	if ($col->contains($left) && $col->contains($right))
		$col->remove($left)->remove($right);
}

foreach ($collisions as $col)
{
	foreach ($ignored as $left => $right)
		remove_if($col, $left, $right);
	foreach ($tranny as $left => $right)
		remove_if($col, $left, $right);
}

$removes = 0;
foreach ($collisions as $ind => $col)
{
	if ($col->empty_())
	{
		++$removes;
		unset($collisions[$ind]);
	}
}
?>
<h1>Guessed duplicate releases take 2!</h1>
<p><?=count($collisions)?> hits:<ul>
	<li><?=count($tranny)?> pairs have relevant ARs and are excluded. (Releases in <span class="tranny">grey</span> have relevant ARs but have not been excluded by them).</li>
	<li><?=count($ignored)?> on the <a href="<?=$ignore_url?>">ignore list</a>.</li>
	<li> ... results in <?=$removes?> removals total.</li>
</ul></p>
<p><?=$req_acc?>ms max difference per track.</p>
<p>Usage hint: The "m" button will add the release to the <a href="http://musicbrainz.org/edit/albumbatch/done.html">Release batch operations</a> page (no need to wait for whatever you browser tells you it&apos;s loading). Middle(or shift)-clicking the "m" button will add the release, <i>and</i> open the <a href="http://musicbrainz.org/edit/albumbatch/done.html">batch operations page</a> in a new tab/window.</p>
<p>Releases in <span class="near">blue/dashed</span> were added very close to each other and hence are more likely to be accidents.</p>
<p><ul>
<li><a href="#trans">Missing trans*ation ARs</a></li>
<li><a href="#artdis">Artist disputes</a></li>
<li><a href="#ident">Identical (ish)</a></li>
<li><a href="#other">Others</a></li>
<li><a href="#odd">Odd Numbers</a></li>
</ul>
<?
$prev = 0;

$oldcollisions = $collisions;
$collisions = array();

$overtwo = array();
$ones = array();

function miniside($id)
{
	global $lines;
	if (!isset($lines[$id]))
		return '<span class="error">Album #' . $id . ' went missing! :o</span>';
	return album_link($id) . ' - ' . $lines[$id][1];
}

foreach ($oldcollisions as $ind => $col)
	if ($col->count_() > 2)
	{
		$s = array();
		foreach ($col->get() as $alb)
			$s[] = miniside($alb);
		$overtwo[] = $s;
	}
	else if ($col->count_() == 1)
		$ones[] = miniside($col->get(0));
	else
		$collisions[$col->get(0)] = $col->get(1);

echo '<table>';

function relate($left, $right)
{
	return "<td" . (abs($left - $right) < 4 ? ' class="near"' : '') . "><a href=\"http://musicbrainz.org/edit/relationship/add.html?link0=album=$left&link1=album=$right\" target=\"none\">r</a></td>";
}

{
	ob_start(); $count = 0; // HAHAHAHAHAHHAHA EVIl

	foreach ($collisions as $left => $right)
	{
		if ($lang[$left] != $lang[$right])
		{
			echo '<tr>' . side($left, true) . relate($left, $right) . side($right) . "</tr>\n";
			++$count;
			unset($collisions[$left]);
		}
	}

	$s = ob_get_contents();
	ob_end_clean();
	echo "<tr><th colspan=\"5\"><a name=\"trans\"/>Probable missing trans*ation ARs ($count total)</th></tr>";
	echo $s;
}

{
	ob_start(); $count = 0;

	foreach ($collisions as $left => $right)
	{
		$al = $lines[$left][1];
		$ar = $lines[$right][1];
		if (($al != $ar) &&
			(
				(strpos($al, $ar) !== false || strpos($ar, $al) !== false)
				|| $lines[$left][0] == $lines[$right][0]
			)
		)
		{
			echo '<tr>' . side($left, true) . relate($left, $right) . side($right) . "</tr>\n";
			++$count;
			unset($collisions[$left]);
		}
	}

	$s = ob_get_contents();
	ob_end_clean();
	echo "<tr><th colspan=\"5\"><a name=\"artdis\"/>Artist disputes ($count total)</th></tr>";
	echo $s;
}

{
	ob_start(); $count = 0;

	foreach ($collisions as $left => $right)
	{
		$al = $lines[$left][1];
		$ar = $lines[$right][1];
		if ($lines[$left][0] == $lines[$right][0])
		{
			echo '<tr>' . side($left, true) . relate($left, $right) . side($right) . "</tr>\n";
			++$count;
			unset($collisions[$left]);
		}
	}

	$s = ob_get_contents();
	ob_end_clean();
	echo "<tr><th colspan=\"5\"><a name=\"ident\"/>Identical (heh heh heh) ($count total)</th></tr>";
	echo $s;
}


echo '<tr><th colspan="5"><a name="other"/>Pairs that confuse me. :( (' . count($collisions) .' total)</th></tr>';

foreach ($collisions as $left => $right)
{
	echo '<tr>' . side($left, true) . relate($left, $right) . side($right) . "</tr>\n";
}

?>
</table>

<h2><a name="odd"/>Odd Numbers</h2>
<p>Things are probably here because they're more duplicated than normal, or when there's only one item, the others have been stolen by trans*ation, it probably needs an AR too.</p>
<h3>Over two (<?=count($overtwo)?> total)</h3>
<?
foreach ($overtwo as $list)
{
	natcasesort($list);
	echo '<hr /><ul><li>' . implode('</li><li>', $list) . '</li></ul>';
}

echo '<h3>Just one (' . count($ones) .' total)</h3>' .
	'<hr /><ul><li>' . implode('</li><li>', $ones) . '</li></ul>';

?>

<?echo "<p>Generated in " . (time()-$start) . " seconds (plus ten minutes or so of cache) on the " . date('jS \of F Y') . ".</p>";?>
</body>
</html>