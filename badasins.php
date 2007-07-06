<?
require_once('database.inc.php');
my_title();

$res = pg_query("SELECT url, album.id as release, album.name as name, url.id as urlid FROM url, album, l_album_url  WHERE link1 = url.id  AND link0 = album.id AND url LIKE '%amazon.%' AND url !~ 'http://(www\.)?amazon\.[^/]*/gp/product/[B01356][0-9A-Z]{9}(\/){0,1}$' ORDER BY name asc");

echo '<p>' . pg_num_rows($res) . ' found.</p>';

?><table><tr><th>URL</th><th>Release</th></tr><?

while ($row = pg_fetch_assoc($res))
	echo '<tr><td>[ <a href="http://musicbrainz.org/show/url/?urlid=' . $row['urlid'] . '">show</a> ] [ <a href="http://musicbrainz.org/edit/url/edit.html?urlid=' . $row['urlid'] . '">edit</a> ] <a href="' . $row['url'] . '">' . $row['url'] . '</a></td><td><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['release'] . '">' . $row['name'] . '</a></td>' . "\n";

?>
</table></tr>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
