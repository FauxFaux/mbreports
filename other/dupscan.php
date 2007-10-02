<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Dupscan 1.992.</title>
<style type="text/css">
table tr td { border: 1px solid black; padding: .5em }
table tr td:first-child { text-align: right }
table tr th { padding: 2em; font-size: 200% }
.tranny { background-color: #ddd }
.error { background-color: #fcc }
.near { background-color: #ccf; border: 2px dashed black }
.wsnw { white-space: nowrap }
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
}

$collisions = array_values($collisions);

$ids = array();

foreach ($collisions as $col)
	$ids = array_merge($ids, $col->get());

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

$tracks = array();
$res = pg_query("select album,sequence,name from track join albumjoin on track.id = albumjoin.track where album in ($ids) order by album,sequence");
while ($row = pg_fetch_array($res))
	$tracks[$row[0]][$row[1]] = $row[2];

function album_link($id)
{
	global $lines;
	return "<a href=\"http://musicbrainz.org/show/release/?releaseid=$id\">{$lines[$id][0]}</a>";
}

function side($id, $left = false)
{
	global $tranny, $lines;
	if (!isset($lines[$id]))
		return '<td class="error">Album #' . $id . ' went missing! :o</td>';
	$ret = '<td' . (@$tranny[$id] ? ' class="tranny"' : '') . '>';
	if (!$left)
		return "$ret" . album_link($id) . " ({$lines[$id][1]})</td>";
	else
		return "$ret({$lines[$id][1]}) " . album_link($id) . "</td>";
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

$checks = array(
	'identical_trli' => 'Identical track-lists',
	'artist_disp' => 'Artist disputes',
	'diff_lang' => 'Probable missing trans*ation ARs',
	'identical_albs' => 'Identical (heh heh heh)',
	'other_albs' => 'Other pairs'
);

?>
<h1>Guessed duplicate releases take 2!</h1>
<p><?=count($collisions)?> hits:<ul>
	<li><?=count($tranny)?> pairs have relevant ARs and are excluded. (Releases in <span class="tranny">grey</span> have relevant ARs but have not been excluded by them).</li>
	<li><?=count($ignored)?> on the <a href="<?=$ignore_url?>">ignore list</a>.</li>
	<li> ... results in <?=$removes?> removals total.</li>
</ul></p>
<p><?=$req_acc?>ms max difference per track.</p>
<p>Releases in <span class="near">blue/dashed</span> were added very close to each other and hence are more likely to be accidents. All sorted by age.</p>
<p><a name="index"></a><ul>
<?

foreach ($checks as $key => $title)
	echo "<li><a href=\"#$key\">$title</a></li>\n";
echo '</ul>';

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

function diff_lang($left, $right)
{
	global $lang;
	return $lang[$left] && $lang[$right] && $lang[$left] != $lang[$right];
}

function artist_disp($left, $right)
{
	global $lines;
	$al = $lines[$left][1];
	$ar = $lines[$right][1];
	return (($al != $ar) &&
		(
			(strpos($al, $ar) !== false || strpos($ar, $al) !== false)
			|| $lines[$left][0] == $lines[$right][0]
		)
	);
}

function identical_trli($left, $right)
{
	global $tracks;
	$tl = $tracks[$left];
	$tr = $tracks[$right];
	$tn = count($tl);
	$tt = 0.0;
	foreach ($tl as $ind => $l)
		if ($l != $tr[$ind])
			return false;
	return true;
}

function identical_albs($left, $right)
{
	global $lines;
	return $lines[$left][0] == $lines[$right][0];
}

function other_albs($left, $right)
{
	return true;
}

function seperate_by($f, array $arr)
{
	$in = $out = array();
	foreach ($arr as $left => $right)
		if (call_user_func($f, $left, $right))
			$in[$left] = $right;
		else
			$out[$left] = $right;
	return array($out, $in);
}

function merge_button($left, $right)
{
	return "<a href=\"http://musicbrainz.org/edit/albumbatch/done.html?releaseid{$left}=on&releaseid{$right}=on\" class=\"mergebutton\">m</a>";
}

function relate($left, $right)
{
	return "<a href=\"http://musicbrainz.org/edit/relationship/add.html?link0=album=$left&link1=album=$right\">r</a>";
}

function image_img_for($token, $amount)
{
	return "<img src=\"match-$token.png\" alt=\"" . round($amount) . '% track-list match" />';
}

function image_for($amount)
{
	foreach (array(100, 90, 80, 70, 60) as $num)
		if ($amount >= $num)
			return image_img_for($num, $amount);
	return image_img_for(50, $amount);
}

function similarity($left, $right)
{
	global $tracks;
	$tl = $tracks[$left];
	$tr = $tracks[$right];
	$tn = count($tl);
	$tt = 0.0;
	foreach ($tl as $ind => $l)
	{
		$r = $tr[$ind];
/*
		$non_ascii = '/[\x80-\zff]/';
		if (preg_match($non_ascii, $l) ||
			preg_match($non_ascii, $r))
			return '<abbr title="Cowardly confusing to compare non-ASCII texts">NA</abbr>';
*/
		$p = 0;
		// Return into $p, float percentage <=100.
		similar_text($l, $r, $p);
		$tt += $p;
	}

	return image_for($tt / (float)$tn);
}

function buttons($left, $right)
{
	return "<td class=\"wsnw" . (abs($left - $right) < 4 ? ' near"' : '"') . ">" .
		merge_button($left, $right) . ' - ' . relate($left, $right) . ' - ' . similarity($left, $right) .
		'</td>';
}



ksort($collisions);

foreach ($checks as $func => $title)
{
	list($collisions, $this_iter) = seperate_by($func, $collisions);
	echo "<tr><th colspan=\"5\"><a name=\"$func\"/>$title (" . count($this_iter) . " total)</th></tr>";
	foreach ($this_iter as $left => $right)
		echo '<tr>' . side($left, true) . buttons($left, $right) . side($right) . "</tr>\n";
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
