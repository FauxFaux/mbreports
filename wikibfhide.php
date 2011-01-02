<?
header('Content-type: text/html; ; charset=utf-8');
require_once('database.inc.php');
$idregex = '#id=(\d+)&#';
$id=(int)$_SERVER['QUERY_STRING'];
$fn='/home/faux/mbreports/mbwikilist/MBWikiList' . str_pad($id, 3, '0', STR_PAD_LEFT) . '.html';
for ($i=1; $i<78;++$i)
	echo "<a href=\"?$i\">$i</a>, ";
echo "<a href=\"?\">wiki</a>.";
if (($s = @file($fn)) === FALSE)
{
	echo "<p>No such page</p>";
	echo '<iframe style="width:100%; height: 90%" src="http://wiki.musicbrainz.org/MBWikipediaARs"></iframe>';
	die();
}
preg_match_all($idregex, implode("\n",$s), $regs);
$a = pg_query("select link0 from l_artist_url where link0 in (" . implode(',',$regs[1]) . ") and link_type=10 order by link0");
while ($j = pg_fetch_assoc($a))
	$has[$j['link0']]=true;

foreach($s as $line)
	if (!preg_match($idregex, $line, $reg) || !@$has[$reg[1]])
		echo $line;

