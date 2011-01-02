<?
require_once('database.inc.php');
my_title();

?>
<form method="GET">
Search: <input type="text" name="search" style="width: 50%" /> <input type="submit" />
</form>
<?

@$search = pg_escape_string($_GET{'search'});

if (strlen($search) == 0)
	die();

if (strlen($search) < 2)
	die("Please provide moar search term.");

echo '<ol>';
$charh = pg_query("select id,gid,name,sortname,resolution from artist where resolution ilike '%" . $search . "%' order by resolution,name");

while ($row = pg_fetch_assoc($charh))
	echo '<li>' . $row['resolution'] . ' (<a href="http://musicbrainz.org/artist/' . $row['gid'] . '.html">' . htmlentities($row['name']) . '</a> [<a href="http://musicbrainz.org/edit/artist/edit.html?artistid=' . $row['id'] . '">edit</a>])</li>' . "\n";

?>
</ol>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
