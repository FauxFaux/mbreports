<<?='?'?>xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html>
<head>
<title>List of reports</title>
</head>
<body>
<table><tr><th>Link</th><th>Desc.</th></tr>
<?

require_once('database.inc.php');

$d = dir('.');

while (false !== ($entry = $d->read()))
	if (preg_match('/^[^\\.]+\\.php$/', $entry))
	   	echo @"<tr><td><a href=\"$entry\">$entry</a></td><td>{$desc[$entry]}.</td></tr>\n";
?>
</table>
</body>
</html>
