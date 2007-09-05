<?
require_once('database.inc.php');
my_title();

$res = pg_query("select album.id as release, album.name, url.id as urlid, url.url
	from l_album_url
	join url on url.id = link1
	join album on album.id = link0
	where link_type = 34 and
	(
		url.url not ilike '%.jpg' and
		url.url not ilike '%.gif'
		and url.url not ilike '%.png'
		and url.url not ilike '%.jpeg'
	)
	order by url.url asc
");

echo '<p>' . pg_num_rows($res) . ' found.</p>';

?><table><tr><th>URL</th><th>Release</th></tr><?

while ($row = pg_fetch_assoc($res))
	echo '<tr><td><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['release'] . '">' . $row['name'] . '</a> ' . '</td><td>[ <a href="http://musicbrainz.org/show/url/?urlid=' . $row['urlid'] . '">show</a> ] [ <a href="http://musicbrainz.org/edit/url/edit.html?urlid=' . $row['urlid'] . '">edit</a> ] <a href="' . $row['url'] . '">' . $row['url'] . '</a></td>' . "\n";

?>
</table></tr>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
