<?
/*
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
		ORDER BY grouper
*/

require_once('database.inc.php');
my_title();

function missing()
{
	return '{{missing}}';
}

function if_not_missing($var)
{
	if ($var)
		return $var;
	return missing();
}

function link_for($det)
{
	return "<a href=\"http://musicbrainz.org/show/release/?releaseid={$det['id']}\">{$det['name']}</a>";
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

$per_page = 500;
$offset = 10;

if ($page > 0)
	echo '<a href="?page=' . ($page-1) . '">&lt;-- previous page</a> ';

echo '<a href="?page=' . ($page+1) . '">next page --&gt;</a>';

$rohs = pg_query("
	SELECT * FROM mergeproblems_full
	LIMIT " . ($per_page+$offset*2) . " OFFSET " . ($page*$per_page - $offset) . "
	"
);

function check_equal(&$violations, $key, $left, $right)
{
	if ($left[$key] != $right[$key])
		$violations[] = link_for($left) . "'s $key ( " . if_not_missing($left[$key]) . " ) mismatches with " . link_for($right) . ' ( ' . if_not_missing($right[$key]) . ' )';
}

$boxes = $total = 0;

$dat = array();

while ($row = pg_fetch_assoc($rohs))
{
	@$dat[$row['grouper']][] = $row;
	@$dat[$row['grouper']]['ids'][] = $row['id'];
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
	");

	$releasedates = array();

	while ($row = pg_fetch_assoc($res))
	{
		$alb = $row['album'];
		unset($row['album']);
		$releasedates[$alb][] = $row;
	}

	//var_dump($releasedates);

	$violations = array();
	unset($albs['ids']);
	$rawk = $albs;


	$prev = array_shift($rawk);
	foreach ($rawk as $det)
	{
		$rel1 = @$releasedates[$det['id']];
		$rel2 = @$releasedates[$prev['id']];
		if ($rel1 != $rel2)
			$violations[] = link_for($det) . "'s release-info ( " . reldates_string($rel1) . ") mismatches with " . link_for($prev) . ' ( ' . reldates_string($rel2) . ')';

		foreach (array('artist', 'attributes', 'language', 'script', 'asin') as $key)
			check_equal($violations, $key, $det, $prev);


		$prev = $det;
	}

	$prev = $det = null;

	if ($violations)
	{
		$total+=count($violations);
		++$boxes;
		echo "<h3>Mismatches in $acname</h3><ul><li>" . implode("</li><li>", $violations) . '</li></ul>';
	}

}
echo "<p>Generated in " . (time()-$start) . " seconds. $total total, $boxes things hit.</p>";
?>
</body>
</html>
