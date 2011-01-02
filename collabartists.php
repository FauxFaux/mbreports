<style type="text/css">
.ambigious { font-style: italic; }
.missing { border: 1px solid #999; }
.correct { color: #ccc; text-decoration:line-through; }
</style>

<?
require_once('database.inc.php');
my_title();
?>
<p class="ambigious">italics: more than one artist by this name</p>
<p class="missing">box: no artist by this name</p>
<p><a href="">link an artist</a> to it's collaboration</p>
<p class="correct">Artist is already correctly linked in</p>
<?
$ands="(&|and|with|y|und|et|og|och|und|med)";
$prefix=pg_escape_string($_GET{'prefix'});
$page = (int)$_GET{'page'};
$res = pg_query("select artist.name,gid,artist.id from artist where name ilike '$prefix%' and name ~ ' $ands ' order by name limit 1000 offset " . $page*1000);

$idcache = array();
$perfect = 0;
echo '<p>';
for ($c = ord('a'); $c <= ord('z'); ++$c)
	echo '<a href="collabartists.php?prefix=' . chr($c) . '">' . chr($c) . '</a> ';
echo '</p>';

function artistid_nocache($name)
{
	// already escaped
	$res = pg_query("select id from artist where name='$name' limit 2");
	if (pg_num_rows($res) == 0)
		return 0;
	if (pg_num_rows($res) > 1)
		return -1;
	$row = pg_fetch_assoc($res);
	return $row['id'];
}

function artistid($name)
{
	global $idcache;
	if (isset($idcache[$name]))
		return $idcache[$name];
	return $idcache[$name] = artistid_nocache($name);
}

function has_link($from, $to)
{
	$r = pg_fetch_assoc(pg_query("select count(*) as cnt from l_artist_artist where link0=$from and link1=$to and link_type = 11")); // 11 == collab
	return $r['cnt'];
}
echo "<ol>";

while ($row = pg_fetch_assoc($res))
{
	$n = $row['name'];
	if (preg_match('/^((?:[^,]+?, )*[^,]+),? ' . $ands . ' (.+)$/', $n, $regs))
	{
		$noms = explode(', ', $regs[1]);
		$noms[] = $regs[3];
		$noms = array_map('pg_escape_string', array_map('trim', $noms));
		$s = array();
		$correct = 0;
		foreach ($noms as $nom)
		{
			$aid = artistid($nom);
			if ($aid == 0)
				$s[] = "<span class=\"missing nom\">$nom</span>";
			else if ($aid == -1)
				$s[] = "<span class=\"ambigious nom\">$nom</span>";
			else if (has_link($aid, $row['id']))
			{
				++$correct;
				$s[] = "<span class=\"correct\">$nom</span>";
			}
			else
				$s[] = "<a href=\"http://musicbrainz.org/edit/relationship/add.html?link0=artist=$aid&amp;link1=artist={$row['id']}&amp;linktypeid=11\">$nom</a>";
		}
		if ($correct != count($noms))
		{
			echo '<li>';
			for ($i=0;$i<count($s)-2;++$i)
				echo $s[$i] . ', ';
			if ($i < count($s)-1)
				echo $s[$i++] . ' ' . "<a href=\"http://musicbrainz.org/show/artist?artistid={$row['id']}\">{$regs[2]}</a> ";
			echo $s[$i];
			echo '</li>';
		}
		else
			++$perfect;
	}
	else
	{
		echo "fail: <a href=\"http://musicbrainz.org/artist/{$row['gid']}.html\">$n</a><br/>";
		continue;
	}
}
echo "</ol>";
if (pg_num_rows($res) == 1000)
	echo "<p>...and probably <a href=\"collabartists.php?prefix=$prefix&amp;page=" . ($page+1) . "\">more</a>.</p>";

?>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
