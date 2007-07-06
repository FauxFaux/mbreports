<?

require_once('database.inc.php');

if (isset($_GET{'regenerate'}))
{
	ignore_user_abort(true);
	set_time_limit(0);
	echo '<p>Regenerating... you may (but shouldn\'t) close the page.</p>' . "\n";
	flush();
	pg_query("BEGIN;
	DROP TABLE mergeproblems_full;
	CREATE TABLE mergeproblems_full AS SELECT album.id,album.name,album.artist,album.attributes,
			language.name as language,
			script.name as script,
			album_amazon_asin.asin,
			substr(album.name, 0, strpos(album.name, ' (disc ')) AS grouper
			FROM album
			JOIN script ON album.script=script.id
			JOIN language ON album.language=language.id
			LEFT JOIN album_amazon_asin ON album.id=album_amazon_asin.album
			WHERE album.name LIKE '% (disc %'
		ORDER BY grouper;
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
	p.boxedlist a { background-color: #ccc; padding: 0 .4em; border: 1px solid #777; margin-top: .2em }
	p.boxedlist a.current { font-weight: bold; background-color: black; color: white }
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
	$s = '';
	if (!$relds)
		return missing();
	foreach ($relds as $reld)
		$s .= "{$reld['country']} {$reld['releasedate']} <b>{$reld['label']} {$reld['catno']}</b> {$reld['barcode']} <b>{$reld['format']}</b>";
	return $s;
}

@$page = (int)$_GET{'page'};
@$prefix = pg_escape_string($_GET{'prefix'});

$per_page = 500;
$offset = 10;

echo '<p class="boxedlist">Only items starting with: ' .
	'<a ' . ($prefix=='' ? 'class="current" ' : '') . 'href="?prefix=">anything</a> ';

for ($i = ord('a'); $i <= ord('z'); ++$i)
	echo '<a href="?prefix=' . chr($i) . '"' . (chr($i) == $prefix ? ' class="current"' : '') . '>' . chr($i) . '</a> ';

echo '</p>';


$rohs = pg_query("
	SELECT * FROM mergeproblems_full
	WHERE name ILIKE '$prefix%'
	"
);

$rows = pg_num_rows($rohs);
pg_free_result($rohs);

$pages = ((int)($rows/$per_page)+1);

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
	die ('<p>No results to display on this page.</p>');

function check_equal(&$violations, $key, $left, $right)
{
	if ($left[$key] != $right[$key])
		$violations[] = link_for($left) . "'s $key ( " . if_not_missing($left[$key]) . " ) mismatches with " . link_for($right) . ' ( ' . if_not_missing($right[$key]) . ' )';
}

$boxes = $total = 0;

$dat = array();

while ($row = pg_fetch_assoc($rohs))
{
	;
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
<table><tr><th>disc</th><th>Artist</th><th>Attributes</th><th>Language</th><th>Script</th><th>ASIN</th><th>Release events</th></tr>
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
	echo '<tr class="dr"><td><a href="http://musicbrainz.org/show/release/?releaseid=' . $what['id'] . '">' . substr($what['name'], $skip) . '</a></td>' .
		'<td class="' . comparison($compare_to, $what, 'artist') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'attributes') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'language') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'script') . '</td>' .
		'<td class="' . comparison($compare_to, $what, 'asin') . '</td>' .
		'<td class="' . @comparisoni(reldates_string($compare_to['releasedate']), reldates_string($what['releasedate'])) . '</td>' .
		'</tr>';
}

foreach ($dat as $acname => $albs)
{
	if (count($albs) < 2)
	{
		echo "$acname unexpectedly empty. Skipping.<br/>";
		continue;
	}

	$ids = $albs['ids'];

	$res = pg_query("SELECT
		album,isocode as country,releasedate,label,catno,barcode,format
		FROM release
		JOIN country ON (country.id = release.country)
		WHERE album IN (" . implode(",", $ids) . ")
		ORDER BY album,isocode,releasedate,label,catno,barcode,format
	");

	$releasedates = array();

	while ($row = pg_fetch_assoc($res))
	{
		$alb = $row['album'];
		unset($row['album']);
		$albs[$alb]['releasedate'][] = $row;
	}


	unset($albs['ids']);

	reset($albs);
	$starid = key($albs);
	$first = array_shift($albs);

	if (check_all_same($first, $albs))
		continue;

	$skip = strlen($acname) + 1;

	echo "<tr><td colspan=\"$span\">&nbsp;</td></tr><tr class=\"hd\"><td colspan=\"$span\">$acname</td></tr>\n";

	line_for($first, $first);

	foreach ($albs as $foo)
		line_for($first, $foo);

}
echo '</table>';
echo '<p>Generated in ' . (time()-$start) . ' seconds.</p>';

?>
</body>
</html>
