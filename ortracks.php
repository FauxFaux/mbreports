<?
require_once('database.inc.php');
my_title();

ini_set('max_execution_time', '0');

?><ul><?

$res = pg_query("select track.id,track.name from track where track.name like '%`%' or track.name like '%´%' order by name");

while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://musicbrainz.org/show/track/?trackid=' . $row['id'] . "\">{$row['name']}</a></li>\n";
?>
</ul>

<? $rows = pg_num_rows($res);
echo "<p>$rows found. Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
