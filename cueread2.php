<?

/*
 * This depends on (aggregate stolen from http://www.postgresql.org/docs/8.0/static/xaggr.html):

CREATE AGGREGATE array_accum (
	sfunc = array_append,
	basetype = anyelement,
	stype = anyarray,
	initcond = '{}'
);

BEGIN;
create temporary view album_tracklen as select albumjoin.album,albumjoin.sequence,length from track join albumjoin on (track.id = albumjoin.track) order by albumjoin.sequence;
create temporary view album_trackponies as select album,array_accum(length) as tracklist from album_tracklen group by album;
drop table if exists album_tracklist cascade;
create table album_tracklist as select album,tracklist,array_upper(tracklist, 1) as track_count from album_trackponies;


ALTER TABLE album_tracklist
  ADD CONSTRAINT album_tracklist_pkey PRIMARY KEY(album);


-- DROP INDEX atltc;

CREATE INDEX atltc
  ON album_tracklist
  (track_count);

COMMIT;
 * This could take of the order of 20 minutes to run.
 */

require_once('database.inc.php');

my_title();

function string_time_to_ms($thing)
{
	if ($thing == '?:??')
		return null;
	$thing = explode(':', $thing);
	return (int)(($thing[0] * 60 + $thing[1] + @$thing[2]/100) * 1000);
}

$times_ms = array();

$tns = array_keys($_GET);
$track_count = @max($tns);

assert($track_count <= 99);

if (@$track_count)
	for ($i = 1; $i <= $track_count; ++$i)
	{
		$t = $_GET{$i} or $_GET{"0$i"};
		$times_ms[$i-1] = is_numeric($t) ? (int)($t) : null;
	}
else
{
	echo '<p>Dump a set of times (ie. n:nn(:nn)) into the box, most crap\'ll be ignored. Use ?:?? to indicate a missing/unknown track.</p>';
	echo '<p>See also: <a href="searchbytimes.py">searchbytimes.py</a>, the PicardQt plugin.</a></p>';
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
5:07" : htmlentities($f)) . '</textarea><p><input type="submit"/></p></form>';

	// Grab the times (or literal ?:??) from the input string.
	preg_match_all('/(?<!INDEX 00 )((?:\?:\?\?)|(?:[0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?))/i', $f, $regs);

	// Take only the matches.
	$times_string = $regs[1];


	// Convert the string times to milliseconds.
	$times_ms = array_map('string_time_to_ms', $times_string);

}

if (!count($times_ms))
	die('<p>No tracks?</p>');

echo '<iframe name="hiddeniframe" style="display: none"></iframe>';
echo '<p>' . count($times_ms) . ' tracks.</p>';


// Basis of our sql statement:
$sql = "SELECT DISTINCT album.id,artist.name,album.name as album,album.gid as gid " .
	"FROM album_tracklist " .
	"JOIN album ON (album.id = album_tracklist.album) " .
	"JOIN artist ON (artist.id = album.artist) " .
	"WHERE track_count = " . count($times_ms) . "\n";


$wiggle_room = 5000; // 5000ms = 5 seconds.

foreach ($times_ms as $track_number_zero => $length)
	// If the $length is exactly null it means we're ignoring this track.
	if ($length !== null)
		$sql .= "\tAND (tracklist[" . ($track_number_zero+1) . "] BETWEEN " . ($length - $wiggle_room). " AND " . ($length + $wiggle_room) . ")\n";

$sql .= "\tORDER BY album.name LIMIT 20";

$res = pg_query($sql);

if (!pg_num_rows($res))
	die('<p>None found. :(</p>');

echo '<ul>';

// Dump the results to the browser.
while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://127.0.0.1:' . (isset($_GET{'tport'}) ? $_GET{'tport'} : '8000') . '/openalbum?id=' . $row['gid'] . '" target="hiddeniframe"><img src="http://musicbrainz.org/images/mblookup-tagger.png"/></a> <a href="http://musicbrainz.org/show/release/?releaseid=' . "{$row['id']}\">{$row['album']}</a> ({$row['name']})</li>\n";

echo '</ul>';
