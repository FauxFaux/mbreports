<?
ini_set ('max-execution_time', '0' );
require_once('database.inc.php');
$ids = @$_REQUEST{'ids'};

if (!$ids)
	die ('<form method="post"><textarea style="width: 100%; height: 40em" name="ids"></textarea><br/><input type="submit"/></form>');

header('Content-type: text/plain');
if (preg_match_all('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $ids, $regs))
{

	$query = " gid in ('" . implode("','", array_map('pg_escape_string', $regs[1])) . "')";
}
else
{
	$ex = explode(',', $ids);
	$ids = array();
	foreach ($ex as $a)
		$ids[] = pg_escape_string((int)$a);
	$query = " track.id in (" . implode(',', $ids) . ")";
}

$res = pg_query("select track.id,albumjoin.album as aid,gid,length from track join albumjoin on track.id=albumjoin.track where $query");

while ($row = pg_fetch_assoc($res))
	echo "{$row['id']}\t{$row['gid']}\t{$row['aid']}\t{$row['length']}\n";
