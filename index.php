<<?='?'?>xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html>
<head>
<title>PONIES as of around <?=@date('l dS \of F Y')?></title>
</head>
<body>
<?
$start = time();

pg_connect('host=192.168.1.4 user=postgres dbname=musicbrainz_db password=poines69');

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

@$page = (int)$_GET{'page'};

if ($page > 0)
	echo '<a href="?page=' . ($page-1) . '">&lt;-- previous page</a> ';

echo '<a href="?page=' . ($page+1) . '">next page --&gt;</a>';

	$rohs = pg_query("
		SELECT DISTINCT 
		SUBSTR(name, 0, STRPOS(name, ' (disc')) AS name
		FROM album 
		WHERE name LIKE '% (disc%' LIMIT 100 OFFSET " . ($page*100)
	);

function check_equal(&$violations, $key, $left, $right)
{
	if ($left[$key] != $right[$key])
		$violations[] = link_for($left) . "'s $key ( " . if_not_missing($left[$key]) . " ) mismatches with " . link_for($right) . ' ( ' . if_not_missing($right[$key]) . ' )';
}

$boxes = $total = 0;

while ($acnames = pg_fetch_row($rohs))
{
	$acname = $acnames[0];
	$res = pg_query("SELECT 
		id,gid,name,artist,attributes,language,script 
		FROM album 
		WHERE name LIKE '" . pg_escape_string($acname) . " (disc %'
	");

	if (!pg_num_rows($res))
	{
		echo "$acname unexpectedly empty. Skipping.<br/>";
		continue;
	}
	$ids = $rawk = array();
	
	while ($row = pg_fetch_assoc($res))
	{
		$rawk[] = $row;
		$ids[] = $row['id'];
	}
		
	$res = pg_query("SELECT 
		album,isocode as country,releasedate,label,catno,barcode,format 
		FROM release 
		JOIN country ON (country.id = release.country) 
		WHERE album IN (" . implode(",", $ids) . ")
	");

	$releasedates = array();
	while ($row = pg_fetch_assoc($res))
	{
		$alb = $row['album'];
		unset($row['album']);
		$releasedates[$alb][] = $row;
	}

	//var_dump($releasedates);

	$violations = array();

	$prev = array_shift($rawk);
	foreach ($rawk as $det)
	{
		$rel1 = @$releasedates[$det['id']];
		$rel2 = @$releasedates[$prev['id']];
		if ($rel1 != $rel2)
			$violations[] = link_for($det) . "'s release-info ( " . reldates_string($rel1) . ") mismatches with " . link_for($prev) . ' ( ' . reldates_string($rel2) . ')';

		foreach (array('artist', 'attributes', 'language', 'script') as $key)
			check_equal($violations, $key, $det, $prev);


		$prev = $det;
	}
	
	$prev = $det = null;
	
	if ($violations)
	{
		$total+=count($violations);
		++$boxes;
		echo "<h3>Mismatches in $acname</h3><ul><li>" . implode("</li><li>", $violations) . '</li></ul>';
	}

}
echo "<p>Generated in " . (time()-$start) . " seconds. $total total, $boxes things hit.</p>";
?>
</body>
</html>
