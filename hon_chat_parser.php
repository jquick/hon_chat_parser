<?
//**************SETUP*****************
$dpath = "/home/jquick/jaredquick.com/tmp";
$match_id = $argv[1] ?: trim($_GET['match_id']);
$show_kills = $argv[2] ?: $_GET['show_kills'];
echo "<LINK href='hon_chat_parser.css' rel='stylesheet' type='text/css'>";
echo "
<form method='get' action='hon_chat_parser_test.php'>
<table><tr><td>
Match ID:<input type='text' value='$match_id' size='12' maxlength='12' name='match_id'></td><td>
Show Kills:<input type='checkbox' name='show_kills'" . ($show_kills == 1 ? 'checked' : null) . "
value='1' /></td><td><input type='submit' value='Go!' /></td></table></form>";
//***********************************

//**************RUN******************
if (!empty($match_id)) {
	include 'hon_chat_parser_heroes.php';
	parse($dpath, $match_id, $show_kills);
}
//***********************************

function parse ($dpath, $match_id, $show_kills) {
	//vars
	$players = null;
	$start = null;
	$output = null;
	$kill_count = array();
	$debug = $_GET['debug'];

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
	//update hero names
	$players = update_heroes($players);


	//find event hex key
	preg_match('/\x00\x00[^\x00][^\x00](.)\x00\xFC\xFF\xFF\xFF[^\x00][^\x00][^\x00][\x00\x01]\x00\x00/', $data, $key);
	$key = bin2hex($key[1]);
	echo ($debug == 1 ? "key - $key<br />" : null);

	//get start time
	preg_match("/\\x0A\\x00\\x00\\x00\\x00\\x00\\x00\\x00\\x0B[^\\x$key]*[\\x$key]\\x00[^\\x00]([^\\xFF][^\\xFF])/", $data, $start);
	$tstart = hexdec(bin2hex(strrev($start[1])));

	//grab all the events
	//check for dupe hex key
	if (preg_match("/\\x00\\xFF\\xFF\\x$key\\x00[^\\x00]/", $data, $pos, PREG_OFFSET_CAPTURE)) {
		$data = null;
		$key2 = dechex((hexdec($key)+1));
		echo ($debug == 1 ?"we have dupe key trying to fix - " . $key2 : null);
		rewind($fp);
		$data = fread($fp, $pos[0][1]);
		$data2 = fread($fp, filesize($fname));

		$events = preg_split("/([\\x$key]\\x00[^\\x00][^\\xFF][^\\x00])\\x00\\x00/", $data, 0, PREG_SPLIT_DELIM_CAPTURE);
		$data = null;
		$events2 = preg_split("/([\\x$key2]\\x00[^\\x00][^\\xFF][^\\x00])\\x00\\x00/", $data2, 0, PREG_SPLIT_DELIM_CAPTURE);
		$data2= null;
		array_shift($events2);
		$events = array_merge($events, $events2);
	} else {
		$events = preg_split("/([\\x$key]\\x00[^\\x00][^\\xFF][^\\x00])\\x00\\x00/", $data, 0, PREG_SPLIT_DELIM_CAPTURE);
		$data = null;
	}
	fclose($fp);

	//search events
	for ($i=0; $i < count($events); $i=$i+2) {
		//echo bin2hex($events[$i+1]) . " - " . bin2hex($events[$i]) . "<br />";
		//teamchat
		$start = "[^\\xFF]\\x00\\x00\\x00[^\\xFF]\\x00\\x00\\x00\\x03(.)\\x00\\x00\\x00";
		preg_match("/$start([^\\x00]*)\\x00/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'time2' => bin2hex($events[$i+1]),
				'type' => 'Team',
				'user' => hexdec(bin2hex($m[1])),
				'msg' => $m[2]
			);
		}

		//allchat
		$start = '\xFF\xFF\xFF\xFF[^\xFF]\x00\x00\x00\x02(.)\x00\x00\x00';
		$end = "\\x00";
		preg_match("/$start([^\\x00]*)$end/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'time2' => bin2hex($events[$i+1]),
				'type' => 'All',
				'user' => hexdec(bin2hex($m[1])),
				'msg' => $m[2]
			);
		}

		//emote
		$start = '\xFF\xFF\xFF\xFF[^\xFF]\x00\x00\x00\x53(.)\x00\x00\x00';
		$end = "\\x00";
		preg_match("/$start([^\\x00]*)$end/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'time2' => bin2hex($events[$i+1]),
				'type' => 'Emote',
				'user' => hexdec(bin2hex($m[1])),
				'msg' => $m[2]
			);
		}

		//kills
		$start = '\x00\xFF\xFF\xFF\xFF[^\xFF\x01]\x00\x00\x00[^\xFF]{0,6}([\x60\x63])([\x00-\x09])\x00\x00\x00([\x00-\x09])\x00\x00';
		$end = "\\x00";
		preg_match("/$start$end/", $events[$i], $m);
		if (!empty($m[0])) {
			$output[] = array(
				'time' => hexdec(bin2hex(strrev(substr($events[$i+1],3,2)))),
				'time2' => bin2hex($events[$i+1]),
				'type' => 'Kill',
				'code' => bin2hex($m[1]),
				'user' => hexdec(bin2hex($m[2])),
				'user2' => hexdec(bin2hex($m[3])),
			);
		}
	}

	//output
	echo "<h2>{$ginfo['gamename']}</h2>";
	echo "<a href='http://replaydl.heroesofnewerth.com/replay_dl.php?file=&match_id={$match_id}'>Download</a>";
	echo " {$ginfo['date']} {$ginfo['time']} ";
	echo (gmdate('H:i:s', $ginfo['matchlength']/1000));
	echo " Winner: <b>" . $ginfo['winner'] . "</b>";

	print_match_table($players, $match_id, $debug);

	//output messages
	foreach ($output as $msg) {
		echo ($debug == 1 ? "[" . $msg['time2'] . "]" : null);
		$msg['time'] = (($msg['time']-$tstart)/7.816);

		if ($msg['type'] == 'Kill' && $show_kills == 1) {
			echo "[" . gmdate('i:s', $msg['time']) . "]";
			echo "<span class='{$msg['type']}'>[{$msg['type']}]</span>";
			echo "<span class='kill'><span class='color" . $players[$msg['user']]['team'] . $players[$msg['user']]['teamindex'] . "'>";
			echo $players[$msg['user']]['name'] . "</span> cridered ";
			echo "<span class='color" . $players[$msg['user2']]['team'] . $players[$msg['user2']]['teamindex'] . "'>";
			echo $players[$msg['user2']]['name'] . "</span>";
			//echo " - " . $msg['code'];
			//check kills
			$kill_count = check_kill_count($msg, $kill_count);
			echo "</span><br />";
		} else if (isset($msg['msg']) && !empty($msg['msg']) && $msg['type'] != 'Kill') {
			$msg['msg'] = htmlentities($msg['msg']);
			echo "[" . gmdate('i:s', $msg['time']) . "]";
			if ($msg['type'] != 'Team')
				echo "<span class='{$msg['type']}'>[{$msg['type']}]</span>";
			else
				echo "<span class='team". $players[$msg['user']]['team'] . "'>[{$msg['type']}]</span>";
			echo "<span class='color" . $players[$msg['user']]['team'] . $players[$msg['user']]['teamindex'] . "'>";
			echo $players[$msg['user']]['name'] . "</span>: {$msg['msg']}<br />";
		}
	}
}

