<?
$f = @$_POST{'cue'};

file_put_contents('cueread_' . sha1($f) . '.post', $f);

echo '<form method="post"><textarea style="width: 100%; height: 50em" name="cue">' . htmlentities($f) . '</textarea><p><input type="submit"/></p>';

preg_match_all('/INDEX 01 ([0-9]+:[0-9:]+)/i', $f, $regs);
$regs=$regs[1];

function tosectors($thing)
{
	$thing = explode(':', $thing);
	return (int)(($thing[0] * 60 + $thing[1] + @$thing[2]/100) * 74.4);
}

$regs = array_map('tosectors', $regs);

if (!count($regs))
	die('<p>No tracks?</p>');

$sql = "SELECT DISTINCT album.id,artist.name,album.name as album FROM cdtoc JOIN album_cdtoc ON (cdtoc.id = album_cdtoc.cdtoc) JOIN album ON (album.id = album_cdtoc.album) JOIN artist ON (artist.id = album.artist) WHERE trackcount = " . count($regs) . "\n";
unset($regs[0]);
foreach ($regs as $no => $r)
	$sql .= "\tAND (trackoffset[" . ($no+1) . "] BETWEEN " . (int)($r-1000*$no). " AND " . (int)($r+1000*$no) . ")\n";

$sql .= "\tORDER BY album.name";

require_once('database.inc.php');

$res = pg_query($sql);

if (!pg_num_rows($res))
	die('<p>None found. :(</p>');

echo '<ul>';

while ($row = pg_fetch_assoc($res))
	echo "<li><a href=\"http://musicbrainz.org/show/release/?releaseid={$row['id']}\">{$row['album']}</a> ({$row['name']})</li>\n";

echo '</ul>';
