<?
require_once('database.inc.php');
my_title();

?><ul><?

$res = pg_query("select release.album,album.name,releasedate from release join album on album.id=release.album where substr(releasedate, 0, 5)<1988 and format=1 order by releasedate asc, name asc");

while ($row = pg_fetch_assoc($res))
	echo '<li>' . $row['releasedate'] . ' - <a href="http://musicbrainz.org/show/release/?releaseid=' . $row['album'] . '">' . $row['name'] . '</a></li>' . "\n";

?>
</ul>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
