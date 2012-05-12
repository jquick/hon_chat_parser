<?
//**************SETUP*****************
$dpath = "/home/jquick/jaredquick.com/tmp";
$match_id = $argv[1] ?: $_GET['match_id'];
echo "<LINK href='hon_chat_parser.css' rel='stylesheet' type='text/css'>";
//***********************************

//**************RUN******************
if (!empty($match_id))
	parse($dpath, $match_id);
//***********************************

function parse ($dpath, $match_id) {
	//vars
	$players = null;
	$start = null;
	$output = null;

	//see if we have the file
	if (!empty($match_id) && !file_exists("{$dpath}/{$match_id}.honreplay"))
		download_replay($dpath,$match_id);

	//download replay info
	$ginfo = get_replay_info($dpath,$match_id, $players);
	$players = $ginfo[0];
	$ginfo = $ginfo[1];

	//open replay data
	$fname = "$dpath/$match_id/replaydata";
	$fp = fopen($fname, "rb");
	$data = fread($fp, filesize($fname));

	//grab players
	$players = parse_players($data, $players, $ginfo);

	//find event hex key
	preg_match('/\x00\x00[^\x00][^\x00](.)\x00\xFC\xFF\xFF\xFF[^\x00][^\x00][^\x00]\x00\x00\x00/', $data, $key);
	$key = bin2hex($key[1]);
	//echo "key - $key<br />";

	//get start time
	preg_match("/\\x0A\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x0B[^\\x$key]*[\\x$key]\\x00[^\\x00]([^\\xFF][^\\xFF])/", $data, $start);
	$start = hexdec(bin2hex(strrev($start[1])));

	//grab all the events
	//check for dupe hex key
	if (preg_match("/\\x00\\xFF\\xFF\\x$key\\x00[^\\x00]/", $data, $pos, PREG_OFFSET_CAPTURE)) {
		$key2 = dechex((hexdec($key)+1));
		//echo "we have dupe key trying to fix - " . $key2;
		rewind($fp);
		$data = fread($fp, $pos[0][1]);
		$data2 = fread($fp, filesize($fname));

		$events = preg_split("/([\\x$key]\\x00[^\\x00][^\\xFF][^\\x00])/", $data, 0, PREG_SPLIT_DELIM_CAPTURE);
		$events2 = preg_split("/([\\x$key2]\\x00[^\\x00][^\\xFF][^\\x00])/", $data2, 0, PREG_SPLIT_DELIM_CAPTURE);
		array_shift($events2);
		$events = array_merge($events, $events2);
	} else {
		$events = preg_split("/([\\x$key]\\x00[^\\x00][^\\xFF][^\\x00])/", $data, 0, PREG_SPLIT_DELIM_CAPTURE);
		fclose($fp);
	}
	$data = null;
	$data2= null;

	//search events
	for ($i=0; $i < count($events); $i=$i+2) {
	//	echo bin2hex($events[$i+1]) . " - " . bin2hex($events[$i]) . "<br />";
		//echo bin2hex($events[$i+1]) . " ";
		//teamchat
		$teamstart = "[^\\xFF]\\x00\\x00\\x00[^\\xFF]\\x00\\x00\\x00\\x03(.)\\x00\\x00\\x00";
		preg_match("/$teamstart([^\\x00]*)\\x00/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'type' => 'Team',
				'user' => hexdec(bin2hex($m[1])),
				'msg' => $m[2]
			);
		}

		//allchat
		$allstart = '\xFF\xFF\xFF\xFF[^\xFF]\x00\x00\x00\x02(.)\x00\x00\x00';
		$end = "\\x00";
		preg_match("/$allstart([^\\x00]*)$end/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'type' => 'All',
				'user' => hexdec(bin2hex($m[1])),
				'msg' => $m[2]
			);
		}

		//emote
		$estart = '\xFF\xFF\xFF\xFF[^\xFF]\x00\x00\x00\x53(.)\x00\x00\x00';
		$end = "\\x00";
		preg_match("/$estart([^\\x00]*)$end/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'type' => 'Emote',
				'user' => hexdec(bin2hex($m[1])),
				'msg' => $m[2]
			);
		}

	}

	//output
	echo "<h2>{$ginfo['gamename']}</h2><a href='http://replaydl.heroesofnewerth.com/replay_dl.php?file=&match_id={$match_id}'>Download</a>";
	echo " {$ginfo['date']} {$ginfo['time']} - ";
	echo (gmdate('i:s', $ginfo['matchlength']/1000)) . "min";

	print_match_table($players);

	//output messages
	foreach ($output as $msg) {
		// sometimes we get junk remove empty lines
		$msg['msg'] = trim($msg['msg']);
		if (!empty($msg['msg'])) {
			//attempt timestamp
			echo "[" . (gmdate('i:s',($msg['time']-$start)/7.816)). "]";
			if ($msg['type'] != 'Team')
				echo "<span class='{$msg['type']}'>[{$msg['type']}]</span>";
			else
				echo "<span class='team". $players[$msg['user']]['team'] . "'>[{$msg['type']}]</span>";

			echo "<span class='color" . $players[$msg['user']]['team'] . $players[$msg['user']]['teamindex'] . "'>";
			echo $players[$msg['user']]['name'] . "</span>: {$msg['msg']}<br />";
		}
	}
}

