<style type="text/css">
	.missingtimes { border: 1px solid black; color: red; background: red; padding: 0 .4em }
	.tcmatch { color: black; background-color: #afa; padding: .1em }
</style>
<?
require_once('database.inc.php');
my_title();

$res = pg_query("select album.id, album.name, artist.name as artist, track_count, array_search(tracklist, 0) from album join album_tracklist on album.id = album_tracklist.album join artist on album.artist = artist.id where album.name ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}.*' order by album.name,track_count");

$dates = array();
while ($row = pg_fetch_assoc($res))
	$dates[substr($row['name'], 0, 10)][] = $row;

$missingtimes = '<abbr class="missingtimes" title="Missing times (ie. not on normal dupscan)">t</abbr>';

function discseq($rowset)
{
	$discseq = 0;
	foreach ($rowset as $row)
		if (!strpos($row['name'], '(disc ' . ++$discseq))
			return false;
	return true;
}

foreach ($dates as $date => $rowset)
{
	if (count($rowset) < 2)
		continue;

	if (discseq($rowset))
		continue;

	$lasttc = 0;

	echo "<h4>$date</h4><ul>";
	foreach ($rowset as $row)
	{
		$tcmatch = $row['track_count'] == $lasttc;
		echo '<li>' . ($tcmatch ? '<span class="tcmatch">' : '') . '(' . str_pad($row['track_count'], 2, '0', STR_PAD_LEFT) . ')' . ($tcmatch ? '</span>' : '') . ' - <a href="http://musicbrainz.org/show/release/?releaseid=' . $row['id'] . '">' . $row['name'] . '</a> (' . $row['artist'] . ') ' . ($row['array_search'] == 't' ? $missingtimes : '') . '</li>' . "\n";
		$lasttc = $row['track_count'];
	}
	echo '</ul>';
}
?>

<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
