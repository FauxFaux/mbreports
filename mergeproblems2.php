<?

require_once('database.inc.php');

if (isset($_GET{'regenerate'}))
{
	ignore_user_abort(true);
	set_time_limit(0);
	echo '<p>Regenerating... you may (but shouldn\'t) close the page.</p>' . "\n";
	flush();
	// Yeah, this is unbelievably awful. Trust me, I tried to get it to run in a sane time the other ways. Please, if you can, fix it.
	pg_query("BEGIN;
		DROP TABLE IF EXISTS mp_ids;
		CREATE TEMPORARY TABLE mp_ids AS SELECT id FROM album WHERE album.name LIKE '% (disc %';
		SELECT * FROM mp_ids;

		DROP TABLE IF EXISTS mp_dates;
		CREATE TEMPORARY TABLE mp_dates AS SELECT album,string_accum(
			COALESCE(country.isocode, '') ||'||'|| COALESCE(releasedate, '') ||'||'|| COALESCE(label.name, '') ||'||'|| COALESCE(catno, '') ||'||'|| COALESCE(barcode, '') ||'||'|| COALESCE(format, 0) ||';'
		) AS reldate FROM (SELECT * FROM release WHERE album IN (SELECT id FROM mp_ids) ORDER BY country,releasedate) AS ponies JOIN country ON (country.id = ponies.country) JOIN label ON (label.id = ponies.label) GROUP BY album;

		DROP TABLE IF EXISTS mp_artist_name;
		CREATE TEMPORARY TABLE mp_artist_name AS SELECT album.id,artist.name AS artist FROM artist JOIN album ON album.artist=artist.id JOIN mp_ids ON album.id=mp_ids.id;

		DROP TABLE IF EXISTS mergeproblems_full;
		CREATE TABLE mergeproblems_full AS SELECT album.id,album.name,mp_artist_name.artist,album.attributes,
			language.name as language,
			script.name as script,
			album_amazon_asin.asin,
			mp_dates.reldate as releasedate,
			substr(album.name, 0, strpos(album.name, ' (disc ')) AS grouper
			FROM album
			JOIN mp_ids ON mp_ids.id=album.id
			JOIN mp_artist_name ON mp_artist_name.id=mp_ids.id
			LEFT JOIN mp_dates on mp_ids.id=mp_dates.album
			JOIN script ON album.script=script.id
			JOIN language ON album.language=language.id
			LEFT JOIN album_amazon_asin ON album.id=album_amazon_asin.album
		ORDER BY grouper;


		DROP VIEW IF EXISTS mp_firstchar_sort;
		DROP VIEW IF EXISTS mp_firstchar_count;
		DROP VIEW IF EXISTS mp_firstchar;
		DROP TABLE IF EXISTS mergeproblems_characters;
		CREATE TEMPORARY VIEW mp_firstchar AS SELECT LOWER(SUBSTR(name, 1,1)) AS cha FROM mergeproblems_full;
		CREATE TEMPORARY VIEW mp_firstchar_count AS SELECT COUNT(*),cha FROM mp_firstchar GROUP BY cha;
		CREATE TEMPORARY VIEW mp_firstchar_sort AS SELECT * FROM mp_firstchar_count ORDER BY count DESC LIMIT 30;
		CREATE TABLE mergeproblems_characters AS SELECT cha FROM mp_firstchar_sort ORDER BY cha ASC;

	COMMIT;");

	echo 'Done. <a href="' . $_SERVER['SCRIPT_NAME'] . '">Go back</a>.';
	die();
}

my_title();
?>
<h2>Partially CACHED report, <a href="?regenerate">regenerate cache</a> (should take under two minutes)</h2>
<style type="text/css">
	body { background-color: white; color: black }
	.bore { color: grey }
	.diff { color: red }
	p.boxedlist a { background-color: #ddd; padding: 0 .4em; border: 1px solid #777; margin-top: .2em }
	p.boxedlist a.current { font-weight: bold; background-color: black; color: white }
	span.reldate { margin-right: 1em }
	span.relcountry { padding: .2em; border: 1px dashed black }
	.dirty { background-color: #fec }
	.dirtydupes { background-color: #fcc }
	th,tr.dr td { border: 1px solid black; padding: .4em }
	tr.hd td { border: 2px solid black; padding: 1em; font-weight: bold; font-size: 140% }
</style>
<?

function missing()
{
	return '{{missing}}';
}

function if_not_missing($var)
{
	if (trim($var))
		return $var;
	return missing();
}

function reldates_string($relds)
{
	global $formats;
	$s = '';
	if (!$relds)
		return missing();
	foreach (explode(';', $relds) as $reld)
	{
		$reld = explode('||', $reld);
		if (count($reld) < 6)
			continue;
		$s .= "<span class=\"reldate\"><span class=\"relcountry\">{$reld[0]}</span> {$reld[1]} <b>{$reld[2]} {$reld[3]}</b> {$reld[4]} <b>{$formats[$reld[5]]}</b></span>";
	}
	return $s;
}

@$page = (int)$_GET{'page'};
@$prefix = pg_escape_string($_GET{'prefix'});

$per_page = 500;
$offset = 10;

echo '<p class="boxedlist">Only items starting with: ' .
	'<a ' . ($prefix=='' ? 'class="current" ' : '') . 'href="?prefix=">anything</a> ';

$charh = pg_query('SELECT cha FROM mergeproblems_characters');
while ($row = pg_fetch_assoc($charh))
	echo '<a href="?prefix=' . $row['cha'] . '"' . ($row['cha'] == strtolower($prefix) ? ' class="current"' : '') . '>' . $row['cha'] . '</a> ';

echo '</p>';


$rohs = pg_query("
	SELECT COUNT(*) FROM mergeproblems_full
	WHERE name ILIKE '$prefix%'
	"
);
if (!$rohs)
	die('The cache is missing. Try regenerating it above?');

$tmp = pg_fetch_assoc($rohs);
$rows = $tmp['count'];
pg_free_result($rohs);

$pages = (int)($rows/$per_page);

$page_suffix = '<a href="?prefix=' . $prefix . '&amp;page=';

echo '<p class="boxedlist">Page: ';

if ($page > 0)
	echo $page_suffix . ($page-1) . '">&laquo;</a> ';

for ($i = 0; $i <= $pages; ++$i)
	echo $page_suffix . $i . '"' . ($i == $page ? ' class="current"' : '') . '>' . ($i+1) . '</a> ';

if ($page < $pages)
	echo $page_suffix . ($page+1) . '">&raquo;</a>';

echo '</p>';

$rohs = pg_query("
	SELECT * FROM mergeproblems_full
	WHERE name ILIKE '$prefix%'
	ORDER BY grouper,name
	LIMIT " . ($per_page+$offset*2) . " OFFSET " . ($page*$per_page - $offset) . "
	"
);

if (!pg_num_rows($rohs))
	die ('<p>No items considered on this page. Invalid prefix/page number.</p>');

$boxes = $total = 0;

$dat = array();

while ($row = pg_fetch_assoc($rohs))
{
	@$dat[$row['grouper']][$row['id']] = $row;
	@$dat[$row['grouper']]['ids'][] = $row['id'];
}

function check_all_same($start, array $arr)
{
	$start['id'] = $start['name'] = '';
	$start['asin'] = $start['asin'] ? trim($start['asin']) : '';
	foreach ($arr as $bits)
	{
		$bits['id'] = $bits['name'] = '';
		$bits['asin'] = $bits['asin'] ? trim($bits['asin']) : '';
		if ($bits != $start)
			return false;
	}
	return true;

}

$span = 7;

?>
<hr/>
<table><tr><th>disc <abbr title="There's more than one complete (as far as I can tell) cd of this name." class="dirtydupes">...</abbr> <abbr title="There aren't enough disc numbers for me to make a guess." class="dirty">...</abbr></th><th>Artist</th><th>Attributes</th><th>Language</th><th>Script</th><th>ASIN</th><th>Release events</th></tr>
<?


function comparisoni($compare_to, $what)
{
	return (if_not_missing($compare_to) == if_not_missing($what) ? 'bore">' : 'diff">') . if_not_missing($what);
}

function comparison($compare_to, $what, $key)
{
	return comparisoni($compare_to[$key], $what[$key]);
}

function line_for($compare_to, $what)
{
	global $skip;
	echo '<tr class="dr"><td><a href="http://musicbrainz.org/show/release/?releaseid=' . $what['id'] . '">' . substr($what['name'], $skip) . '</a> ' .
		(isset($what['track_count']) ? '(<abbr title="' . $what['track_count'] . ' tracks on this disc.">' . $what['track_count'] . '</abbr>)' : '') .
		'</td>' .
		'<td class="' . comparison($compare_to, $what, 'artist') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'attributes') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'language') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'script') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'asin') . '</td>' .
		'<td class="' . @comparisoni(reldates_string($compare_to['releasedate']), reldates_string($what['releasedate'])) . '</td>' .
		'</tr>';
}

