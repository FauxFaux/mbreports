<?
require_once('database.inc.php');
my_title();

?>
<table><tr><th>catno</th><th>label</th><th>barcode</th><th>release</th></tr>
<?
$start = time();

require_once('database.inc.php');

function missing()
{
	return '{{missing}}';
}

function if_not_missing($var)
{
	if ($var)
		return $var;
	return missing();
}

function link_for($det)
{
	return "<a href=\"http://musicbrainz.org/album/{$det['gid']}.html\">{$det['name']}</a>";
}

function reldates_string($relds)
{
	$s = '';
	if (!$relds)
		return missing();
	foreach ($relds as $reld)
		$s .= "{$reld['country']} {$reld['releasedate']} <b>{$reld['label']} {$reld['catno']}</b> {$reld['barcode']} <b>{$reld['format']}</b>";
	return $s;
}


$res = pg_query("select label.name, release.catno, release.barcode, album.name as albumname, artist.name as artistname, album.gid from release left join album on album.id=release.album left join artist on artist.id=album.artist left join label on label.id = release.label where catno like 'B000%' or catno like 'LC%' order by label.name,catno desc");

while ($row = pg_fetch_assoc($res))
	echo "<tr><td>{$row['catno']}</td><td>{$row['name']}</td><td>{$row['barcode']}</td><td><a href=\"http://musicbrainz.org/album/{$row['gid']}.html\">{$row['artistname']} - {$row['albumname']}</a></td></tr>";

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.";?>
</body>
</html>
