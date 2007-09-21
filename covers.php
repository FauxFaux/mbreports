<style type="text/css">
th,td { text-align: left; border: 1px solid black; padding: .4em }
</style>
<table>
<?
ini_set('max_execution_time', 0);

header('Content-type: text/html; ; charset=utf-8');
require_once('database.inc.php');

if (isset($_GET{'regenerate'}))
{
	ignore_user_abort(true);
	$res = pg_query("begin; drop table if exists tracks_covers; create table tracks_covers as select track.id,track.name,length,artist.name as artist from track join artist on track.artist=artist.id where track.name ilike '%(%cover)%' order by name asc; commit;");
	die('Done.');
}

echo '<p><a href="?regenerate">Regenerate cache</a></p>';

@$pf = pg_escape_string($_GET{'prefix'});
$a = pg_query("select id,name,length,artist from tracks_covers where name ilike '$pf%'");

while ($j = pg_fetch_assoc($a))
{
	$n = $j['name'];
	echo "<tr><th colspan=\"2\">{$j['artist']} - <a href=\"http://musicbrainz.org/show/track/?trackid={$j['id']}\">$n</a>";
	$n = preg_replace('/\(.*?\)/', '', $n);

	if (!preg_match('/^([a-z0-9 ]{4,}?[a-z0-9]*)/i', $n, $prereg))
		$query = " = '" . pg_escape_string($n) . "'";
	else
	{
		preg_match_all('/([a-z0-9]{1,})/i', substr($n, strlen($prereg[1])), $regs);
		$query = " LIKE '" . $prereg[1] . '%' . implode('%', array_map('pg_escape_string', $regs[1])) . "%'";
	}

	$length = $j['length'];
	echo ($length == 0 ? ' (no length, not working properly, ignore me for now ;))' : '') . "</th>";

	$wiggle_room = 5000;

	$query = ("select track.id,artist.name as artist, track.name as track,length,abs(length - {$length}) as dist from track join artist on (artist.id = track.artist) where artist != 97546 and track.name $query and length BETWEEN " . ($length - $wiggle_room). " AND " . ($length + $wiggle_room) . " order by dist asc limit 8;");
	$begin = microtime(true);
	$res = pg_query($query);
	while ($row = pg_fetch_assoc($res))
	{
		echo "<tr><td style=\"text-align: right\">" . round($row['dist']/1000.0, 1) . "</td><td><a href=\"http://musicbrainz.org/show/track/?trackid={$row['id']}\">{$row['track']}</a> ({$row['artist']})</td></tr>";
	}

	if (microtime(true)-$begin > 2)
		echo '<tr><td colspan="2">Disasterously slow query (' . round(microtime(true)-$begin) . ' seconds): ' . $query . '</td></tr>';
}
