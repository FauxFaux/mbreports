<style type="text/css">
	th,td { border: 1px solid black; padding: .4em }
	tr.dirty td { background-color: #ffffcc }
</style>
<?
require_once('database.inc.php');
my_title();

if (!preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/', $_SERVER{'QUERY_STRING'}, $regs))
	die('<p>Please provide an artist gid (or url containing gid): <form method="get"><input length="36" style="width: 38em" type="text" name="pony" /><br />(or, if you\'re using a real browser): <input length="36" style="width: 100%" type="url" name="horse" /><br /><input type="submit" /></form></p>');

$gid = pg_escape_string($regs[1]);

echo '<table>';

$res = pg_query("select
	album.id,album.gid,album.name,(COALESCE(album.modpending,0)+COALESCE(modpending_lang,0)+modpending_qual+release.modpending) as modpending,
	language.name as language,script.name as script,
	country.isocode as country,releasedate,label,catno,barcode,format,
	attributes[2]
	from album
		left join release on release.album=album.id
		left join label on label.id=release.label
		left join country on (country.id = release.country)
		left join language on language.id = album.language
		left join script on script.id = album.script
	where
		artist=(select id from artist where artist.gid='$gid') and
		label is null and
		attributes[3] != 102
	order by
		attributes[2], releasedate, name
");

echo "<tr><th colspan=\"10\">" . pg_num_rows($res) . " hits.</th></tr>";

while ($row = pg_fetch_assoc($res))
	echo "<tr" . ($row['modpending'] ? ' class="dirty"' : '') . "><td><a href=\"http://musicbrainz.org/album/{$row['gid']}.html\">{$row['name']}</a></td>" .
		"<td>{$row['attributes']}</td>" .
		"<td>{$row['language']}</td><td>{$row['script']}</td>" .
		"<td>{$row['country']}</td><td>{$row['releasedate']}</td><td>{$row['label']}</td><td>{$row['catno']}</td><td>{$row['barcode']}</td><td>" . @$formats[$row['format']] . '</td></tr>';

?>
</table>
<?echo "<p>Generated in " . (time()-$start) . " seconds.";?>
</body>
</html>
