#!/usr/bin/env python
#import datetime
from datetime import datetime
from Queue import Queue, Empty
from phpserialize import *
import urllib2, threading, sys, string, csv
import re, cgi, codecs, os.path, zipfile
tstart = datetime.now()

form = cgi.FieldStorage()
dpath = '/home/jquick/jaredquick.com/tmp/'
show_kills = 0
match_id = ""
chat_log= ""

if 'show_kills' in form:
	show_kills = int(form["show_kills"].value)
if 'match_id' in form:
	match_id = form["match_id"].value
elif sys.argv[1] is not None:
	match_id = sys.argv[1]
else:
	match_id = "94575700"

print "Content-type:text/html\r\n\r\n"
print '<html>'
print '<title>HONCP</title>'
print '<link rel="shortcut icon" href="images/favicon.ico" >'
print "<link href='css/honcp.css' rel='stylesheet' type='text/css'>"
print "<script src='js/jquery-1.7.2.min.js' type='text/javascript'></script>"
print "<form method='get' action='honcp.py'>"
print "<table><tr><td>"
print "Match ID:<input type='text' value='%s' size='12' maxlength='12' name='match_id'></td><td>" % (match_id)
print "Show Kills:<input type='checkbox' name='show_kills' " + ('checked' if show_kills == 1 else '')
print "value='1' /></td><td><input type='submit' value='Go!' /></td></tr></table></form>"

def run():
	if os.path.exists(dpath + match_id + '.zip'):
		parse()
	else:
		download_file(match_id)
		parse()

def parse():
	check_award = 0
	ginfo = {}
	players = []
	event_list = {}
	match_info = ""
	events = ['INFO_DATE','INFO_SERVER','INFO_GAME','INFO_MATCH','INFO_SETTINGS','PLAYER_CONNECT','PLAYER_TEAM_CHANGE','PLAYER_SELECT','PLAYER_CHAT','KILL','GAME_START','AWARD_FIRST_BLOOD','AWARD_KILL_STREAK','AWARD_MULTI_KILL','GAME_CONCEDE','GAME_END','HERO_POWERUP','PLAYER_RANDOM']

	q = Queue()
	t_match_info = threading.Thread(target=get_match_info, args=(match_id,q))
	t_match_info.daemon = True
	t_match_info.start()

	z = zipfile.ZipFile(dpath + match_id + '.zip', "r")
	z.extract(z.namelist()[0], dpath)
	z.close()
	f = codecs.open('../tmp/m' + match_id + '.log', encoding='utf-16-le', mode='rb')
	while 1:
		lines = f.readlines(100000)
		if not lines:
			break
		for line in lines:
			line = line.encode('ascii', 'ignore')
			event = first_word(line)
			#if event in event_list:
				#event_list[event] += 1
			#else:
				#event_list[event] = 0

			#only parse events we care about
			if event in events:
				if check_award == 1 and event[0:5] != 'AWARD':
					log('<br />')
					check_award = 0
				line = line.replace(':', ': ')
				for row in csv.reader([line], delimiter=' ', quotechar='"'):
					src = {}
					src['event'] = row[0]
					for i in row:
						if i and i[-1] == ':':
							z = row.index(i)
							i = i.replace(':', '')
							src[i] = row[z+1]

				e = src['event']
				if e == 'INFO_DATE':
					ginfo = info_date(src, ginfo)
				elif e == 'INFO_SERVER':
					ginfo = info_server(src, ginfo)
				elif e == 'INFO_GAME':
					ginfo = info_game(src, ginfo)
				elif e == 'INFO_MATCH':
					ginfo = info_match(src, ginfo)
				elif e == 'INFO_MAP':
					ginfo = info_map(src, ginfo)
				elif e == 'INFO_SETTINGS':
					ginfo = info_settings(src, ginfo)
				elif e == 'PLAYER_CONNECT':
					players = player_connect(src, players)
				elif e == 'PLAYER_TEAM_CHANGE':
					players = player_team_change(src, players)
				elif e == 'PLAYER_SELECT':
					players = player_select(src, players)
				elif e == 'PLAYER_RANDOM':
					players = player_select(src, players)
				elif e == 'PLAYER_CHAT':
					player_chat(src, players)
				elif e == 'KILL':
					if show_kills == 1:
						check_award = kill(src, players)
				elif e == 'GAME_START':
					log('[GAME_START]<br />')
				elif e == 'AWARD_FIRST_BLOOD' and check_award == 1:
					award_first_blood()
				elif e == 'AWARD_KILL_STREAK' and check_award == 1:
					award_kill_streak(src)
				elif e == 'AWARD_MULTI_KILL' and check_award == 1:
					award_multi_kill(src)
				elif e == 'GAME_CONCEDE':
					ginfo = game_end(src, ginfo)
				elif e == 'GAME_END':
					ginfo = game_end(src, ginfo)
				elif e == 'HERO_POWERUP':
					players = hero_powerup(src, players)
	#cleanup
	f.close()
	os.remove('../tmp/m' + match_id + '.log')
	#print '&#8476;'
	#wait for thread to finish
	try:
		match_info = q.get(1, 2.1)
		display_match_info(match_info, ginfo, players)
	except Empty:
		echo("<h2>s2 is being slow.. click <input type='button' value='force match stats'/> if you wana wait</h2><br /><br />")

	print chat_log



