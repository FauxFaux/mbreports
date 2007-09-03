<?
require_once('database.inc.php');
my_title();

$res = pg_query("select url.description,album.name as name,album.id as release,url.url,url.id as urlid,lt_album_url.linkphrase
	from l_album_url join album on (album.id=l_album_url.link0)
	join url on (url.id=l_album_url.link1)
	join lt_album_url on (link_type=lt_album_url.id)
	where
	(link_type = 30 AND url NOT LIKE 'http://%amazon.%/%/%/%') OR
	(link_type = 34 " /* cover art */. "AND (url NOT LIKE 'http://%archive.org/%' AND url NOT LIKE 'http://%cdbaby.%/%' AND url NOT LIKE 'http://%jamendo.%/album/%')) OR
	(link_type = 27 AND url NOT LIKE 'http://%imdb.%/%') OR
	(link_type = 25 AND url NOT LIKE 'http://%musicmoz.org/%/%/%') OR
	(link_type = 24 AND url NOT LIKE 'http://%discogs.%/%') OR
	(link_type = 23 AND url NOT LIKE 'http://%.wikipedia.org/%')
	ORDER BY link_type ASC,url ASC
");

echo '<p>' . pg_num_rows($res) . ' found.</p>';

?><table><tr><th>URL</th><th>Release</th></tr><?

while ($row = pg_fetch_assoc($res))
{
	if (isset($last) && $row['linkphrase'] != $last)
		echo '<tr><td colspan="2"><hr/></td></tr>';
	echo '<tr><td><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['release'] . '">' . $row['name'] . '</a> ' . ($last = $row['linkphrase']) . '</td><td>[ <a href="http://musicbrainz.org/show/url/?urlid=' . $row['urlid'] . '">show</a> ] [ <a href="http://musicbrainz.org/edit/url/edit.html?urlid=' . $row['urlid'] . '">edit</a> ] <a href="' . $row['url'] . '">' . $row['url'] . '</a></td>' . "\n";
}

?>
</table></tr>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
