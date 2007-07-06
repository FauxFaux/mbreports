<?

if (isset($_SERVER))
	die ('See <a href="badbadasins.html">the static version</a>.');

require_once('database.inc.php');

$res = pg_query("SELECT url, album.id as release, album.name as name, url.id as urlid FROM url, album, l_album_url  WHERE link1 = url.id  AND link0 = album.id AND url LIKE '%amazon.%' AND url !~ 'http://(www\.)?amazon\.[^/]*/gp/product/[B01356][0-9A-Z]{9}(\/){0,1}$' ORDER BY name asc");

echo '<p>' . pg_num_rows($res) . ' found.</p>';

function print_row($row)
{
	return '[ <a href="http://musicbrainz.org/show/url/?urlid=' . $row['urlid'] . '">show</a> ] [ <a href="http://musicbrainz.org/edit/url/edit.html?urlid=' . $row['urlid'] . '">edit</a> ] <a href="' . $row['url'] . '">' . $row['url'] . '</a>';
}

while ($row = pg_fetch_assoc($res))
{
	//
	preg_match('/[A-Z0-9]{10}/', $row['url'], $regs);

	$rohs = pg_query("select url,id as urlid from url where url LIKE '%{$regs[0]}%'");
	if (pg_num_rows($rohs) > 1)
	{
		echo '<ul>';
		while ($nr = pg_fetch_assoc($rohs))
			echo '<li>' . print_row($nr) . '</li>';
		echo "</ul>\n\n";
	}
}

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
