<?
require_once('database.inc.php');
my_title();

ini_set('max_execution_time', 50);

?><ul><?

$res = pg_query("select album.id,album.name from album where album.language = 309 order by album.name");
$albs = array();

while ($row = pg_fetch_assoc($res))
	$albs[$row['id']] = $row['name'];

$ids = implode(',', array_keys($albs));
$res = pg_query("select track.id,track.name,albumjoin.album,sequence from track join albumjoin on track.id=albumjoin.track where album in ($ids) order by album, sequence");

$hits = 0;

while ($row = pg_fetch_assoc($res))
{
	$int = mb_substr($row['name'], 1, mb_strlen($row['name']), 'utf-8');
	$lower = mb_strtolower($int, 'utf-8');
	if ($int != $lower)
	{
		echo '<li><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['album'] . '">' . $albs[$row['album']] . "</a> {$row['sequence']}. {$row['name']}</li>\n";
		++$hits;
	}
}
?>
</ul>

<? $rows = pg_num_rows($res);
echo "<p>$hits out of $rows found. Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
