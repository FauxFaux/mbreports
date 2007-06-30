<?
require_once('database.inc.php');
my_title();

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


$res = pg_query("select album.gid,album.name,barcode from release join album on album.id=release.album where barcode ~ '^0?0946[0-9]{8}$'");

while ($row = pg_fetch_assoc($res))
	echo "<a href=\"http://musicbrainz.org/album/{$row['gid']}.html\">{$row['name']} - {$row['barcode']}</a><br/>";

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.";?>
</body>
</html>
