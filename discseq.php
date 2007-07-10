<?
require_once('database.inc.php');
my_title();

?><ul><?

$res = pg_query("select id,name from album where name like '% (disc %' order by name limit 50");

$albids = $albs = array();

while ($row = pg_fetch_assoc($res))
	preg_match('/^(.*?) \(disc ([0-9]+)(?:: (.*?))?\)/', $row['name'], $r) and @$albs[$r[1]][$r[2]][] = array($r[3], $row['id']);

print_r($albs);

?>
</ul>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
