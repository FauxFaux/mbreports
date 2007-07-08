<?
require_once('database.inc.php');
my_title();
?>
<style type="text/css">
	td { border: 1px solid black; padding: .4em }
	a.hit { font-weight: bold; color: black; background: white }
</style>
<?

$typen = @(int)$_GET{'type'};
$official = @(int)$_GET{'official'};

if (!$typen && !$official)
	$typen = 4;

$types = array(
	'Album'          => 1,
	'Single'         => 2,
	'Ep'             => 3,
	'Compilation'    => 4,
	'Soundtrack'     => 5,
	'Spokenword'     => 6,
	'Interview'      => 7,
	'Audiobook'      => 8,
	'Live'           => 9,
	'Remix'          => 10,
	'Other'          => 11
);

$officials = array(
	'Promotion'      => 101,
	'Bootleg'        => 102,
	'Pseudo Release' => 103
);


echo '<ul>';
foreach ($types as $name => $no)
	echo '<li><a href="?type=' . $no . '"' . ($typen == $no ? ' class="hit"' : '') . '>' . $name . '</a>';
echo '</ul>';
echo '<hr/>';
echo '<ul>';
foreach ($officials as $name => $no)
	echo '<li><a href="?official=' . $no . '"' . ($official == $no ? ' class="hit"' : '') . '>' . $name . '</a>';
echo '</ul>';

$res = pg_query("select album.id,album.name,album.attributes,artist.name as artist,album.modpending from album join artist on (album.artist = artist.id) where album.modpending != 0 and attributes[" . ($typen ? "2] = " . $typen : '3] = ' . $official) . " order by id desc limit 100");

echo '<p>' . pg_num_rows($res) . ' shown.</p>';

echo '<table><tr><th>Album</th><th>Artist</th><th>Outstanding edits</th></tr>';

while ($row = pg_fetch_assoc($res))
{
	echo '<tr>' .
		'<td><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['id'] . '">' . $row['name'] . '</a></td>' .
		'<td>' . $row['artist'] . '</td>' .
		'<td>[ <a href="http://musicbrainz.org/mod/search/results.html?object_type=album&orderby=desc&object_id=' . $row['id'] . '">view ' . $row['modpending'] . '</a> ]</td>'
		;
}

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
