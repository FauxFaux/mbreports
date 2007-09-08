<?
require_once('../database.inc.php');
$res = pg_query('select id,name from language order by name asc');
$a = @$_REQUEST{'str'};
if (!isset($a))
	$a = " * [http://musicbrainz.org/search/textsearch.html?query=lang%3A{{id}}&type=release&adv=on {{id}}: {{name}}]\n";

echo '<form method="post"><textarea name="str" style="height: 5em; width: 100%">' . $a . '</textarea><br/><input type="submit"/></form>';

echo '<textarea style="width: 100%; height: 100%">';
while ($row = pg_fetch_assoc($res))
	echo str_replace(array('{{id}}', '{{name}}'), array($row['id'], $row['name']), $a);
echo '</textarea>';