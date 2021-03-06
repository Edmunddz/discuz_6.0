<?php

/*
	[Discuz!] (C)2001-2007 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: forumdisplay.php 10115 2007-08-24 00:58:08Z cnteacher $
*/

define('CURSCRIPT', 'forumdisplay');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/forum.func.php';

$discuz_action = 2;

if($forum['redirect']) {
	dheader("Location: $forum[redirect]");
} elseif($forum['type'] == 'group') {
	dheader("Location: {$indexname}?gid=$fid");
} elseif(empty($forum['fid'])) {
	showmessage('forum_nonexistence', NULL, 'HALTED');
}

$showoldetails = isset($showoldetails) ? $showoldetails : '';
switch($showoldetails) {
	case 'no': dsetcookie('onlineforum', 0, 86400 * 365); break;
	case 'yes': dsetcookie('onlineforum', 1, 86400 * 365); break;
}

if($forum['type'] == 'forum') {
	$navigation = "&raquo; $forum[name]";
	$navtitle = strip_tags($forum['name']);
} else {
	$forumup = $_DCACHE['forums'][$forum['fup']]['name'];
	$navigation = "&raquo; <a href=\"forumdisplay.php?fid=$forum[fup]\">$forumup</a> &raquo; $forum[name]";
	$navtitle = strip_tags($forum['name']).' - '.strip_tags($forumup);
}

$rsshead = $rssstatus ? ('<link rel="alternate" type="application/rss+xml" title="'.$bbname.' - '.$navtitle.'" href="'.$boardurl.'rss.php?fid='.$fid.'&amp;auth='.$rssauth."\" />\n") : '';
$navtitle .= ' - ';
$metakeywords = !$forum['keywords'] ? strip_tags($forum['name']) : $forum['keywords'];
$metadescription = !$forum['description'] ? strip_tags($forum['name']) : strip_tags($forum['description']);

if($forum['viewperm'] && !forumperm($forum['viewperm']) && !$forum['allowview']) {
	showmessage('forum_nopermission', NULL, 'NOPERM');
} elseif($forum['formulaperm']) {
	forumformulaperm($forum['formulaperm']);
}

if($forum['password']) {
	if($action == 'pwverify') {
		if($pw != $forum['password']) {
			showmessage('forum_passwd_incorrect', NULL, 'HALTED');
		} else {
			dsetcookie('fidpw'.$fid, $pw);
			showmessage('forum_passwd_correct', "forumdisplay.php?fid=$fid");
		}
	} elseif($forum['password'] != $_DCOOKIE['fidpw'.$fid]) {
		include template('forumdisplay_passwd');
		exit();
	}
}

if($forum['rules']) {
	if(empty($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'rules') === FALSE) {
		$collapse['rules'] = '';
		$collapse['rules_link'] = 'display: none';
	} else {
		$collapse['rules'] = 'display: none';
		$collapse['rules_link'] = '';
	}
	$forum['rules'] && $forum['rules'] = nl2br($forum['rules']);
}

$forum['modrecommend'] = $forum['modrecommend'] ? unserialize($forum['modrecommend']) : array();
if($forum['modrecommend'] && $forum['modrecommend']['open']) {
	$forum['recommendlist'] = recommendupdate($fid, $forum['modrecommend']);
}

if($forum['recommendlist']) {
	if(empty($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'recommendlist') === FALSE) {
		$collapse['recommendlist'] = '';
		$collapse['recommendlist_link'] = 'display: none';
	} else {
		$collapse['recommendlist'] = 'display: none';
		$collapse['recommendlist_link'] = '';
	}
}

$toptablewidth = $forum['rules'] && $forum['recommendlist'] ? '50%' : '100%';

$forum['typemodels'] = $forum['typemodels'] ? unserialize($forum['typemodels']) : array();

$moderatedby = moddisplay($forum['moderators'], 'forumdisplay');
$highlight = empty($highlight) ? '' : htmlspecialchars($highlight);
if($forum['autoclose']) {
	$closedby = $forum['autoclose'] > 0 ? 'dateline' : 'lastpost';
	$forum['autoclose'] = abs($forum['autoclose']) * 86400;
}

