<?
require_once('database.inc.php');
my_title();

ini_set('max_execution_time', 180);
$rules = array(
	'amazon asin' =>		"url NOT ILIKE 'http://%amazon.%/%/%/%'",
	'cover art link' =>		"(url NOT ILIKE 'http://%archive.org/%' AND url NOT ILIKE 'http://%cdbaby.%/%' AND url NOT ILIKE 'http://%jamendo.%/album/%')",
	'IMDb' =>				"url NOT ILIKE 'http://%imdb.%/%'",
	'musicmoz' =>			"url NOT ILIKE 'http://%musicmoz.org/%/%/%'",
	'discogs' =>			"url NOT ILIKE 'http://%discogs.%/%'",
	'purevolume' =>			"url NOT ILIKE 'http://www.purevolume.com/%'",
	'myspace' =>			"url NOT ILIKE 'http://%myspace.com/%'",
	'purchase for mail-order' => "url ILIKE 'http://%archive.org/%'",
	'purchase for download' => "url ILIKE 'http://%archive.org/%'",
	'wikipedia' =>			"url NOT ILIKE 'http://%wikipedia.org/%'"
);

$types = array('album', 'label', 'artist');

$lttypes = array();
foreach ($types as $type)
{
	$rohs = pg_query("SELECT name,linkphrase FROM lt_{$type}_url");
	while($row = pg_fetch_assoc($rohs))
		$lttypes[$row['name']] = $row['linkphrase'];
}

$ruledesc = array();

$tools = $rules;

foreach ($lttypes as $key => $lph)
{
	$b = isset($rules[$key]);
	$ruledesc[] = ($b ? 'Rule' : '<span style="font-weight: bold">No rule</span>') . " for $key ('$lph')" . ($b ? ": <span style=\"font-family: monospace\">{$rules[$key]}</span>" : '') . '.';
	unset($tools[$key]);
}

foreach ($tools as $key => $rule)
	$ruledesc[] = "Unused rule for '$key'";

sort($ruledesc);

function gen_sql($thing)
{
	global $rules;
	$sql = "select '" . ($thing == 'album' ? 'release' : $thing) . "'::text as url_type, l_{$thing}_url.id as link_id, {$thing}.name, link0 as thing_id, link1 as urlid, linkphrase, url.url
		from l_{$thing}_url
		join lt_{$thing}_url on (l_{$thing}_url.link_type = lt_{$thing}_url.id)
		join url on (url.id = link1)
		join {$thing} on ({$thing}.id = link0)
		WHERE ";

	foreach ($rules as $rule => $data)
		$sql .= "(lt_{$thing}_url.name = '{$rule}' AND $data) OR \n";

	$sql .= "FALSE;"; // LAZY
	return $sql;
}

if (isset($_GET{'regenerate'}))
{
	ignore_user_abort(true);
	ini_set('max_execution_time', 180);

	$sql = 'begin;
	drop table if exists bad_url_types;
	create table bad_url_types as ' . gen_sql(array_pop($types));

	foreach ($types as $thing)
		$sql .= 'insert into bad_url_types ' . gen_sql($thing) . "\n\n";

	$sql .= 'commit;';

	pg_query($sql) or die (pg_last_error());
	die('Done. <a href="' . $_SERVER['SCRIPT_NAME'] . '">Go back</a>.');
}

$res = pg_query("select * from bad_url_types " .
	(isset($_GET{'catcatlovesthepony'}) ? "WHERE url_type = 'release' " : '') . "
	ORDER BY linkphrase ASC,url_type ASC,url ASC
");

echo '<p>' . pg_num_rows($res) . ' found. <a href="?regenerate">Regenerate cache (&lt; one minute).</a></p>';

?><table><tr><th>Thing</th><th>URL</th></tr><?

while ($row = pg_fetch_assoc($res))
{
	if (isset($last) && $row['linkphrase'] != $last)
		echo '<tr><td colspan="2"><hr/></td></tr>';
	$url_type_other = ($row['url_type'] == 'release' ? 'album' : $row['url_type']);
	echo '<tr><td>' . $row['url_type'] . ': <a href="http://musicbrainz.org/show/' . $row['url_type'] . '?' . $row['url_type'] . 'id=' . $row['thing_id'] . '">' . $row['name'] . '</a> ' .
		($last = $row['linkphrase']) . '</td><td>' .
		'[ <a href="http://musicbrainz.org/show/url/?urlid=' . $row['urlid'] . '">show</a> ] ' .
		'[ <a href="http://musicbrainz.org/edit/url/edit.html?urlid=' . $row['urlid'] . '">edit url</a> ] ' .
		'[ <a href="http://musicbrainz.org/edit/relationship/remove.html?type=' . $url_type_other . '-url&id=' . $row['link_id'] . '">rem. rel.</a> ] ' .
		'[ <a href="http://musicbrainz.org/edit/relationship/edit.html?type=' . $url_type_other . '-url&id=' . $row['link_id'] . '">edit rel.</a> ] ' .
		'<a href="' . $row['url'] . '">' . $row['url'] . '</a></td>' . "\n";
}

?>
</table></tr>
<?
echo "<ul><li>" . implode('</li><li>', $ruledesc) . '</li></ul>';
echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
