<?

/*
 * This depends on (aggregate stolen from http://www.postgresql.org/docs/8.0/static/xaggr.html):

CREATE AGGREGATE array_accum (
	sfunc = array_append,
	basetype = anyelement,
	stype = anyarray,
	initcond = '{}'
);

create temporary view album_tracklen as select albumjoin.album,albumjoin.sequence,length from track join albumjoin on (track.id = albumjoin.track) order by albumjoin.sequence;
create temporary view album_trackponies as select album,array_accum(length) as tracklist from album_tracklen group by album;
create table album_tracklist as select album,tracklist,array_upper(tracklist, 1) as track_count from album_trackponies

 * This could take of the order of 20 minutes to run.
 */

require_once('database.inc.php');

my_title();

echo '<p>Dump a set of times (ie. n:nn(:nn)) into the box, most crap\'ll be ignored. Use ?:?? to indicate a missing/unknown track.</p>';

$f = @$_REQUEST{'cue'};

file_put_contents('cueread_' . sha1($f) . '.post', $f);

echo '<form method="post"><textarea style="width: 100%; height: 50em" name="cue">' . (!$f ? "5:29
4:30
5:04
4:29
6:15
5:15
5:04
5:15
?:??
?:??
?:??
?:??
?:??
5:00
5:07" : htmlentities($f)) . '</textarea><p><input type="submit"/></p>';

//(?<!INDEX 00 )
preg_match_all('/((?:\?:\?\?)|(?:[0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?))/i', $f, $regs);
$regs=$regs[1];

function tosectors($thing)
{
	if ($thing == '?:??')
		return null;
	$thing = explode(':', $thing);
	return (int)(($thing[0] * 60 + $thing[1] + @$thing[2]/100) * 1000);
}

$regs = array_map('tosectors', $regs);

if (!count($regs))
	die('<p>No tracks?</p>');

echo '<p>' . count($regs) . ' tracks.</p>';

$sql = "SELECT DISTINCT album.id,artist.name,album.name as album FROM album_tracklist JOIN album ON (album.id = album_tracklist.album) JOIN artist ON (artist.id = album.artist) WHERE track_count = " . count($regs) . "\n";
//$sql = "SELECT DISTINCT album_tracklist.album FROM album_tracklist WHERE track_count = " . count($regs) . "\n";

foreach ($regs as $no => $r)
	if ($r !== null)
		$sql .= "\tAND (tracklist[" . ($no+1) . "] BETWEEN " . (int)($r-5000). " AND " . (int)($r+5000) . ")\n";

$sql .= "\tORDER BY album.name LIMIT 20";

//die($sql);

$res = pg_query($sql);

if (!pg_num_rows($res))
	die('<p>None found. :(</p>');

echo '<ul>';

while ($row = pg_fetch_assoc($res))
	echo "<li><a href=\"http://musicbrainz.org/show/release/?releaseid={$row['id']}\">{$row['album']}</a> ({$row['name']})</li>\n";

echo '</ul>';