$subexists = 0;
foreach($_DCACHE['forums'] as $sub) {
	if($sub['type'] == 'sub' && $sub['fup'] == $fid && (!$hideprivate || !$sub['viewperm'] || forumperm($sub['viewperm']) || strstr($sub['users'], "\t$discuz_uid\t"))) {
		$subexists = 1;
		$sublist = array();
		$sql = $accessmasks ? "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, ff.description, ff.moderators, ff.icon, ff.viewperm, a.allowview FROM {$tablepre}forums f
						LEFT JOIN {$tablepre}forumfields ff ON ff.fid=f.fid
						LEFT JOIN {$tablepre}access a ON a.uid='$discuz_uid' AND a.fid=f.fid
						WHERE fup='$fid' AND status>0 AND type='sub' ORDER BY f.displayorder"
					: "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, ff.description, ff.moderators, ff.icon, ff.viewperm FROM {$tablepre}forums f
						LEFT JOIN {$tablepre}forumfields ff USING(fid)
						WHERE f.fup='$fid' AND f.status>0 AND f.type='sub' ORDER BY f.displayorder";
		$query = $db->query($sql);
		while($sub = $db->fetch_array($query)) {
			if(forum($sub)) {
				$sub['orderid'] = count($sublist);
				$sublist[] = $sub;
			}
		}
		break;
	}
}

if($subexists) {
	if($forum['forumcolumns']) {
		$forum['forumcolwidth'] = floor(100 / $forum['forumcolumns']).'%';
		$forum['subscount'] = count($sublist);
		$forum['endrows'] = '';
		if($colspan = $forum['subscount'] % $forum['forumcolumns']) {
			while(($forum['forumcolumns'] - $colspan) > 0) {
				$forum['endrows'] .= '<td>&nbsp;</td>';
				$colspan ++;
			}
			$forum['endrows'] .= '</tr>';
		}
	}
	if(empty($_COOKIE['discuz_collapse']) || strpos($_COOKIE['discuz_collapse'], 'subforum_'.$fid) === FALSE) {
		$collapse['subforum'] = '';
		$collapseimg['subforum'] = 'collapsed_no.gif';
	} else {
		$collapse['subforum'] = 'display: none';
		$collapseimg['subforum'] = 'collapsed_yes.gif';
	}
}

if($forum['simple'] & 1) {
	$forumselect = $forummenu = '';
	if($forumjump) {
		if($jsmenu[1]) {
			$forummenu = forumselect(FALSE, 1);
		} else {
			$forumselect = forumselect();
		}
	}
	include template('forumdisplay_simple');
	exit();
}

$page = isset($page) ? max(1, intval($page)) : 1;
$page = $threadmaxpages && $page > $threadmaxpages ? 1 : $page;
$start_limit = ($page - 1) * $tpp;

if($page == 1) {
	if($_DCACHE['announcements_forum']) {
		$announcement = $_DCACHE['announcements_forum'];
		$announcement['starttime'] = gmdate($dateformat, $announcement['starttime'] + ($timeoffset * 3600));
	} else {
		$announcement = NULL;
	}
}

$forumdisplayadd = $filteradd = '';
$specialtype = array('poll' => 1, 'trade' => 2, 'reward' => 3, 'activity' => 4, 'debate' => 5, 'video' => 6);

if(isset($filter)) {
	if($filter == 'digest') {
		$forumdisplayadd .= '&amp;filter=digest';
		$filteradd = "AND digest>'0'";
	} elseif($filter == 'type' && $forum['threadtypes']['listable'] && $typeid && isset($forum['threadtypes']['types'][$typeid])) {
		$forumdisplayadd .= "&amp;filter=type&amp;typeid=$typeid";
		$filteradd = "AND typeid='$typeid'";
	} elseif(preg_match("/^\d+$/", $filter)) {
		$forumdisplayadd .= "&amp;filter=$filter";
		$filteradd = $filter ? "AND lastpost>='".($timestamp - $filter)."'" : '';
	} elseif(isset($specialtype[$filter])) {
		$forumdisplayadd .= "&amp;filter=$filter";
		$filteradd = "AND special='$specialtype[$filter]'";
	} else {
		$filter = '';
	}
} else {
	$filter = '';
}

