<?
require_once('database.inc.php');
my_title();

ini_set('max_execution_time', 180);

function gen_sql($thing)
{
	return "select '" . ($thing == 'album' ? 'release' : $thing) . "'::text as url_type, {$thing}.name, link0 as thing_id, link1 as urlid, linkphrase, url.url
		from l_{$thing}_url
		join lt_{$thing}_url on (l_{$thing}_url.link_type = lt_{$thing}_url.id)
		join url on (url.id = link1)
		join {$thing} on ({$thing}.id = link0)
		WHERE
		(lt_{$thing}_url.name = 'amazon asin' AND url NOT ILIKE 'http://%amazon.%/%/%/%') OR
		(lt_{$thing}_url.name = 'cover art link' AND (url NOT ILIKE 'http://%archive.org/%' AND url NOT ILIKE 'http://%cdbaby.%/%' AND url NOT ILIKE 'http://%jamendo.%/{$thing}/%')) OR
		(lt_{$thing}_url.name = 'IMDb' AND url NOT ILIKE 'http://%imdb.%/%') OR
		(lt_{$thing}_url.name = 'musicmoz' AND url NOT ILIKE 'http://%musicmoz.org/%/%/%') OR
		(lt_{$thing}_url.name = 'discogs' AND url NOT ILIKE 'http://%discogs.%/%') OR
		(lt_{$thing}_url.name = 'wikipedia' AND url NOT ILIKE 'http://%.wikipedia.org/%');";
}

$sql = 'begin;
drop table if exists bad_url_types;
create table bad_url_types as ' . gen_sql('album');

foreach (array('label', 'artist') as $thing)
	$sql .= 'insert into bad_url_types ' . gen_sql($thing) . "\n\n";

$sql .= 'commit;';

pg_query($sql) or die (pg_last_error());

$res = pg_query("select * from bad_url_types
	ORDER BY linkphrase ASC,url_type ASC,url ASC
");

echo '<p>' . pg_num_rows($res) . ' found.</p>';

?><table><tr><th>Thing</th><th>URL</th></tr><?

while ($row = pg_fetch_assoc($res))
{
	if (isset($last) && $row['linkphrase'] != $last)
		echo '<tr><td colspan="2"><hr/></td></tr>';
	echo '<tr><td>' . $row['url_type'] . ': <a href="http://musicbrainz.org/show/' . $row['url_type'] . '?' . $row['url_type'] . 'id=' . $row['thing_id'] . '">' . $row['name'] . '</a> ' . ($last = $row['linkphrase']) . '</td><td>[ <a href="http://musicbrainz.org/show/url/?urlid=' . $row['urlid'] . '">show</a> ] [ <a href="http://musicbrainz.org/edit/url/edit.html?urlid=' . $row['urlid'] . '">edit</a> ] <a href="' . $row['url'] . '">' . $row['url'] . '</a></td>' . "\n";
}

?>
</table></tr>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
