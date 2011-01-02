<style type="text/css">
	th,td { border: 1px solid black; padding: .4em }
	tr.dirty td { background-color: #ffffcc }
</style>
<base href="http://musicbrainz.org/"/>
<?
require_once('database.inc.php');

if (!preg_match_all('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $_SERVER{'QUERY_STRING'}, $regs))
	die('<p>Please provide an artist gid (or url containing gid): <form method="get"><input length="36" style="width: 38em" type="text" name="pony" /><br />(or, if you\'re using a real browser): <input length="36" style="width: 100%" type="url" name="horse" /><br /><input type="submit" /></form></p>');

$gid = implode("','", array_map('pg_escape_string', $regs[1]));

$res = pg_query("select
		artist.name
	from
		artist
	where
		artist.gid IN ('$gid')
");

$ars=array();
while ($ar = pg_fetch_assoc($res))
	$ars[] = $ar['name'];

sort($ars);

my_title(implode(' and ', $ars));

echo '<table>';



$res = pg_query("select
		albumjoin.sequence,track.id,track.length,track.name,track.modpending,album,album.id as aid,album.name as alname
	from
		artist
		join track on (track.artist = artist.id)
		join albumjoin on (albumjoin.track = track.id)
		join album on (albumjoin.album = album.id)
	where
		artist.gid IN ('$gid')
	order by
		track.name asc,track.length asc,album.name asc,id asc
");

echo "<tr><th colspan=\"6\">" . pg_num_rows($res) . " hits.</th></tr>";

function hrtime($t)
{
	$t/=1000;
	if ($t == 0)
		return "?:??";
	$m = (int)($t/60);
	$s = (int)($t-($m*60));
	return "$m:" . str_pad($s, 2, "0", STR_PAD_LEFT);
}

$lastname = '';

$uniq = 0;

while ($row = pg_fetch_assoc($res))
{
	if ($row['name'] != $lastname)
	{
		$lastname = $row['name'];
		echo "<tr><td style=\"border: none\" colspan=\"6\"><hr/></td></tr>";
		++$uniq;
	}
	echo "<tr" . ($row['modpending'] ? ' class="dirty"' : '') . "><td>" . hrtime($row['length']) . "</td>
		<td><a href=\"edit/track/edit.html?trackid={$row['id']}&releaseid={$row['aid']}\">edit</a></td>
		<td><a href=\"edit/annotation/track/edit.html?id={$row['id']}\">ann.</a></td>
		<td><a href=\"edit/track/change.html?trackid={$row['id']}&releaseid={$row['aid']}&tindex=" . ($row['sequence']-1) . "&solo=1\">change</a></td>
		<td><a href=\"show/track?trackid={$row['id']}\">{$row['name']}</a></td>
		<td><a href=\"show/release/?releaseid={$row['aid']}\">{$row['alname']}</a></td>";
}

echo "<tr><th colspan=\"6\">" . $uniq . " unique songs.</th></tr>";

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.";?>
</body>
</html>

