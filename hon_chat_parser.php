<?
$dpath = "/home/jquick/jaredquick.com/tmp";
if (!empty($argv[1]))
	$match_id = $argv[1];
else 
	$match_id = $_GET["match_id"];

//try and download file
if (!empty($match_id)) {
	set_time_limit(0);
	$fp = fopen ("$dpath/$match_id.honreplay", 'w+');//This is the file where we save the information
	$ch = curl_init("http://replaydl.heroesofnewerth.com/replay_dl.php?file=&match_id=$match_id");//Here is the file we are downloading
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
}

//unzip the file
$cmd = "unzip $dpath/$match_id.honreplay -d $dpath/$match_id";
exec($cmd);

//check to make sure we have file
echo "<LINK href='hon_chat_parser.css' rel='stylesheet' type='text/css'>";
if (!file_exists("$dpath/$match_id/replayinfo")) {
	echo "<h1 style='color:red'>FILE NOT FOUND!!... invalid id or you need to wait longer for it to be posted</h1>";
} else {

	//open replay info
	$fname = "$dpath/$match_id/replayinfo";
	$fp = fopen($fname, "rb");
	$data = fread($fp, filesize($fname));
	$info = json_decode(json_encode((array) simplexml_load_string($data)),1);
	fclose($fp);
	foreach ($info["player"] as $p) {
		$p = $p["@attributes"];
		$players[$p["name"]]["accountid"] = $p["accountid"];
		$players[$p["name"]]["team"] = $p["team"];
		$players[$p["name"]]["teamindex"] = $p["teamindex"];
		$players[$p["name"]]["heroname"] = $p["heroname"];
		$players[$p["name"]]["heronicon"] = $p["heroicon"];
	}

	//open replay data
	$fname = "$dpath/$match_id/replaydata";
	$fp = fopen($fname, "rb");
	$data = fread($fp, filesize($fname));

	//Match info
	preg_match('/\xFF\x33\xFF([^\xFF\x3F]*)\xFF\x33\x30\xFF/', $data, $match);

	//players
	preg_match('/\xFF\x31\x30\xFF([^\xFF\x3F]*)\xFF\x31\x31\xFF/', $data, $test);
	$players[$test[1]]["id"] = 0;

	preg_match('/\xFF\x31\x33\xFF([^\xFF\x3F]*)\xFF\x31\x34\xFF/', $data, $test);
	$players[$test[1]]["id"] = 1;

	preg_match('/\xFF\x31\x36\xFF([^\xFF\x3F]*)\xFF\x31\x37\xFF/', $data, $test);
	$players[$test[1]]["id"] = 2;

	preg_match('/\xFF\x31\x39\xFF([^\xFF\x3F]*)\xFF\x32\xFF/', $data, $test);
	$players[$test[1]]["id"] = 3;

	preg_match('/\xFF\x32\x32\xFF([^\xFF\x3F]*)\xFF\x32\x33\xFF/', $data, $test);
	$players[$test[1]]["id"] = 4;

	preg_match('/\xFF\x32\x35\xFF([^\xFF\x3F]*)\xFF\x32\x36\xFF/', $data, $test);
	$players[$test[1]]["id"] = 5;

	preg_match('/\xFF\x32\x38\xFF([^\xFF\x3F]*)\xFF\x32\x39\xFF/', $data, $test);
	$players[$test[1]]["id"] = 6;

	preg_match('/\xFF\x33\x31\xFF([^\xFF\x3F]*)\xFF\x33\x32\xFF/', $data, $test);
	$players[$test[1]]["id"] = 7;

	preg_match('/\xFF\x33\x34\xFF([^\xFF\x3F]*)\xFF\x33\x35\xFF/', $data, $test);
	$players[$test[1]]["id"] = 8;

	preg_match('/\xFF\x37\xFF([^\xFF\x3F]*)\xFF\x38\xFF/', $data, $test);
	$players[$test[1]]["id"] = 9;

	foreach ($players as $k => $v) {
		$tmp_id = $v["id"] + 1;
		if ($tmp_id == 10)
			$tmp_id = 0;

		//handle spectators
		if ($v['team'] == 0)
			$tmp_id = (abs($v["id"]) + 10);

		$tmp[$tmp_id] = $v;
		$tmp[$tmp_id]["name"] = $k;
	}
	$players = $tmp;

	//find both team hex
	$t1 = null;
	$t2 = null;
	foreach ($players as $k => $v) {
		if ($v["team"] == 1)
			$t1 = "\\x0" . $k;
		if ($v["team"] == 2)
			$t2 = "\\x0" . $k;
	}
	//chat log
	$teamstart = "[$t1$t2]\\x00\\x00\\x00[^\\x00]\\x00\\x00\\x00\\x03(.)\\x00\\x00\\x00";
	$allstart = '\xFF\xFF\xFF\xFF[^\xFF]\x00\x00\x00\x02(.)\x00\x00\x00';
	$end = '\x00';

	preg_match_all("/$allstart([^\\x00]*)$end|$teamstart([^\\x00]*)$end/", $data, $matches);
	fclose($fp);

	//open replay info
	$fname = "$dpath/$match_id/replayinfo";
	$fp = fopen($fname, "rb");
	$data = fread($fp, filesize($fname));
	$info = json_decode(json_encode((array) simplexml_load_string($data)),1);
	fclose($fp);

	//delete file
	$cmd = "rm $dpath/* -rf";
	exec($cmd);

	//build messages
	$chat[] = null;
	$tmp[] = null;
	foreach ($matches[2] as $id => $row) {
		if (!empty($row)) {
			$tmp = array(
				$row,
				$players[dechex(bin2hex($matches[1][$id]))],
				'All'
			);
			//echo $id . " - " . $row .  "\n";
		} else {
			$tmp = array(
				$matches[4][$id],
				$players[dechex(bin2hex($matches[3][$id]))],
				'Team'
			);
			//echo $id . " - " . $matches[4][$id] . "\n";
		}
		$chat[$id] = $tmp;
	}

	echo "<h2>{$match[1]}</h2>";
	//match table
	$sen[] = null;
	$hel[] = null;
	foreach ($players as $p) {
		if ($p['team'] == 1)
			$sen[$p['teamindex']] = $p;
		else if ($p['team'] == 2)
			$hel[$p['teamindex']] = $p;
	}
	echo "<table border=1>";
	echo "<th width=150><span class='team1'>Legion</span></th>";
	echo "<th width=150><span class='team2'>Hellbourne</span></th>";
	for ($i = 0; $i < 5; $i++) {
		echo "<tr><td><span class='color1{$sen[$i]['teamindex']}'>{$sen[$i]['name']}</span></td>";
		echo "<td><span class='color2{$hel[$i]['teamindex']}'>{$hel[$i]['name']}</span></td></tr>";
	}
	echo "</table><br />";

	//output messages
	foreach ($chat as $msg) {
		// sometimes we get junk remove empty lines
		$msg[0] = trim($msg[0]);
		if (!empty($msg[0])) {
			if ($msg[2] == 'All')
				echo "<span class='all'>[{$msg[2]}]</span>";
			else
				echo "<span class='team{$msg[1]['team']}'>[{$msg[2]}]</span>";
			echo "<span class='color{$msg[1]['team']}{$msg[1]['teamindex']}'>{$msg[1]['name']}</span>: $msg[0]<br />";
		}
	}
}
?>