function check_kill_count($msg, $kill_count) {
	if (empty($kill_count)) {
		echo " (firstblood)";
	}

	$kill_count[$msg['user2']]['kills'] = 0;
	$kill_count[$msg['user']]['kills'] += 1;
	//echo "[" . $msg['time'] . " - " . $kill_count[$msg['user']]['last_kill'] . "]";
	if (!empty($kill_count[$msg['user']]['last_kill'])) {
		$killer['time'] = $msg['time'] - $kill_count[$msg['user']]['last_kill'];
		if ($killer['time'] == 0)
			$killer['time'] = 1;
	}

	$kill_count[$msg['user']]['last_kill'] = $msg['time'];
	$killer['kills'] = $kill_count[$msg['user']]['kills'];

	if ($killer['time'] <= 18 && $killer['time'] > 0) {
		$kill_count[$msg['user']]['fast_kills'] += 1;
		$killer['fast_kills'] = $kill_count[$msg['user']]['fast_kills'];
		//echo $killer['time'];
		//echo $killer['fast_kills'];

		switch ($killer['fast_kills']) {
			case 1:
				echo " (double tap)";
				break;
			case 2:
				echo " (hat trick)";
				break;
			case 3:
				echo " (quad kill)";
				break;
			case 4:
				echo " (annihilation)";
				break;
		}
	} else {
		$kill_count[$msg['user']]['fast_kills'] = 0;
	}

	if ($killer['kills'] > 2 ) {
		switch ($killer['kills']) {
			case 3:
				echo " (serial killer)";
				break;
			case 4:
				echo " (ultimate warrior)";
				break;
			case 5:
				echo " (legendary)";
				break;
			case 6:
				echo " (onslaught)";
				break;
			case 7:
				echo " (savage sick)";
				break;
			case 8:
				echo " (dominating)";
				break;
			case 9:
				echo " (champion of newerth)";
				break;
		}
		if ($killer['kills'] >= 10 && $killer['kills'] < 15)
			echo " (bloodbath)";
		if ($killer['kills'] >= 15)
			echo " (IMMORTAL)";
	}
	//print_r($kill_count);
	return $kill_count;
}

