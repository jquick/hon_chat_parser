<?

// hero names
function update_heroes ($players) {
	$heroes = array(
		2 => array('rname' => '', 'name' => 'Armadon'),
		3 => array('rname' => '', 'name' => 'Behemoth'),
		4 => array('rname' => '', 'name' => 'Chronos'),
		5 => array('rname' => '', 'name' => 'Defiler'),
		6 => array('rname' => '', 'name' => 'Devourer'),
		7 => array('rname' => 'DwarfMagi', 'name' => 'Blacksmith'),
		8 => array('rname' => 'Ebulus', 'name' => 'Slither'),
		9 => array('rname' => '', 'name' => 'Electrician'),
		10 => array('rname' => 'Fairy', 'name' => 'Nymphora'),
		12 => array('rname' => 'Frosty', 'name' => 'Glacius'),
		13 => array('rname' => '', 'name' => 'Hammerstorm'),
		14 => array('rname' => '', 'name' => 'Night Hound'),
		15 => array('rname' => 'Hiro', 'name' => 'Swiftblade'),
		16 => array('rname' => 'Hunter', 'name' => 'Blood Hunter'),
		17 => array('rname' => '', 'name' => 'Kraken'),
		18 => array('rname' => 'Kunas', 'name' => 'Thunderbringer'),
		20 => array('rname' => 'Krixi', 'name' => 'Moon Quene'),
		21 => array('rname' => 'PollywogPriest', 'name' => 'Pollywog Priest'),
		22 => array('rname' => 'Rocky', 'name' => 'Pebbles'),
		24 => array('rname' => '', 'name' => 'Soulstealer'),
		25 => array('rname' => 'Treant', 'name' => 'Keeper of the Forest'),
		26 => array('rname' => '', 'name' => 'The Dark Lady'),
		27 => array('rname' => 'Voodoo', 'name' => 'Voodoo Jester'),
		29 => array('rname' => 'WolfMan', 'name' => 'War Beast'),
		30 => array('rname' => 'Yogi', 'name' => 'Wildsoul'),
		31 => array('rname' => '', 'name' => 'Zephyr'),
		34 => array('rname' => 'Mumra', 'name' => 'Pharaoh'),
		35 => array('rname' => '', 'name' => 'Tempest'),
		36 => array('rname' => '', 'name' => 'Ophelia'),
		37 => array('rname' => 'Javaras', 'name' => 'Magebane'),
		38 => array('rname' => '', 'name' => 'Legionnaire'),
		39 => array('rname' => '', 'name' => 'Predator'),
		40 => array('rname' => '', 'name' => 'Accursed'),
		41 => array('rname' => '', 'name' => 'Nomad'),
		42 => array('rname' => 'Scar', 'name' => 'The Madman'),
		43 => array('rname' => 'Shaman', 'name' => 'Demented Shaman'),
		44 => array('rname' => '', 'name' => 'Scout'),
		89 => array('rname' => '', 'name' => 'Jereziah'),
		90 => array('rname' => 'Xalynx', 'name' => 'Torturer'),
		91 => array('rname' => 'PuppetMaster', 'name' => 'Puppet Master'),
		92 => array('rname' => '', 'name' => 'Arachna'),
		93 => array('rname' => '', 'name' => 'Hellbringer'),
		94 => array('rname' => '', 'name' => 'Pyromancer'),
		95 => array('rname' => '', 'name' => 'Pestilence'),
		96 => array('rname' => '', 'name' => 'Maliken'),
		102 => array('rname' => '', 'name' => 'Andromeda'),
		103 => array('rname' => '', 'name' => 'Valkyrie'),
		104 => array('rname' => 'BabaYaga', 'name' => 'Wretched Hag'),
		105 => array('rname' => 'Succubis', 'name' => 'Succubus'),
		106 => array('rname' => 'Magmar', 'name' => 'Magmus'),
		108 => array('rname' => 'DiseasedRider', 'name' => 'Plague Rider'),
		109 => array('rname' => 'HellDemon', 'name' => 'Soul Reaper'),
		110 => array('rname' => 'Panda', 'name' => 'Pandamonium'),
		114 => array('rname' => 'CorruptedDisciple', 'name' => 'Corrupted Disciple'),
		115 => array('rname' => '', 'name' => 'Vindicator'),
		116 => array('rname' => 'SandWraith', 'name' => 'Sand Wraith'),
		117 => array('rname' => '', 'name' => 'Rampage'),
		120 => array('rname' => 'WitchSlayer', 'name' => 'Witch Slayer'),
		121 => array('rname' => 'ForsakenArcher', 'name' => 'Forsaken Archer'),
		122 => array('rname' => '', 'name' => 'Engineer'),
		123 => array('rname' => '', 'name' => 'Deadwood'),
		124 => array('rname' => 'Chipper', 'name' => 'The Chipper'),
		125 => array('rname' => '', 'name' => 'Bubbles'),
		126 => array('rname' => 'Fade', 'name' => 'Fayde'),
		127 => array('rname' => 'Bephelgor', 'name' => 'Balphagore'),
		128 => array('rname' => '', 'name' => 'Gauntlet'),
		160 => array('rname' => '', 'name' => 'Tundra'),
		161 => array('rname' => 'Gladiator', 'name' => 'The Gladiator'),
		162 => array('rname' => 'DoctorRepulsor', 'name' => 'Doctor Repulsor'),
		163 => array('rname' => 'FlintBeastwood', 'name' => 'Flint Beastwood'),
		164 => array('rname' => '', 'name' => 'Bombardier'),
		165 => array('rname' => '', 'name' => 'Moraxus'),
		166 => array('rname' => 'Hydromancer', 'name' => 'Myrmidon'),
		167 => array('rname' => '', 'name' => 'Dampeer'),
		168 => array('rname' => '', 'name' => 'Empath'),
		169 => array('rname' => '', 'name' => 'Aluna'),
		170 => array('rname' => '', 'name' => 'Tremble'),
		185 => array('rname' => '', 'name' => 'Silhouette'),
		187 => array('rname' => '', 'name' => 'Flux'),
		188 => array('rname' => '', 'name' => 'Martyr'),
		192 => array('rname' => 'Ra', 'name' => 'Amun-Ra'),
		194 => array('rname' => '', 'name' => 'Parasite'),
		195 => array('rname' => 'EmeraldWarden', 'name' => 'Emerald Warden'),
		196 => array('rname' => '', 'name' => 'Revenant'),
		197 => array('rname' => 'MonkeyKing', 'name' => 'Monkey King'),
		201 => array('rname' => 'DrunkenMaster', 'name' => 'Drunken Master'),
		202 => array('rname' => '', 'name' => 'Master Of Arms'),
		203 => array('rname' => '', 'name' => 'Rhapsody'),
		204 => array('rname' => '', 'name' => 'Geomancer'),
		205 => array('rname' => '', 'name' => 'Midas'),
		206 => array('rname' => '', 'name' => 'Cthulhuphant'),
		207 => array('rname' => '', 'name' => 'Monarch'),
		208 => array('rname' => '', 'name' => 'Gemini'),
		209 => array('rname' => 'Dreadknight', 'name' => 'Lord Salforis'),
		210 => array('rname' => '', 'name' => 'Shadowblade'),
		211 => array('rname' => '', 'name' => 'Artesia'),
		212 => array('rname' => 'Taint', 'name' => 'Gravekeeper'),
		213 => array('rname' => '', 'name' => 'Berzerker'),
		214 => array('rname' => '', 'name' => 'Draconis'),
		215 => array('rname' => '', 'name' => 'Kinesis'),
		216 => array('rname' => '', 'name' => 'Gunblade'),
		217 => array('rname' => '', 'name' => 'Blitz'),
		218 => array('rname' => '', 'name' => 'Artillery'),
		219 => array('rname' => '', 'name' => 'Ellonia'),
		220 => array('rname' => 'Riftmage', 'name' => 'Rift Walker')
	);

	//print_r($players);
	//print_r($heroes);
	foreach ($players as $k => $v) {
		foreach ($heroes as $id => $h) {
			if (substr($v['heroname'], 5) == $h['rname']) {
				$players[$k]['heroname'] = $h['name'];
				$players[$k]['heroid'] = $id;
			} else if (substr($v['heroname'], 5) == $h['name']) {
				$players[$k]['heroname'] = $h['name'];
				$players[$k]['heroid'] = $id;
			}
		}
	}
	return $players;
}
?>
