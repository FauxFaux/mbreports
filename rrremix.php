<?
require_once('database.inc.php');
my_title();

ini_set('max_execution_time', 120);

?><ul><?

$res = pg_query("
	select album.id,album.name,count(*) as cnt from album join albumjoin on (album.id=albumjoin.album) where albumjoin.track in 
			(select track.id from track where name like '%Remix%')
			group by album.id,album.name order by cnt desc, album.name
			");

while ($row = pg_fetch_assoc($res))
	echo "<li>{$row['cnt']}: <a href=\"http://musicbrainz.org/edit/album/editall.html?releaseid={$row['id']}\">{$row['name']}</a></li>\n";

$rows = pg_num_rows($res);
echo "<p>$hits out of $rows found. Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