function usort_discnos_real($left, $right)
{
	if (!isset($left['name']))
		var_dump($left);
	if (preg_match('/\(disc ([0-9]+)/', $left['name'], $larl))
		if (preg_match('/\(disc ([0-9]+)/', $right['name'], $rarl))
			return $larl[1] < $rarl[1] ? -1 : 1;
	return 0;
}

foreach ($dat as $acname => $albs)
{
	if (count($albs) < 2)
	{
		echo "$acname unexpectedly empty. Skipping.<br/>";
		continue;
	}

	unset($albs['ids']);
	$skip = strlen($acname) + 1;

	uasort($albs, 'usort_discnos_real');

	$discs = array();
	foreach ($albs as $alb)
		if (preg_match('/\(disc ([0-9]+)/', substr($alb['name'], $skip), $regs))
			@++$discs[$regs[1]];

	$ak = array_keys($discs);
	if (count($ak))
		$high = max($ak);
	else
	{
		echo '<tr><td></td></tr><tr><td colspan="' . $span . '" style="border: 5px solid red; padding: .5em"><h1>Chronic FAIL. This is probably the result of the disc-numbers being against style in some unexpected way. Please fix them, or report this page if it\'s unexpected:</h1><pre>';
		var_dump($acname, $albs);
		echo '</pre></td></tr>';
	}

	@$perfectdupes = $discs[2] > 1;
	@$clean = $discs[1] == 1;

	for ($i=2; $i <= $high; ++$i)
	{
		@$perfectdupes &= ($discs[$i-1] >= $discs[$i]);
		@$clean &= ($discs[$i] == 1);
	}

	if (!$clean)
	{
		$tch = pg_query("SELECT album,COUNT(track) FROM albumjoin WHERE album IN (" . implode(',', array_keys($albs)) . ") GROUP BY album");
		while ($row = pg_fetch_assoc($tch))
			$albs[$row['album']]['track_count'] = $row['count'];
	}

	$first = array_shift($albs);

	if (check_all_same($first, $albs))
		continue;

	echo "<tr><td colspan=\"$span\">&nbsp;</td></tr><tr class=\"hd\"><td colspan=\"$span\"" . ($perfectdupes ? ' class="dirtydupes"' : (!$clean ? ' class="dirty"' : '')) . ">$acname</td></tr>\n";

	line_for($first, $first);

	foreach ($albs as $foo)
		line_for($first, $foo);

}
echo '</table>';
echo '<p>Generated in ' . (time()-$start) . ' seconds.</p>';

?>
</body>
</html>
