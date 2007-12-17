<style type="text/css">
	body { background-color: white; color: black }
	.bore { color: grey }
	.diff { color: red }
	p.boxedlist a { background-color: #ddd; padding: 0 .4em; border: 1px solid #777; margin-top: .2em }
	p.boxedlist a.current { font-weight: bold; background-color: black; color: white }
	span.reldate { margin-right: 1em }
	span.relcountry { padding: .2em; border: 1px dashed black }
	.dirty { background-color: #fec }
	.dirtydupes { background-color: #fcc }
	th,tr.dr td { border: 1px solid black; padding: .4em }
	tr.hd td { border: 2px solid black; padding: 1em; font-weight: bold; font-size: 140% }
</style>

<ol>
<?
require_once('database.inc.php');
my_title();

$res = pg_query("select gid,name from artist where id in (select distinct artist from album where attributes[2] = 5 and artist not in (select artist from album where album.attributes[2] = 1) and artist in (select artist from (select artist,count(*) from album group by artist) as lulz where count < 5)) order by name asc");

while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://musicbrainz.org/artist/' . $row['gid'] . '">' . $row['name'] . '</a></li>' . "\n";

?>
</ol>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