isset($orderby) && in_array($orderby, array('lastpost', 'dateline', 'replies', 'views')) ? $forumdisplayadd .= "&amp;orderby=$orderby" : $orderby = $_DCACHE['forums'][$fid]['orderby'] ? $_DCACHE['forums'][$fid]['orderby'] : 'lastpost';
isset($ascdesc) && in_array($ascdesc, array('ASC', 'DESC')) ? $forumdisplayadd .= "&amp;ascdesc=$ascdesc" : $ascdesc = $_DCACHE['forums'][$fid]['ascdesc'] ? $_DCACHE['forums'][$fid]['ascdesc'] : 'DESC';

$check = array();
$check[$filter] = $check[$orderby] = $check[$ascdesc] = 'selected="selected"';

if($whosonlinestatus == 2 || $whosonlinestatus == 3) {
	$whosonlinestatus = 1;
	$onlineinfo = explode("\t", $onlinerecord);
	$detailstatus = $showoldetails == 'yes' || (((!isset($_DCOOKIE['onlineforum']) && !$whosonline_contract) || $_DCOOKIE['onlineforum']) && $onlineinfo[0] < 500 && !$showoldetails);

	if($detailstatus) {
		updatesession();
		@include language('actions');
		$whosonline = array();
		$forumname = strip_tags($forum['name']);
		$guestwhere = isset($_DCACHE['onlinelist'][7]) ? '' : "uid>'0' AND";

		$query = $db->query("SELECT uid, groupid, username, invisible, lastactivity, action FROM {$tablepre}sessions WHERE $guestwhere fid='$fid' AND invisible='0'");
		if($db->num_rows($query)) {
			$whosonlinestatus = 1;
			while($online = $db->fetch_array($query)) {
				if($online['uid']) {
					$online['icon'] = isset($_DCACHE['onlinelist'][$online['groupid']]) ? $_DCACHE['onlinelist'][$online['groupid']] : $_DCACHE['onlinelist'][0];
				} else {
					$online['icon'] = $_DCACHE['onlinelist'][7];
					$online['username'] = $_DCACHE['onlinelist']['guest'];
				}
				$online['action'] = $actioncode[$online['action']];
				$online['lastactivity'] = gmdate($timeformat, $online['lastactivity'] + ($timeoffset * 3600));
				$whosonline[] = $online;
			}
		}
		unset($online);
	}
} else {
	$whosonlinestatus = 0;
}

$pmlist = array();
$announcepm = 0;
if($_DCACHE['pmlist']) {
	$readapmids = !empty($_DCOOKIE['readapmid']) ? explode('D', $_DCOOKIE['readapmid']) : array();
	foreach($_DCACHE['pmlist'] as $pm) {
		if($discuz_uid && (empty($pm['groups']) || in_array($groupid, $pm['groups']))) {
			if(!in_array($pm['pmid'], $readapmids)) {
				$pm['announce'] = TRUE;
				$pmlist[] = $pm;
				$announcepm++;
			}
		}
	}
}
if($discuz_uid && $newpm) {
	require_once DISCUZ_ROOT.'./include/pmprompt.inc.php';
}

if(empty($filter)) {
	$threadcount = $forum['threads'];
} else {
	$query = $db->query("SELECT COUNT(*) FROM {$tablepre}threads WHERE fid='$fid' $filteradd AND displayorder>='0'");
	$threadcount = $db->result($query, 0);
}
$thisgid = $forum['type'] == 'forum' ? $forum['fup'] : $_DCACHE['forums'][$forum['fup']]['fup'];
if($globalstick) {
	$stickytids = $_DCACHE['globalstick']['global']['tids'].(empty($_DCACHE['globalstick']['categories'][$thisgid]['count']) ? '' : ','.$_DCACHE['globalstick']['categories'][$thisgid]['tids']);

	$stickycount = $_DCACHE['globalstick']['global']['count'] + $_DCACHE['globalstick']['categories'][$thisgid]['count'];
} else {
	$stickycount = $stickytids = 0;
}

$filterbool = !empty($filter) && in_array($filter, array('digest', 'type', 'activity', 'poll', 'trade', 'reward', 'debate', 'video'));
$threadcount += $filterbool ? 0 : $stickycount;
$multipage = multi($threadcount, $tpp, $page, "forumdisplay.php?fid=$fid$forumdisplayadd", $threadmaxpages);
$extra = rawurlencode("page=$page$forumdisplayadd");

$separatepos = 0;
$threadlist = array();
$colorarray = array('', 'red', 'orange', 'yellow', 'green', 'cyan', 'blue', 'purple', 'gray');

