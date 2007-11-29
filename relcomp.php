<?
require_once('database.inc.php');
$ids = @$_REQUEST{'ids'};

header('Content-type: text/plain');
if (preg_match_all('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $ids, $regs))
	$query = " gid in ('" . implode("','", array_map('pg_escape_string', $regs[1])) . "')";
else
{
	$ex = explode(',', $ids);
	$ids = array();
	foreach ($ex as $a)
		$ids[] = pg_escape_string((int)$a);
	$query = " id in (" . implode(',', $ids) . ")";
}

$res = pg_query("select album.id,album.name,artist.id, artist.name from album join artist on (artist.id=album.artist) where $query");

while ($row = pg_fetch_assoc($res))
	echo "{$row['id']} {$row['gid']}\n";