def info_date(src, ginfo):
	ginfo['date'] = src['date']
	ginfo['time'] = src['time']
	return ginfo

def info_server(src, ginfo):
	ginfo['server'] = src['name']
	return ginfo

def info_game(src, ginfo):
	ginfo['version'] = src['version']
	return ginfo

def info_match(src, ginfo):
	ginfo['name'] = src['name']
	ginfo['id'] = src['id']
	return ginfo

def info_map(src, ginfo):
	ginfo['map'] = src['name']
	return ginfo

def info_settings(src, ginfo):
	ginfo['mode'] = src['mode']
	ginfo['options'] = src['options']
	return ginfo

def player_connect(src, players):
	i = int(src['player'])
	tmp = {'name': src['name'], 'psr': src['psr'], 'id': src['id'], 'parse_id': i, 'runes': 0}
	players.insert(i, tmp)
	return players

def player_team_change(src, players):
	i = int(src['player'])
	players[i]['team'] = src['team']
	sort = sorted(players, key=lambda p: p['psr'], reverse=True)
	ti = 0
	ti2 = 0
	for p in sort:
		idx = p['parse_id']
		if p['team'] == '1':
			players[idx]['teamindex'] = ti
			ti += 1
		elif p['team'] == '2':
			players[idx]['teamindex'] = ti2
			ti2 += 1
	return players

def player_select(src, players):
	i = int(src['player'])
	players[i]['hero'] = src['hero']
	return players

def player_chat(src, players):
	i = int(src['player'])
	p = players[i]
	if 'time' in src:
		log(time(src['time']))
	else:
		log('[00:00]')
	if src['target'] == 'team':
		log("<span class='team%s'>[%s]</span>" % (p['team'], src['target']))
	else:
		log("<span class='%s'>[%s]</span>" % (src['target'], src['target']))
	log("<span class='color%s%s'>%s: </span>" % (p['team'], p['teamindex'], p['name']))
	log('%s<br />' % (src['msg']))

def kill(src, players):
	if 'owner' in src and 'player' in src and src['target'][0:4] == 'Hero':
		i = int(src['player'])
		i2 = int(src['owner'])
		p = players[i]
		p2 = players[i2]

		log(time(src['time']))
		log("<span class='kill'>[kill]<span class='color%s%s'>%s</span> cridered " % (p['team'], p['teamindex'], p['name']))
		log("<span class='color%s%s'>%s</span></span>" % (p2['team'], p2['teamindex'], p2['name']))
		return 1

def award_first_blood():
	log("<span class='kill'>")
	log(' (first blood)')
	log("</span>")

def award_kill_streak(src):
	log("<span class='kill'>")
	c = int(src['count'])
	if c == 3:
		log(' (serial killer)')
	elif c == 4:
		log (' (ultimate warrior)')
	elif c == 5:
		log (' (legendary)')
	elif c == 6:
		log (' (onslaught)')
	elif c == 7:
		log (' (savage sick)')
	elif c == 8:
		log (' (dominating)')
	elif c == 9:
		log (' (champion of newerth)')
	elif c >= 10 and c < 15:
		log (' (bloodbath)')
	elif c >= 15:
		log (' (IMMORTAL)')
	log("</span>")

def award_multi_kill(src):
	log("<span class='kill'>")
	c = int(src['count'])
	if c == 2:
		log(' (double tap)')
	elif c == 3:
		log (' (hat trick)')
	elif c == 4:
		log (' (quad kill)')
	elif c == 5:
		log (' (annihilation)')
	log("</span>")

def game_end(src, ginfo):
	ginfo['winner'] = src['winner']
	ginfo['endtime'] = src['time']
	return ginfo

def hero_powerup(src, players):
	i = int(src['player'])
	players[i]['runes'] += 1
	return players

