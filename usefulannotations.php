<style type="text/css">
	td { border: 1px solid black }
</style>
<?
ini_set('max_execution_time', 0);

if (!isset($argv))
	die ('See <a href="usefulannotations.html">the static version</a>.');


require_once('database.inc.php');

$res = pg_query("select rowid,
album.name,
text,releasedate,isocode as country,label,catno,barcode,format
	from annotation
	inner join album on (rowid = album.id)
	left join release on (release.album=rowid)
	join country on (country.id = release.country)
	where
		annotation.type=2 and
		label is null and
		text like 'Label%'
		order by text
	limit 5000");

echo '<p>' . pg_num_rows($res) . ' shown.</p>';

echo '<table><tr><th>Album</th><th>Annotation</th><th>Release info</th></tr>';

while ($row = pg_fetch_assoc($res))
{
	echo '<tr>' .
		'<td><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['rowid'] . '">' . $row['name'] . '</a></td>' .
		'<td>' . nl2br(preg_replace('/\[(http:.*?)\|([^\]]+)\]/', '<a href="\1">\2</a>', $row['text'])) . '</td>' .
		"<td>{$row['country']} {$row['releasedate']} <b>{$row['label']} {$row['catno']}</b> {$row['barcode']} <b>{$row['format']}</b></td>"
		;
}

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
