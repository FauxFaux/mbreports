<style type="text/css">
	th,td { border: 1px solid black; padding: .4em }
	tr.dirty td { background-color: #ffffcc }
</style>
<?
require_once('database.inc.php');
my_title();

if (!preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $_SERVER{'QUERY_STRING'}, $regs))
	die('<p>Please provide an artist gid (or url containing gid): <form method="get"><input length="36" style="width: 38em" type="text" name="pony" /><br />(or, if you\'re using a real browser): <input length="36" style="width: 100%" type="url" name="horse" /><br /><input type="submit" /></form></p>');

$gid = pg_escape_string($regs[1]);

echo '<table>';

$res = pg_query("select
		track.id,track.length,track.name,album.gid,album.name as alname
	from
		artist
		join track on (track.artist = artist.id)
		join albumjoin on (albumjoin.track = track.id)
		join album on (albumjoin.album = album.id)
	where
		artist.gid = '$gid'
	order by
		track.name asc,track.length asc,album.name asc,id asc
");

echo "<tr><th colspan=\"3\">" . pg_num_rows($res) . " hits.</th></tr>";

function hrtime($t)
{
	$t/=1000;
	if ($t == 0)
		return "?:??";
	$m = (int)($t/60);
	$s = (int)($t-($m*60));
	return "$m:" . str_pad($s, 2, "0");
}

$lastname = '';

$uniq = 0;

while ($row = pg_fetch_assoc($res))
{
	if ($row['name'] != $lastname)
	{
		$lastname = $row['name'];
		echo "<tr><td style=\"border: none\" colspan=\"3\"><hr/></td></tr>";
		++$uniq;
	}
	echo "<tr><td>" . hrtime($row['length']) . "</td><td><a href=\"http://musicbrainz.org/show/track?trackid={$row['id']}\">{$row['name']}</a></td><td><a href=\"http://musicbrainz.org/album/{$row['gid']}.html\">{$row['alname']}</a></td>";
}

echo "<tr><th colspan=\"3\">" . $uniq . " unique songs.</th></tr>";

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.";?>
</body>
</html>
