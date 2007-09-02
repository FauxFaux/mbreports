<form method="post"><textarea name="def" style="width: 100%; height: 40em">
<?

if (isset($_POST{'def'}))
	$def = $_POST{'def'};
else
	$def = '1.	 	City Streets (Radio Edit)
Artist(s): G-SPOTT 	3:07
2.	 	Boy I Believe (Club Mix)
Artist(s): Dan Marciano 	5:44
3.	 	A Touch Too Much (Club Mix)
Artist(s): Phonjaxx 	6:50
4.	 	Revolution On The Dance Floor (Radio Edit)
Artist(s): Alexander Perls vs. Thomas Falke 	2:58
5.	 	Save My Soul (David Guetta Mix)
Artist(s): Logic 	4:33
6.	 	Let It Go (Club Mix)
Artist(s): Phunk Foundation 	7:00
7.	 	Closer (Phunk Investigation Radio Edit)
Artist(s): The Pull 	3:17
8.	 	Turn The Lights Off (Club Mix)
Artist(s): Egohead Deluxe 	6:46
9.	 	Midnight Sun (Radio Edit)
Artist(s): Roy Gates 	3:08
10.	 	My Shooter (Club Mix)
Artist(s): Groove Cutter 	7:19
11.	 	Love Electric (Club Mix Radio Edit)
Artist(s): The Circ 	3:38
12.	 	Missile Test (Club Mix)
Artist(s): Menace & Adam 	8:32';

echo $def;
?>
</textarea><p><input type="submit"/></p></form><pre>
<?

preg_match_all('/([0-9]+)\.[\t ]+(.*?)[\t ]*\r?\nArtist(?:\(s\))?:[\t ]+(.*?)[\t ]+([0-9]+:[0-9]+)[\t ]*\r?(?:\n|$)/s', $def, $regs);

foreach ($regs[1] as $idx => $trackno)
	echo "{$trackno}. {$regs[3][$idx]}\t-\t{$regs[2][$idx]}\t{$regs[4][$idx]}\n";

?>
</pre>