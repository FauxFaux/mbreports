<?
require_once('database.inc.php');
my_title();

?><ul><?

$res = pg_query("select * from release left join album_amazon_asin on release.album=album_amazon_asin.album where barcode != '' and asin=''");

while ($row = pg_fetch_assoc($res))
	echo '<li><a href="http://musicbrainz.org/show/release/?releaseid=' . $row['album'] . '">' . $row['barcode'] . '</a></li>' . "\n";

?>
</ul>
<?echo "<p>Generated in " . (time()-$start) . " seconds.</p>";?>
</body>
</html>