function print_match_table ($players, $match_id, $debug) {
	$logs = get_match_log($match_id);
	//print_r($logs);
	//match table
	foreach ($players as $p) {
			$tp[$p['team'] . $p['teamindex']] = $p;
	}
	ksort($tp);
	foreach ($tp as $k => $v) {
		//prtection incase s2 crashes
		if (!empty($logs)) {
			$tp[$k] = array_merge($logs['match_player_stats'][$match_id][$v['accountid']], $tp[$k], $logs['inventory'][$match_id][$v['accountid']]);
			$tp[$k]['xpm'] = round($tp[$k]['exp'] / ($tp[$k]['secs']/60));
			$tp[$k]['gpm'] = round($tp[$k]['gold'] / ($tp[$k]['secs']/60));
			$tp[$k]['apm'] = round($tp[$k]['actions'] / ($tp[$k]['secs']/60));
		}
	}
	echo "<div><table>";
	echo "<th><span class='team1'>Legion</span></th>";
	echo "<th><span class='team2'>Hellbourne</span></th>";
	echo "<tr>";
	
	for ($t=1; $t <= 2; $t++) {
		echo "<td class='padding'><table class='players'>";
		echo "<th>Player</th><th title='Level'>lvl</th><th title='Kill/Death/Assists'>k/d/a</th><th title='Creep kills/denies'>C:k/d</th>";
		echo "<th title='XP per min'>xpm</th><th title='Gold per min'>gpm</th><th title='Actions per min'>apm</th><th title='Wards'>!</th><th>Inventory</th>";
		foreach ($tp as $p) {
			if ($p['team'] == $t) {
				echo "<tr><td class='left'><img src='http://www.heroesofnewerth.com/images/heroes/{$p['heroid']}/icon_25.jpg' title='{$p['heroname']}'>";
				echo "&nbsp;<span class='color{$p['team']}{$p['teamindex']}'>{$p['name']}</span></td>";
				echo "<td>{$p['level']}</td><td>{$p['herokills']}/{$p['deaths']}/{$p['heroassists']}</td>";
				echo "<td>{$p['teamcreepkills']}/{$p['denies']}</td><td>{$p['xpm']}</td><td>{$p['gpm']}</td><td>{$p['apm']}</td><td>{$p['wards']}</td><td class='left'>";
				for ($i=1; $i <= 6; $i++) 
					if (!empty($p["slot_{$i}"])) {
						$slot = $p["slot_{$i}"];
						echo "<img src='http://www.heroesofnewerth.com/images/items/{$slot}.jpg' title='{$slot}' width='25'>";
					}
			}
		}
		echo "</td></table></td>";
	}
	echo "</tr></table></div><br />";
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
		$tmp[$tmp_id]['id'] = $tmp_id;
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
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	//unzip the file
	$cmd = "unzip $dpath/$match_id.honreplay -d $dpath/$match_id";
	exec(escapeshellcmd($cmd));
	//check to make sure we have file
	if (!file_exists("$dpath/$match_id/replayinfo")) {
		exec(" rm $dpath/$match_id.honreplay");
		echo "<h1 style='color:red'>FILE NOT FOUND!!...could be:</h1>";
		echo "<h3>1) invalid id</h3>";
		echo "<h3>2) you need to wait longer for it to be posted</h3>";
		echo "<h3>2) replay is to old and your SOL</h3>";
		exit;
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

function get_match_log ($match_id) {
	$url = 'http://masterserver.hon.s2games.com/client_requester.php';
	$username = 'chat_parser';
	$password = 'cdf7449d6ceccd7d74345aa079aeb744';
	$ch = curl_init("$url?f=auth&login=$username&password=$password");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, array('User-agent: S2 Games/Heroes of Newerth/2.0.29.1/lac/x86-biarch'));
	preg_match('/cookie\"\;s\:\d{0,2}\:\"([^\"]*)/im', curl_exec($ch), $m);
	$cookie = $m[1];
	$ch = curl_init("$url?f=get_match_stats&match_id[0]=$match_id&cookie=$cookie");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	return unserialize(curl_exec($ch));
}
?>