$displayorderadd = !$filterbool && $stickycount ? 't.displayorder IN (0, 1)' : 't.displayorder IN (0, 1, 2, 3)';

if(($start_limit && $start_limit > $stickycount) || !$stickycount || $filterbool) {

	$querysticky = '';
	$query = $db->query("SELECT t.* FROM {$tablepre}threads t
		WHERE t.fid='$fid' $filteradd AND $displayorderadd
		ORDER BY t.displayorder DESC, t.$orderby $ascdesc
		LIMIT ".($filterbool ? $start_limit : $start_limit - $stickycount).", $tpp");

} else {

	$querysticky = $db->query("SELECT t.* FROM {$tablepre}threads t
		WHERE t.tid IN ($stickytids) AND t.displayorder IN (2, 3)
		ORDER BY displayorder DESC, $orderby $ascdesc
		LIMIT $start_limit, ".($stickycount - $start_limit < $tpp ? $stickycount - $start_limit : $tpp));

	if($tpp - $stickycount + $start_limit > 0) {
		$query = $db->query("SELECT t.* FROM {$tablepre}threads t
			WHERE t.fid='$fid' $filteradd AND $displayorderadd
			ORDER BY displayorder DESC, $orderby $ascdesc
			LIMIT ".($tpp - $stickycount + $start_limit));
	} else {
		$query = '';
	}

}

if($page < 4 && !(empty($_DCACHE['floatthreads']['categories'][$thisgid]) && empty($_DCACHE['floatthreads']['forums'][$fid]))) {
	$queryfloat = $db->query("SELECT t.* FROM {$tablepre}threads t
		WHERE t.tid IN (".(!empty($_DCACHE['floatthreads']['categories'][$thisgid]) ? $_DCACHE['floatthreads']['categories'][$thisgid] : 0).','.(!empty($_DCACHE['floatthreads']['forums'][$fid]) ? $_DCACHE['floatthreads']['forums'][$fid] : 0).") AND t.displayorder IN (4, 5)
		ORDER BY displayorder DESC");
} else {
	$queryfloat = '';
}

$ppp = $forum['threadcaches'] && !$discuz_uid ? $_DCACHE['settings']['postperpage'] : $ppp;
$iscircle = $supe['status'] && $supe['circlestatus'] && $forum['status'] == 2 ? 1 : 0;
if($iscircle) {
	unset($forum['threadtypes']);
}
$sgids = array();

while(($querysticky && $thread = $db->fetch_array($querysticky)) || ($query && $thread = $db->fetch_array($query)) || ($queryfloat && $thread = $db->fetch_array($queryfloat))) {
	$thread['icon'] = isset($_DCACHE['icons'][$thread['iconid']]) ? '<img src="images/icons/'.$_DCACHE['icons'][$thread['iconid']].'" alt="Icon'.$thread['iconid'].'" class="icon" />' : '&nbsp;';
	$thread['lastposterenc'] = rawurlencode($thread['lastposter']);

	$thread['typeid'] = $thread['typeid'] && !empty($forum['threadtypes']['prefix']) && isset($forum['threadtypes']['types'][$thread['typeid']]) ?
		'<em>[<a href="forumdisplay.php?fid='.$fid.'&amp;filter=type&amp;typeid='.$thread['typeid'].'">'.$forum['threadtypes']['types'][$thread['typeid']].'</a>]</em>' : '';

	$thread['multipage'] = '';
	$topicposts = $thread['special'] ? $thread['replies'] : $thread['replies'] + 1;
	$thread['special'] == 3 && $thread['price'] < 0 && $thread['replies']--;
	if($topicposts > $ppp) {
		$pagelinks = '';
		$topicpages = ceil($topicposts / $ppp);
		for($i = 1; $i <= 6 && $i <= $topicpages; $i++) {
			$pagelinks .= "<a href=\"viewthread.php?tid=$thread[tid]&amp;extra=$extra&amp;page=$i\">$i</a> ";
		}
		if($topicpages > 6) {
			$pagelinks .= " .. <a href=\"viewthread.php?tid=$thread[tid]&amp;extra=$extra&amp;page=$topicpages\">$topicpages</a> ";
		}
		$thread['multipage'] = ' &nbsp; '.$pagelinks;
	}

	if($thread['highlight']) {
		$string = sprintf('%02d', $thread['highlight']);
		$stylestr = sprintf('%03b', $string[0]);

		$thread['highlight'] = ' style="';
		$thread['highlight'] .= $stylestr[0] ? 'font-weight: bold;' : '';
		$thread['highlight'] .= $stylestr[1] ? 'font-style: italic;' : '';
		$thread['highlight'] .= $stylestr[2] ? 'text-decoration: underline;' : '';
		$thread['highlight'] .= $string[1] ? 'color: '.$colorarray[$string[1]] : '';
		$thread['highlight'] .= '"';
	} else {
		$thread['highlight'] = '';
	}

	$thread['moved'] = 0;
	if($thread['closed'] || ($forum['autoclose'] && $timestamp - $thread[$closedby] > $forum['autoclose'])) {
		$thread['new'] = 0;
		if($thread['closed'] > 1) {
			$thread['moved'] = $thread['tid'];
			$thread['tid'] = $thread['closed'];
			$thread['replies'] = '-';
			$thread['views'] = '-';
		}
		$thread['folder'] = 'lock';
	} else {
		$thread['folder'] = 'common';
		if($lastvisit < $thread['lastpost'] && (empty($_DCOOKIE['oldtopics']) || strpos($_DCOOKIE['oldtopics'], 'D'.$thread['tid'].'D') === FALSE)) {
			$thread['new'] = 1;
			$thread['folder'] = 'new';
		} else {
			$thread['new'] = 0;
		}
		if($thread['replies'] > $thread['views']) {
			$thread['views'] = $thread['replies'];
		}
		if($thread['replies'] >= $hottopic) {
			$thread['folder'] = 'hot';
		}
	}

	$thread['dateline'] = gmdate($dateformat, $thread['dateline'] + $timeoffset * 3600);
	$thread['lastpost'] = gmdate("$dateformat $timeformat", $thread['lastpost'] + $timeoffset * 3600);

	if(in_array($thread['displayorder'], array(1, 2, 3))) {
		$thread['id'] = 'stickthread_'.$thread['tid'];
		$separatepos++;
	} elseif(in_array($thread['displayorder'], array(4, 5))) {
		$thread['id'] = 'floatthread_'.$thread['tid'];
	} else {
		$thread['id'] = 'normalthread_'.$thread['tid'];
	}

	$iscircle && $thread['sgid'] && $sgids[] = $thread['sgid'];
	$threadlist[] = $thread;

}

$separatepos = $separatepos ? $separatepos + 1 : ($announcement ? 1 : 0);

$visitedforums = $visitedforums ? visitedforums() : '';
$forumselect = $forummenu = '';
$typeselect = typeselect($typeid, '', 'onchange="if(this.options[this.selectedIndex].className) {this.form.action=this.form.action + \'&previewpost=yes&showpreview=no\';this.form.submit();}"');

$usesigcheck = $discuz_uid && $sigstatus ? 'checked="checked"' : '';
$allowpost = (!$forum['postperm'] && $allowpost) || ($forum['postperm'] && forumperm($forum['postperm'])) || !empty($forum['allowpost']);

$showpoll = $showtrade = $showreward = $showactivity = $showdebate = $showvideo = 0;
if($forum['allowpostspecial']) {
	$showpoll = $forum['allowpostspecial'] & 1;
	$showtrade = $forum['allowpostspecial'] & 2;
	$showreward = isset($extcredits[$creditstrans]) && ($forum['allowpostspecial'] & 4);
	$showactivity = $forum['allowpostspecial'] & 8;
	$showdebate = $forum['allowpostspecial'] & 16;
	$showvideo = ($forum['allowpostspecial'] & 32) && $videoopen;
}

if($allowpost) {
	$allowpostpoll = $allowpostpoll && $showpoll;
	$allowposttrade = $allowposttrade && $showtrade;
	$allowpostreward = $allowpostreward && $showreward;
	$allowpostactivity = $allowpostactivity && $showactivity;
	$allowpostdebate = $allowpostdebate && $showdebate;
	$allowpostvideo = $allowpostvideo && $showvideo;
}

$forumselect = $forummenu = '';

if($forumjump) {
	if($jsmenu[1]) {
		$forummenu = forumselect(FALSE, 1);
	} else {
		$forumselect = forumselect();
	}
}

if($iscircle) {
        require_once DISCUZ_ROOT.'./include/supesite_circle.inc.php';
}

include template('forumdisplay');

?>