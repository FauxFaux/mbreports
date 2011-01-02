<?
ini_set('max_execution_time', 0);

if (!isset($argv))
	die ('Non.');


require_once('database.inc.php');

$res = pg_query(
//"	create or replace function concat(left text, rig anyelement) returns text as $$ begin return lef||rig;end$$ language plpgsql;" .
//"	drop aggregate append_accum(anyelement); create aggregate append_accum(anyelement) (sfunc = concat, stype = text, initcond = '');" .
	"
select album.gid,
album.name||'||'||artist.name||'||'||(
	select append_accum(poo)
	from (
		select albumjoin.sequence||'||'||artist.name||'||'||track.name||';;'
		as poo
		from albumjoin 
		join track on (track.id=albumjoin.track) 
		join artist on (track.artist = artist.id) 
		where albumjoin.album=album.id order by sequence
	) as donkey
)
as stuffs
from album
join albumjoin on (albumjoin.album = album.id)
join artist on (album.artist = artist.id)
group by album.id,album.gid, album.name, artist.name
");

echo pg_num_rows($res) . "\n";

while ($row = pg_fetch_assoc($res))
	echo substr($row['gid'],0,12) . ':' . substr(sha1($row['stuffs']), 0, 8) . "\n";
echo "\nGenerated in " . (time()-$start) . " seconds.\n";?>

