<?
require_once('database.inc.php');
my_title();
//create view mergeables as SELECT array_accum(id) as arr,SUBSTR(name, 0, STRPOS(name, ' (disc')) AS name FROM album WHERE name LIKE '% (disc%' GROUP BY SUBSTR(name, 0, STRPOS(name, ' (disc'));
$res = pg_query("select album.id as release,album.name from mergeables join album on arr[1] = album.id where array_dims(arr)='[1:1]' order by name;");

echo '<p>' . pg_num_rows($res) . ' found.</p>';

?><ol><?

while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['release'] . '">' . $row['name'] . '</a></li>' . "\n";

?>
</ol>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
