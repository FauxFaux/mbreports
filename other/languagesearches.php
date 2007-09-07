<?
header("Content-type: text/plain");
require_once('database.inc.php');
$res = pg_query('select id,name from language order by name asc');
while ($row = pg_fetch_assoc($res))
	echo " * [http://musicbrainz.org/search/textsearch.html?query=lang%3A{$row['id']}&type=release&adv=on {$row['id']}: {$row['name']}]\n";