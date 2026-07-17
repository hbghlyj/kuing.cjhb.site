<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class native_pm {
	private const THREAD = 'common_pm_thread';
	private const MEMBER = 'common_pm_member';
	private const MESSAGE = 'common_pm_message';
	private const MESSAGE_STATUS = 'common_pm_message_status';
	private const BLACKLIST = 'common_pm_blacklist';

	public static function checknew($uid, $more = 0) {
		$uid = intval($uid);
		$private = DB::result_first('SELECT COUNT(*) FROM %t m INNER JOIN %t t ON t.plid=m.plid WHERE m.uid=%d AND m.isnew=1 AND t.pmtype=1', [self::MEMBER, self::THREAD, $uid]);
		$chat = DB::result_first('SELECT COUNT(*) FROM %t m INNER JOIN %t t ON t.plid=m.plid WHERE m.uid=%d AND m.isnew=1 AND t.pmtype=2', [self::MEMBER, self::THREAD, $uid]);
		$total = intval($private) + intval($chat);
		if(!$more) {
			return $total;
		}
		$result = ['newpm' => $total, 'newprivatepm' => intval($private)];
		if($more >= 2) {
			$result['newchatpm'] = intval($chat);
		}
		return $result;
	}

	public static function send($fromuid, $msgto, $subject, $message, $replypmid = 0, $isusername = 0, $type = 0) {
		$fromuid = intval($fromuid);
		$from = table_common_member::t()->fetch($fromuid);
		if(!$from || trim(stripslashes($message)) === '') {
			return 0;
		}
		if($replypmid) {
			$plid = DB::result_first('SELECT plid FROM %t WHERE pmid=%d', [self::MESSAGE, intval($replypmid)]);
			return self::reply($plid, $from, $message);
		}

		$targets = array_values(array_filter(array_unique(array_map('trim', explode(',', (string)$msgto))), 'strlen'));
		if($isusername) {
			$members = table_common_member::t()->fetch_all_by_username($targets);
			$targets = array_column($members, 'uid');
		}
		$targets = dintval($targets, true);
		if(in_array($fromuid, $targets)) {
			return -8;
		}
		$targets = self::filterBlockedTargets($from, $targets);
		if(!$targets) {
			return -9;
		}
		if($type && count($targets) < 2) {
			return -10;
		}

		$message = stripslashes($message);
		$subject = trim(stripslashes($subject));
		if($subject === '') {
			$subject = cutstr(strip_tags($message), 80, '');
		}
		$lastpmid = 0;
		if(!$type) {
			foreach($targets as $touid) {
				$lastpmid = self::sendPrivate($from, $touid, $subject, $message);
			}
		} else {
			$lastpmid = self::sendChat($from, $targets, $subject, $message);
		}
		return $lastpmid;
	}

	private static function sendPrivate($from, $touid, $subject, $message) {
		$relationship = self::relationship($from['uid'], $touid);
		$thread = DB::fetch_first('SELECT * FROM %t WHERE min_max=%s AND pmtype=1', [self::THREAD, $relationship]);
		if(!$thread) {
			$plid = DB::insert(self::THREAD, [
				'authorid' => $from['uid'], 'pmtype' => 1, 'subject' => $subject,
				'members' => 2, 'min_max' => $relationship, 'dateline' => TIMESTAMP,
				'lastdateline' => TIMESTAMP, 'lastauthorid' => $from['uid'],
				'lastsummary' => self::summary($message),
			], true);
		} else {
			$plid = $thread['plid'];
			DB::update(self::THREAD, [
				'lastdateline' => TIMESTAMP, 'lastauthorid' => $from['uid'],
				'lastsummary' => self::summary($message),
			], 'plid='.intval($plid));
		}
		$pmid = self::insertMessage($plid, $from['uid'], $message);
		self::touchMember($plid, $from['uid'], false);
		self::touchMember($plid, $touid, true);
		self::notify([$touid]);
		return $pmid;
	}

	private static function sendChat($from, $targets, $subject, $message) {
		$plid = DB::insert(self::THREAD, [
			'authorid' => $from['uid'], 'pmtype' => 2, 'subject' => $subject,
			'members' => count($targets) + 1, 'min_max' => '', 'dateline' => TIMESTAMP,
			'lastdateline' => TIMESTAMP, 'lastauthorid' => $from['uid'],
			'lastsummary' => self::summary($message),
		], true);
		$pmid = self::insertMessage($plid, $from['uid'], $message);
		self::touchMember($plid, $from['uid'], false);
		foreach($targets as $touid) {
			self::touchMember($plid, $touid, true);
		}
		self::notify($targets);
		return $pmid;
	}

	private static function reply($plid, $from, $message) {
		$thread = DB::fetch_first('SELECT * FROM %t WHERE plid=%d', [self::THREAD, intval($plid)]);
		if(!$thread) {
			return -11;
		}
		$members = DB::fetch_all('SELECT uid FROM %t WHERE plid=%d', [self::MEMBER, $thread['plid']], 'uid');
		if(!isset($members[$from['uid']])) {
			return -12;
		}
		if($thread['pmtype'] == 1) {
			$users = array_map('intval', explode('_', $thread['min_max']));
			$touid = $users[0] == $from['uid'] ? $users[1] : $users[0];
			if(!self::filterBlockedTargets($from, [$touid])) {
				return -6;
			}
			$recipients = [$touid];
		} else {
			$recipients = array_values(array_diff(array_keys($members), [$from['uid']]));
		}
		$message = stripslashes($message);
		$pmid = self::insertMessage($thread['plid'], $from['uid'], $message);
		DB::update(self::THREAD, [
			'lastdateline' => TIMESTAMP, 'lastauthorid' => $from['uid'],
			'lastsummary' => self::summary($message),
		], 'plid='.intval($thread['plid']));
		self::touchMember($thread['plid'], $from['uid'], false);
		foreach($recipients as $uid) {
			self::touchMember($thread['plid'], $uid, true);
		}
		self::notify($recipients);
		return $pmid;
	}

	private static function insertMessage($plid, $authorid, $message) {
		return DB::insert(self::MESSAGE, [
			'plid' => intval($plid), 'authorid' => intval($authorid),
			'message' => $message, 'dateline' => TIMESTAMP,
		], true);
	}

	private static function touchMember($plid, $uid, $isnew) {
		DB::query('INSERT INTO %t (plid,uid,isnew,pmnum,lastupdate,lastdateline) VALUES (%d,%d,%d,1,%d,%d) ON DUPLICATE KEY UPDATE isnew=%d,pmnum=pmnum+1,lastupdate=%d,lastdateline=%d', [
			self::MEMBER, $plid, $uid, $isnew ? 1 : 0, $isnew ? 0 : TIMESTAMP, TIMESTAMP,
			$isnew ? 1 : 0, $isnew ? 0 : TIMESTAMP, TIMESTAMP,
		]);
	}

	private static function notify($uids) {
		$uids = dintval($uids, true);
		if($uids) {
			DB::query('UPDATE %t SET newpm=1 WHERE uid IN (%n)', ['common_member', $uids]);
		}
	}

	public static function listing($uid, $page, $pagesize, $filter, $msglen) {
		$uid = intval($uid);
		$page = max(1, intval($page));
		$pagesize = max(1, intval($pagesize));
		$newsql = $filter == 'newpm' ? ' AND m.isnew=1' : '';
		$count = DB::result_first('SELECT COUNT(*) FROM %t m WHERE m.uid=%d %i', [self::MEMBER, $uid, $newsql]);
		$start = ($page - 1) * $pagesize;
		$rows = DB::fetch_all('SELECT m.*,t.* FROM %t m INNER JOIN %t t ON t.plid=m.plid WHERE m.uid=%d %i ORDER BY m.lastdateline DESC LIMIT %d,%d', [
			self::MEMBER, self::THREAD, $uid, $newsql, $start, $pagesize,
		]);
		$otherids = [];
		foreach($rows as &$row) {
			$row['touid'] = self::otherUid($row, $uid);
			$otherids[] = $row['touid'];
		}
		unset($row);
		$users = $otherids ? table_common_member::t()->fetch_all($otherids) : [];
		$data = [];
		foreach($rows as $row) {
			$summary = $msglen ? cutstr($row['lastsummary'], $msglen, '') : '';
			$row['founddateline'] = $row['dateline'];
			$row['dateline'] = $row['lastdateline'];
			$row['pmid'] = $row['plid'];
			$row['lastauthor'] = self::username($row['lastauthorid']);
			$row['lastsummary'] = $summary;
			$row['msgfromid'] = $row['lastauthorid'];
			$row['msgfrom'] = $row['lastauthor'];
			$row['message'] = $summary;
			$row['new'] = $row['isnew'];
			$row['msgtoid'] = $row['touid'];
			$row['tousername'] = isset($users[$row['touid']]) ? $users[$row['touid']]['username'] : '';
			$data[] = $row;
		}
		return ['data' => $data, 'count' => intval($count)];
	}

	public static function view($uid, $pmid, $touid, $daterange, $page, $pagesize, $type, $isplid) {
		$uid = intval($uid);
		if($pmid) {
			$row = DB::fetch_first('SELECT t.*,p.*,t.authorid AS founderuid,t.dateline AS founddateline FROM %t p INNER JOIN %t t ON t.plid=p.plid INNER JOIN %t m ON m.plid=p.plid AND m.uid=%d LEFT JOIN %t s ON s.pmid=p.pmid AND s.uid=%d WHERE p.pmid=%d AND s.pmid IS NULL', [
				self::MESSAGE, self::THREAD, self::MEMBER, $uid, self::MESSAGE_STATUS, $uid, intval($pmid),
			]);
			return $row ? self::formatRows([$row]) : [];
		}
		$plid = $isplid ? intval($touid) : self::findPrivateThread($uid, intval($touid));
		if(!$plid || !DB::result_first('SELECT COUNT(*) FROM %t WHERE plid=%d AND uid=%d', [self::MEMBER, $plid, $uid])) {
			return [];
		}
		$thread = DB::fetch_first('SELECT * FROM %t WHERE plid=%d', [self::THREAD, $plid]);
		if(!$thread || ($type && $thread['pmtype'] != 2)) {
			return [];
		}
		$starttime = self::rangeStart($daterange);
		$limitsql = '';
		if($page && $pagesize) {
			$start = max(0, (intval($page) - 1) * intval($pagesize));
			$limitsql = ' LIMIT '.$start.','.intval($pagesize);
		}
		$rows = DB::fetch_all('SELECT t.*,p.*,t.authorid AS founderuid,t.dateline AS founddateline FROM %t p INNER JOIN %t t ON t.plid=p.plid LEFT JOIN %t s ON s.pmid=p.pmid AND s.uid=%d WHERE p.plid=%d AND p.dateline>=%d AND s.pmid IS NULL ORDER BY p.dateline DESC%i', [
			self::MESSAGE, self::THREAD, self::MESSAGE_STATUS, $uid, $plid, $starttime, $limitsql,
		]);
		DB::update(self::MEMBER, ['isnew' => 0], 'plid='.$plid.' AND uid='.$uid);
		return self::formatRows(array_reverse($rows));
	}

	public static function viewnum($uid, $touid, $isplid) {
		$plid = $isplid ? intval($touid) : self::findPrivateThread(intval($uid), intval($touid));
		return intval(DB::result_first('SELECT pmnum FROM %t WHERE plid=%d AND uid=%d', [self::MEMBER, $plid, intval($uid)]));
	}

	private static function formatRows($rows) {
		$authorids = array_values(array_unique(array_column($rows, 'authorid')));
		$authors = $authorids ? table_common_member::t()->fetch_all($authorids) : [];
		foreach($rows as &$row) {
			$row['touid'] = self::otherUid($row, $row['authorid']);
			$row['author'] = isset($authors[$row['authorid']]) ? $authors[$row['authorid']]['username'] : '';
			$row['msgfromid'] = $row['authorid'];
			$row['msgfrom'] = $row['author'];
			$row['msgtoid'] = $row['touid'];
			$row['message'] = self::formatMessage($row['message']);
		}
		unset($row);
		return $rows;
	}

	public static function deleteMessages($uid, $pmids) {
		$uid = intval($uid);
		foreach(dintval((array)$pmids, true) as $pmid) {
			$message = DB::fetch_first('SELECT p.pmid,p.plid FROM %t p INNER JOIN %t m ON m.plid=p.plid AND m.uid=%d WHERE p.pmid=%d', [self::MESSAGE, self::MEMBER, $uid, $pmid]);
			if(!$message) continue;
			DB::query('INSERT IGNORE INTO %t (pmid,uid) VALUES (%d,%d)', [self::MESSAGE_STATUS, $pmid, $uid]);
			DB::query('UPDATE %t SET pmnum=GREATEST(0,pmnum-1) WHERE plid=%d AND uid=%d', [self::MEMBER, $message['plid'], $uid]);
		}
		return 1;
	}

	public static function deleteUsers($uid, $touids) {
		foreach(dintval((array)$touids, true) as $touid) {
			$plid = self::findPrivateThread(intval($uid), $touid);
			$plid && self::leaveThread($uid, $plid);
		}
		return 1;
	}

	public static function deleteChats($uid, $plids, $type = 0) {
		foreach(dintval((array)$plids, true) as $plid) {
			$type ? self::deleteThread($uid, $plid) : self::leaveThread($uid, $plid);
		}
		return 1;
	}

	private static function leaveThread($uid, $plid) {
		if(!DB::result_first('SELECT COUNT(*) FROM %t WHERE plid=%d AND uid=%d', [self::MEMBER, $plid, $uid])) return false;
		DB::query('INSERT IGNORE INTO %t (pmid,uid) SELECT pmid,%d FROM %t WHERE plid=%d', [self::MESSAGE_STATUS, $uid, self::MESSAGE, $plid]);
		DB::delete(self::MEMBER, 'plid='.intval($plid).' AND uid='.intval($uid));
		self::cleanupThread($plid);
		return true;
	}

	private static function deleteThread($uid, $plid) {
		$thread = DB::fetch_first('SELECT authorid FROM %t WHERE plid=%d', [self::THREAD, $plid]);
		if(!$thread || intval($thread['authorid']) !== intval($uid)) return false;
		DB::delete(self::MEMBER, 'plid='.intval($plid));
		self::cleanupThread($plid);
		return true;
	}

	private static function cleanupThread($plid) {
		if(DB::result_first('SELECT COUNT(*) FROM %t WHERE plid=%d', [self::MEMBER, $plid])) return;
		$pmids = DB::fetch_all('SELECT pmid FROM %t WHERE plid=%d', [self::MESSAGE, $plid], 'pmid');
		if($pmids) DB::query('DELETE FROM %t WHERE pmid IN (%n)', [self::MESSAGE_STATUS, array_keys($pmids)]);
		DB::delete(self::MESSAGE, 'plid='.intval($plid));
		DB::delete(self::THREAD, 'plid='.intval($plid));
	}

	public static function readstatus($uid, $uids, $plids, $status = 0) {
		$allplids = dintval((array)$plids, true);
		foreach(dintval((array)$uids, true) as $touid) {
			$plid = self::findPrivateThread(intval($uid), $touid);
			$plid && $allplids[] = $plid;
		}
		if($allplids) DB::query('UPDATE %t SET isnew=%d WHERE uid=%d AND plid IN (%n)', [self::MEMBER, $status ? 1 : 0, intval($uid), array_unique($allplids)]);
		return 1;
	}

	public static function ignore($uid) {
		DB::update(self::MEMBER, ['isnew' => 0], 'uid='.intval($uid));
		return 1;
	}

	public static function deleteUserData($uids) {
		$uids = dintval((array)$uids, true);
		if(!$uids) return;
		$memberships = DB::fetch_all('SELECT plid,uid FROM %t WHERE uid IN (%n)', [self::MEMBER, $uids]);
		foreach($memberships as $membership) {
			self::leaveThread($membership['uid'], $membership['plid']);
			$thread = DB::fetch_first('SELECT authorid,pmtype FROM %t WHERE plid=%d', [self::THREAD, $membership['plid']]);
			if($thread && $thread['pmtype'] == 2 && in_array(intval($thread['authorid']), $uids, true)) {
				$newauthor = DB::result_first('SELECT MIN(uid) FROM %t WHERE plid=%d', [self::MEMBER, $membership['plid']]);
				$newauthor && DB::update(self::THREAD, ['authorid' => intval($newauthor)], 'plid='.intval($membership['plid']));
			}
		}
		DB::query('DELETE FROM %t WHERE uid IN (%n)', [self::MESSAGE_STATUS, $uids]);
		DB::query('DELETE FROM %t WHERE uid IN (%n)', [self::BLACKLIST, $uids]);
	}

	public static function chatMembers($uid, $plid) {
		$members = DB::fetch_all('SELECT uid FROM %t WHERE plid=%d', [self::MEMBER, intval($plid)], 'uid');
		if(!isset($members[intval($uid)])) return 0;
		$author = DB::result_first('SELECT authorid FROM %t WHERE plid=%d AND pmtype=2', [self::THREAD, intval($plid)]);
		return $author ? ['author' => intval($author), 'member' => array_keys($members)] : 0;
	}

	public static function appendChat($plid, $uid, $touid) {
		$thread = DB::fetch_first('SELECT * FROM %t WHERE plid=%d', [self::THREAD, intval($plid)]);
		if(!$thread) return -11;
		if($thread['pmtype'] != 2) return -13;
		if($thread['authorid'] != intval($uid)) return -12;
		$from = table_common_member::t()->fetch(intval($uid));
		if(!self::filterBlockedTargets($from, [intval($touid)])) return -6;
		$count = DB::result_first('SELECT COUNT(*) FROM %t WHERE plid=%d', [self::MESSAGE, $plid]);
		DB::query('INSERT INTO %t (plid,uid,isnew,pmnum,lastupdate,lastdateline) VALUES (%d,%d,1,%d,0,%d) ON DUPLICATE KEY UPDATE isnew=1,pmnum=%d,lastdateline=%d', [self::MEMBER, $plid, $touid, $count, TIMESTAMP, $count, TIMESTAMP]);
		DB::query('UPDATE %t SET members=(SELECT COUNT(*) FROM %t WHERE plid=%d) WHERE plid=%d', [self::THREAD, self::MEMBER, $plid, $plid]);
		self::notify([$touid]);
		return 1;
	}

	public static function kickChat($plid, $uid, $touid) {
		$thread = DB::fetch_first('SELECT * FROM %t WHERE plid=%d', [self::THREAD, intval($plid)]);
		if(!$thread) return -11;
		if($thread['pmtype'] != 2) return -13;
		if($thread['authorid'] != intval($uid) || intval($uid) == intval($touid)) return -12;
		self::leaveThread($touid, $plid);
		DB::query('UPDATE %t SET members=(SELECT COUNT(*) FROM %t WHERE plid=%d) WHERE plid=%d', [self::THREAD, self::MEMBER, $plid, $plid]);
		return 1;
	}

	public static function blacklistGet($uid) {
		return (string)DB::result_first('SELECT usernames FROM %t WHERE uid=%d', [self::BLACKLIST, intval($uid)]);
	}

	public static function blacklistSet($uid, $blacklist) {
		$names = self::normalizeBlacklist($blacklist);
		DB::query('INSERT INTO %t (uid,usernames) VALUES (%d,%s) ON DUPLICATE KEY UPDATE usernames=%s', [self::BLACKLIST, intval($uid), $names, $names]);
		return 1;
	}

	public static function blacklistAdd($uid, $username) {
		$current = self::normalizeBlacklist(self::blacklistGet($uid));
		$names = array_filter(array_merge(explode(',', $current), (array)$username));
		return self::blacklistSet($uid, implode(',', $names));
	}

	public static function blacklistDelete($uid, $username) {
		$remove = array_map('strtolower', (array)$username);
		$names = array_filter(explode(',', self::blacklistGet($uid)), static fn($name) => !in_array(strtolower($name), $remove, true));
		return self::blacklistSet($uid, implode(',', $names));
	}

	private static function filterBlockedTargets($from, $targets) {
		if(!$targets) return [];
		$rows = DB::fetch_all('SELECT uid,usernames FROM %t WHERE uid IN (%n)', [self::BLACKLIST, $targets], 'uid');
		$result = [];
		foreach($targets as $uid) {
			$blocked = isset($rows[$uid]) ? array_map('strtolower', explode(',', $rows[$uid]['usernames'])) : [];
			if(!in_array('{all}', $blocked, true) && !in_array(strtolower($from['loginname']), $blocked, true) && !in_array(strtolower($from['username']), $blocked, true)) $result[] = $uid;
		}
		return $result;
	}

	private static function normalizeBlacklist($blacklist) {
		$names = preg_split('/[\s,]+/', trim(stripslashes((string)$blacklist)), -1, PREG_SPLIT_NO_EMPTY);
		$result = [];
		foreach($names as $name) {
			if($name === '{ALL}') {
				$result['{ALL}'] = '{ALL}';
				continue;
			}
			$user = table_common_member::t()->fetch_by_username($name, 1);
			if($user) $result[strtolower($user['loginname'])] = $user['loginname'];
		}
		return implode(',', $result);
	}

	private static function findPrivateThread($uid, $touid) {
		return intval(DB::result_first('SELECT plid FROM %t WHERE min_max=%s AND pmtype=1', [self::THREAD, self::relationship($uid, $touid)]));
	}

	private static function relationship($uid, $touid) {
		$ids = [intval($uid), intval($touid)];
		sort($ids, SORT_NUMERIC);
		return $ids[0] && $ids[0] != $ids[1] ? implode('_', $ids) : '';
	}

	private static function otherUid($row, $uid) {
		if(intval($row['pmtype']) !== 1) return 0;
		$users = array_map('intval', explode('_', $row['min_max']));
		return $users[0] == intval($uid) ? $users[1] : $users[0];
	}

	private static function username($uid) {
		$user = table_common_member::t()->fetch(intval($uid));
		return $user ? $user['username'] : '';
	}

	private static function summary($message) {
		return cutstr(strip_tags(preg_replace('/\[[^\]]+\]/', '', $message)), 150, '');
	}

	private static function rangeStart($daterange) {
		$today = TIMESTAMP - TIMESTAMP % 86400;
		return match(intval($daterange)) {
			1 => $today, 2 => $today - 86400, 3 => $today - 172800,
			4 => $today - 604800, default => 0,
		};
	}

	private static function formatMessage($message) {
		require_once libfile('function/discuzcode');
		return discuzcode($message, false, false, 0, 1, 1, 1);
	}
}
