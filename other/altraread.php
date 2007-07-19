<h1>What I wildly guess are duplicate releases</h1>
<ol>
<?
$arr = array();
foreach (file('altraread.tsv') as $line)
	list ($l, $r) = explode("\t", trim($line)) and $arr[$l][$r] = true and $arr[$r][$l] = true;

/*
$arr2 = array();

foreach ($arr as $id => $children)
	foreach ($children as $cid => $ignore)
		if (count($arr[$cid]) != 1)
		{
			$arr2[$id] = $children;
			break;
		}

var_dump($arr2);
*/

ksort($arr);

function show($id)
{
	return "<a href=\"http://musicbrainz.org/show/release/?releaseid=$id\">$id</a>\n";
}

function show_pair($left, $right)
{
	static $shown = array();
	if (@$shown["$left $right"] || @$shown["$right $left"])
		return;
	$shown["$left $right"] = true;
	echo '<li>' . show($left) . ' looks like ' . show($right) . '</li>';;
}

foreach ($arr as $id => $children)
{
	foreach ($children as $cid => $ignore)
		 show_pair($id, $cid);
}


?>
</ol>