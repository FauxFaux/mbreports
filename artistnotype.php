<?
require_once('database.inc.php');
my_title();

@$page = (int)$_GET{'page'};

if ($page > 0)
	echo '<a href="?page=' . ($page-1) . '">&lt;-- previous page</a> ';

	echo '<a href="?page=' . ($page+1) . '">next page --&gt;</a>';
?>

<ol start="<?=($page*200)+1?>">
<?
$start = time();

require_once('database.inc.php');

$res = pg_query("select name,gid,type from artist where type is null order by name asc LIMIT 200 OFFSET " . ($page*200));

while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://musicbrainz.org/artist/' . $row['gid'] . '">' . $row['name'] . '</a></li>' . "\n";

?>
</ol>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