function print_match_table ($players) {
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
	echo "<th width=200><span class='team1'>Legion</span></th>";
	echo "<th width=200><span class='team2'>Hellbourne</span></th>";
	for ($i = 0; $i < 5; $i++) {
		echo "<tr><td><span class='color1{$sen[$i]['teamindex']}'>{$sen[$i]['name']}</span>(";
		echo substr($sen[$i]['heroname'],5) . ")</td>";
		echo "<td><span class='color2{$hel[$i]['teamindex']}'>{$hel[$i]['name']}</span>(";
		echo substr($hel[$i]['heroname'],5) . ")</td></tr>";
	}
	echo "</table><br />";
}

function parse_players ($e, $players, $ginfo) {
	preg_match('/\xFF\x31\x30\xFF([^\xFF\x3F]*)\xFF\x31\x31\xFF/', $e, $p[]);
	preg_match('/\xFF\x31\x33\xFF([^\xFF\x3F]*)\xFF\x31\x34\xFF/', $e, $p[]);
	preg_match('/\xFF\x31\x36\xFF([^\xFF\x3F]*)\xFF\x31\x37\xFF/', $e, $p[]);
	preg_match('/\xFF\x31\x39\xFF([^\xFF\x3F]*)\xFF\x32\xFF/', $e, $p[]);
	preg_match('/\xFF\x32\x32\xFF([^\xFF\x3F]*)\xFF\x32\x33\xFF/', $e, $p[]);
	preg_match('/\xFF\x32\x35\xFF([^\xFF\x3F]*)\xFF\x32\x36\xFF/', $e, $p[]);
	preg_match('/\xFF\x32\x38\xFF([^\xFF\x3F]*)\xFF\x32\x39\xFF/', $e, $p[]);
	preg_match('/\xFF\x33\x31\xFF([^\xFF\x3F]*)\xFF\x33\x32\xFF/', $e, $p[]);
	preg_match('/\xFF\x33\x34\xFF([^\xFF\x3F]*)\xFF\x33\x35\xFF/', $e, $p[]);
	preg_match('/\xFF\x37\xFF([^\xFF\x3F]*)\xFF\x38\xFF/', $e, $p[]);
	//fix for custom game
	if (!preg_match('/TMM Match/', $ginfo['gamename']))
		preg_match('/\xFF\x00\x30\xFF([^\xFF\x3F]*)\xFF\x31\xFF/', $e, $p[9]);
	for ($i = 0; $i < count($p); $i++)
		$players[$p[$i][1]]["id"] = $i;
	//fix players
	foreach ($players as $k => $v) {
//		echo $k . " - " . $tmp_id . "<br />";
		if($p[9][1] == 'Spectators')
			$tmp_id = $v["id"];
		else
			$tmp_id = $v["id"] +1;
		if ($tmp_id == 10)
			$tmp_id = 0;

		//handle spectators
		if ($v['team'] == 0)
			$tmp_id = (abs($v["id"]) + 10);

		$tmp[$tmp_id] = $v;
		$tmp[$tmp_id]["name"] = $k;
	}
	$players = $tmp;
	return $players;
}

function download_replay ($dpath, $match_id) {
	//try and download file
	set_time_limit(0);
	$fp = fopen ("$dpath/$match_id.honreplay", 'w+');
	$ch = curl_init("http://replaydl.heroesofnewerth.com/replay_dl.php?file=&match_id=$match_id");
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	//unzip the file
	$cmd = "unzip $dpath/$match_id.honreplay -d $dpath/$match_id";
	exec(escapeshellcmd($cmd));
	//check to make sure we have file
	if (!file_exists("$dpath/$match_id/replayinfo")) {
		echo "<h1 style='color:red'>FILE NOT FOUND!!... invalid id or you need to wait longer for it to be posted</h1>";
	}
}

function get_replay_info ($dpath, $match_id, $players) {
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
	return array($players, $info['@attributes']);
}
?>
