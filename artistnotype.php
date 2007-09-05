<style type="text/css">
	body { background-color: white; color: black }
	.bore { color: grey }
	.diff { color: red }
	p.boxedlist a { background-color: #ddd; padding: 0 .4em; border: 1px solid #777; margin-top: .2em }
	p.boxedlist a.current { font-weight: bold; background-color: black; color: white }
	span.reldate { margin-right: 1em }
	span.relcountry { padding: .2em; border: 1px dashed black }
	.dirty { background-color: #fec }
	.dirtydupes { background-color: #fcc }
	th,tr.dr td { border: 1px solid black; padding: .4em }
	tr.hd td { border: 2px solid black; padding: 1em; font-weight: bold; font-size: 140% }
</style>

<?
require_once('database.inc.php');
my_title();

$per_page = 500;

@$prefix = pg_escape_string($_GET{'prefix'});


$page_suffix = '<a href="?prefix=' . $prefix . '&amp;page=';

@$page = (int)$_GET{'page'};
ob_start();
if ($page > 0)
	echo $page_suffix . ($page-1) . '">&lt;-- previous page</a> ';

	echo $page_suffix . ($page+1) . '">next page --&gt;</a>';
$buttons = ob_get_contents();
ob_end_flush();

?>
<ol start="<?=($page*$per_page)+1?>">
<?

echo '<p class="boxedlist">Only items starting with: ' .
	'<a ' . ($prefix=='' ? 'class="current" ' : '') . 'href="?prefix=">anything</a> ';

$charh = pg_query('SELECT cha FROM mergeproblems_characters');
while ($row = pg_fetch_assoc($charh))
	echo '<a href="?prefix=' . $row['cha'] . '"' . ($row['cha'] == strtolower($prefix) ? ' class="current"' : '') . '>' . $row['cha'] . '</a> ';

echo '</p>';

$res = pg_query("select name,gid,type from artist where type is null AND name ILIKE '{$prefix}%' order by name asc LIMIT $per_page OFFSET " . ($page*$per_page));

while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://musicbrainz.org/artist/' . $row['gid'] . '">' . $row['name'] . '</a></li>' . "\n";

?>
</ol>
<?=$buttons?>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
