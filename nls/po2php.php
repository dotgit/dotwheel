<?php

if (empty($argv[1])
	or ! file_exists($argv[1])
)
	die('Specify as a parameter an existing .PO file to convert'.PHP_EOL);	

$PLURALFORMS = null;
$TRANSLATIONS = array();

function add2translations($msgctxt, $msgid, $msgid_plural, $msgstr)
{
	global $TRANSLATIONS;

	eval("\$msgid = \"$msgid\";");

	$id = $msgid;

	if (isset($msgctxt))
		$id .= "\f$msgctxt";

	if (is_array($msgstr))
	{
		eval("\$msgid_plural = \"$msgid_plural\";");
		$id .= "\f$msgid_plural";

		foreach ($msgstr as $n=>$str)
		{
			$idn = "$id\f$n";
			$crc = crc32($idn);
			eval("\$str = \"$str\";");

			if (empty($TRANSLATIONS[$crc]))
				$TRANSLATIONS[$crc] = $str;
			elseif (is_array($TRANSLATIONS[$crc]))
				$TRANSLATIONS[$crc][$idn] = $str;
			else
				$TRANSLATIONS[$crc] = array($crc=>$TRANSLATIONS[$crc], $idn=>$str);
		}
	}
	else
	{
		$crc = crc32($id);
		eval("\$msgstr = \"$msgstr\";");

		if (empty($TRANSLATIONS[$crc]))
			$TRANSLATIONS[$crc] = $msgstr;
		elseif (is_array($TRANSLATIONS[$crc]))
			$TRANSLATIONS[$crc][$id] = $msgstr;
		else
			$TRANSLATIONS[$crc] = array($crc=>$TRANSLATIONS[$crc], $id=>$msgstr);
	}
}

$mode = null;

$msgctxt = null;
$msgid = null;
$msgid_plural = null;
$msgstr = null;

$last_str = null;

foreach (file($argv[1]) as $line)
{
	$line = rtrim($line);

	if (isset($line[0]) and $line[0] == '"') // continued multiline
		$last_str .= substr($line, 1, -1);
	else
	{
		switch ($mode)
		{
		case 'msgctxt':
			$msgctxt = $last_str;
			break;
		case 'msgid':
			$msgid = $last_str;
			break;
		case 'msgid_plural':
			$msgid_plural = $last_str;
			break;
		case 'msgstr':
			$msgstr = $last_str;
			break;
		default:
			if (substr($mode, 0, 6) == 'msgstr') // 'msgstr[N]'
			{
				if (is_array($msgstr))
					$msgstr[] = $last_str;
				else
					$msgstr = array($last_str);
			}
		}

		if (strlen($line) == 0) // empty line
		{
			if (isset($msgid))
			{
				add2translations($msgctxt, $msgid, $msgid_plural, $msgstr);
				$mode = null;
				$msgctxt = null;
				$msgid = null;
				$msgid_plural = null;
				$msgstr = null;
			}
		}
		elseif ($line[0] != '#'
			and preg_match('/^([^"]+)"(.*)"$/', $line, $m) // keyword " line "
		)
		{
			// keywords in $m[1]: msgctxt, msgid, msgid_plural, msgstr, msgstr[N]

			$mode = rtrim($m[1]);
			$last_str = $m[2];
		}
	}
}

if (isset($msgid))
	add2translations($msgctxt, $msgid, $msgid_plural, $msgstr);

if (isset($TRANSLATIONS[0]))
{
	if (is_array($TRANSLATIONS[0]))
	{
		$header = $TRANSLATIONS[0][0];
		unset($TRANSLATIONS[0][0]);
	}
	else
	{
		$header = $TRANSLATIONS[0];
		unset($TRANSLATIONS[0]);
	}

	if (preg_match('/Plural-Forms: .*plural=([^;]+)/', $header, $m))
	{
		$PLURALFORMS = str_replace('n', '$n', $m[1]);
	}
}

printf(
	"<?php\n\$PLURALFORMS = %s;\n\$TRANSLATIONS = %s;\n",
	var_export($PLURALFORMS, 1),
	var_export($TRANSLATIONS, 1)
);