def log(string):
	global chat_log
	chat_log += string

def echo(string):
	sys.stdout.write(string)

def time(t):
	t = int(t)/1000
	minutes, seconds = divmod(t, 60)
	return '[%02d:%02d]' % (minutes, seconds)

def first_word(line):
	word = ""
	for char in line:
		if char != " ":
			word += char
		else:
			break
	return word.strip()

def download_file(match_id):
	file = urllib2.urlopen("http://hondiff.appspot.com/gamelog/" + match_id)
	output = open(dpath + match_id + '.zip','wb')
	output.write(file.read())
	output.close()

def get_match_info(match_id,q):
	url = 'http://masterserver.hon.s2games.com/client_requester.php'
	username = 'chat_parser'
	password = 'cdf7449d6ceccd7d74345aa079aeb744'
	request = urllib2.Request(url + '?f=auth&login=%s&password=%s' % (username, password))
	request.add_header('User-Agent', 'S2 Games/Heroes of Newerth/2.0.29.1/lac/x86-biarch')
	response = urllib2.urlopen(request)
	cookie = re.search("cookie\"\;s\:\d{0,2}\:\"([^\"]*)", response.read())
	cookie = cookie.group(1)
	request = urllib2.Request(url + '?f=get_match_stats&match_id[0]=%s&cookie=%s' % (match_id, cookie))
	request.add_header('User-Agent', 'S2 Games/Heroes of Newerth/2.0.29.1/lac/x86-biarch')
	response = urllib2.urlopen(request)
	q.put(loads(response.read()))

def display_match_info(match_info, ginfo, players):
	tk = {'1': 0, '2': 0}
	for p in players:
		i = players.index(p)
		if 'team' in p:
			p = dict(p.items() + match_info['match_player_stats'][int(match_id)][int(p['id'])].items())
			if int(p['id']) in match_info['inventory'][int(match_id)]:
				p = dict(p.items() + match_info['inventory'][int(match_id)][int(p['id'])].items())
			p['xpm'] = int(p['exp'])/(int(p['secs'])/60)
			p['gpm'] = int(p['gold'])/(int(p['secs'])/60)
			p['apm'] = int(p['actions'])/(int(p['secs'])/60)
			tk[p['team']] += int(p['herokills'])
			players[i] = p

	echo('<div><table>')
	echo("<th>[%d]&nbsp;<span class='team1'>Legion</span>&nbsp;" % (tk['1']))
	echo('[WINNER]' if ginfo['winner'] == '1' else '' + "</th>")
	echo("<th>[%d]&nbsp;<span class='team2'>Hellbourne</span>&nbsp;" % (tk['2']))
	echo('[WINNER]' if ginfo['winner'] == '2' else '' + "</th>")
	echo('<tr>')

	for t in tk:
		echo("<td class='padding'><table class='players'>")
		echo("<th>Player</th><th title='Level'>lvl</th><th title='Kill/Death/Assists'>k/d/a</th><th title='Creep kills/denies'>C:k/d</th>")
		echo("<th title='XP per min'>xpm</th><th title='Gold per min'>gpm</th><th title='Actions per min'>apm</th><th title='Wards'>!</th><th title='Runes'>&#8476</th><th>Inventory</th>")

		for p in sorted(players, key=lambda p: p['psr'], reverse=True):
			if 'team' in p and p['team'] == t:
				echo("<tr><td class='left'>")
				echo("<img src='http://www.heroesofnewerth.com/images/heroes/%s/icon_25.jpg' title='%s-%s'>" % (p['hero_id'], p['hero_id'], p['hero']))
				echo("&nbsp;<span class='color%s%s'>%s</span></td>" % (p['team'], p['teamindex'], p['name']))
				echo("<td>%s</td><td>%s/%s/%s</td>" % (p['level'], p['herokills'], p['deaths'], p['heroassists']))
				echo("<td>%s/%s</td><td>%s</td><td>%s</td><td>%s</td>" % (p['teamcreepkills'], p['denies'], p['xpm'], p['gpm'], p['apm']))
				echo("<td>%s</td><td>%s</td><td class='left'>" % (p['wards'], p['runes']))
				for i in range(1, 7):
					if 'slot_%d' % (i) in p:
						s = p['slot_%d' % (i)]
						if s is not None:
							echo("<img src='http://www.heroesofnewerth.com/images/items/%s.jpg' title='%s' width='25'>" % (s,s))
		echo("</td></table></td>")
	echo('</tr></table></div>')

run()

print '</html>'
tend = datetime.now()
print 'parsed in %s' % (tend - tstart)
