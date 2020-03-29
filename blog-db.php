<?php
/*
	Blog Post by Jackson Siro
	https://www.github.com/jacksiro/Q2A-Blog-Post-Plugin

	Description: Basic and Database functions for the blog post plugin

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	//CATEGORY FUNCTIONS
	function bp_db_cat_create($parentid, $title, $tags)
	{
		$lastpos = bp_db_cat_last_pos($parentid);

		qa_db_query_sub(
			'INSERT INTO ^blog_cats (parentid, title, tags, position) VALUES (#, $, $, #)',
			$parentid, $title, $tags, 1 + $lastpos
		);

		$catid = qa_db_last_insert_id();

		bp_db_cats_recalc_backpaths($catid);

		return $catid;
	}

	function bp_db_cats_recalc_backpaths($firstcatid, $lastcatid = null)
	{
		if (!isset($lastcatid))
			$lastcatid = $firstcatid;

		qa_db_query_sub(
			"UPDATE ^blog_cats AS x, (SELECT cat1.catid, CONCAT_WS('/', cat1.tags, cat2.tags, cat3.tags, cat4.tags) AS backpath FROM ^blog_cats AS cat1 LEFT JOIN ^blog_cats AS cat2 ON cat1.parentid=cat2.catid LEFT JOIN ^blog_cats AS cat3 ON cat2.parentid=cat3.catid LEFT JOIN ^blog_cats AS cat4 ON cat3.parentid=cat4.catid WHERE cat1.catid BETWEEN # AND #) AS a SET x.backpath=a.backpath WHERE x.catid=a.catid",
			$firstcatid, $lastcatid // requires QA_CATEGORY_DEPTH=4
		);
	}

	function bp_db_cat_last_pos($parentid)
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COALESCE(MAX(position), 0) FROM ^blog_cats WHERE parentid<=>#',
			$parentid
		));
	}

	function bp_db_cat_child_depth($catid)
	{
		$result = qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT COUNT(child1.catid) AS count1, COUNT(child2.catid) AS count2, COUNT(child3.catid) AS count3 FROM ^blog_cats AS child1 LEFT JOIN ^blog_cats AS child2 ON child2.parentid=child1.catid LEFT JOIN ^blog_cats AS child3 ON child3.parentid=child2.catid WHERE child1.parentid=#;', // requires QA_CATEGORY_DEPTH=4
			$catid
		));

		for ($depth = QA_CATEGORY_DEPTH - 1; $depth >= 1; $depth--)
			if ($result['count' . $depth])
				return $depth;

		return 0;
	}

	function bp_db_cat_rename($catid, $title, $tags)
	{
		qa_db_query_sub(
			'UPDATE ^blog_cats SET title=$, tags=$ WHERE catid=#',
			$title, $tags, $catid
		);

		bp_db_cats_recalc_backpaths($catid); // may also require recalculation of its offspring's backpaths
	}

	function bp_db_cat_set_content($catid, $content)
	{
		qa_db_query_sub(
			'UPDATE ^blog_cats SET content=$ WHERE catid=#',
			$content, $catid
		);
	}

	function bp_db_cat_get_parent($catid)
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT parentid FROM ^blog_cats WHERE catid=#',
			$catid
		));
	}

	function bp_db_cat_set_position($catid, $newposition)
	{
		qa_db_ordered_move('blog_cats', 'catid', $catid, $newposition,
			qa_db_apply_sub('parentid<=>#', array(bp_db_cat_get_parent($catid))));
	}

	function bp_db_cat_set_parent($catid, $newparentid)
	{
		$oldparentid = bp_db_cat_get_parent($catid);

		if (strcmp($oldparentid, $newparentid)) { // if we're changing parent, move to end of old parent, then end of new parent
			$lastpos = bp_db_cat_last_pos($oldparentid);

			qa_db_ordered_move('blog_cats', 'catid', $catid, $lastpos, qa_db_apply_sub('parentid<=>#', array($oldparentid)));

			$lastpos = bp_db_cat_last_pos($newparentid);

			qa_db_query_sub(
				'UPDATE ^blog_cats SET parentid=#, position=# WHERE catid=#',
				$newparentid, 1 + $lastpos, $catid
			);
		}
	}

	function bp_db_cat_reassign($catid, $reassignid)
	{
		qa_db_query_sub('UPDATE ^blog_posts SET catid=# WHERE catid<=>#', $reassignid, $catid);
	}

	function bp_db_cat_delete($catid)
	{
		qa_db_ordered_delete('blog_cats', 'catid', $catid,
			qa_db_apply_sub('parentid<=>#', array(bp_db_cat_get_parent($catid))));
	}

	function bp_db_cat_slug_to_id($parentid, $slug)
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT catid FROM ^blog_cats WHERE parentid<=># AND tags=$',
			$parentid, $slug
		), true);
	}

	function bp_db_count_cats()
	{
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) FROM ^blog_cats'
		));
	}

	function bp_db_count_catid_ps($catid)
	{
		return qa_db_read_one_value(qa_db_query_sub(
			"SELECT COUNT(*) FROM ^blog_posts WHERE catid<=># AND type='P'",
			$catid
		));
	}

	//USER FUNCTIONS
	function bp_db_user_create($userid)
	{
		qa_db_query_sub(userid, lastposted, pcount, points, 
			'INSERT INTO ^blog_users (userid, pcount, points, lastposted) ' .
			'VALUES ($, $, $, NOW())',
			$userid, 1, 3
		);
	}
	
	function bp_db_user_find_by_handle($userid)
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT userid FROM ^users WHERE userid=$',
			$userid
		));
	}
	
	function bp_db_user_account_selectspec($useridhandle, $isuserid)
	{
		return array(
			'columns' => array(
				'^users.userid', 'passsalt', 'passcheck' => 'HEX(passcheck)', 'passhash', 'email', 'level', 'emailcode', 'handle',
				'created' => 'UNIX_TIMESTAMP(created)', 'sessioncode', 'sessionsource', 'flags', 'loggedin' => 'UNIX_TIMESTAMP(loggedin)',
				'loginip', 'written' => 'UNIX_TIMESTAMP(written)', 'writeip',
				'avatarblobid' => 'BINARY avatarblobid', // cast to BINARY due to MySQL bug which renders it signed in a union
				'avatarwidth', 'avatarheight', 'points', 'wallposts',
			),

			'source' => '^users LEFT JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE ^users.' . ($isuserid ? 'userid' : 'handle') . '=$',
			'arguments' => array($useridhandle),
			'single' => true,
		);
	}
	
	function bp_db_user_points_selectspec($identifier, $isuserid = QA_FINAL_EXTERNAL_USERS)
	{
		return array(
			'columns' => array('points', 'qposts', 'aposts', 'cposts', 'aselects', 'aselecteds', 'qupvotes', 'qdownvotes', 'aupvotes', 'adownvotes', 'qvoteds', 'avoteds', 'upvoteds', 'downvoteds', 'bonus'),
			'source' => '^userpoints WHERE userid=' . ($isuserid ? '$' : '(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)'),
			'arguments' => array($identifier),
			'single' => true,
		);
	}
	
	function bp_db_user_recent_ps_selectspec($voteuserid, $identifier, $count = null, $start = 0)
	{
		$count = isset($count) ? min($count, QA_DB_RETRIEVE_PS_AS) : QA_DB_RETRIEVE_PS_AS;

		$selectspec = bp_db_posts_basic_selectspec($voteuserid);

		$selectspec['source'] .= " WHERE ^blog_posts.userid=" . (QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)") . " AND type='P' ORDER BY ^blog_posts.created DESC LIMIT #,#";
		array_push($selectspec['arguments'], $identifier, $start, $count);
		$selectspec['sortdesc'] = 'created';

		return $selectspec;
	}

	//POST FUNCTIONS
	function qa_bp_post_find_by_permalink($permalink)
	{
		return qa_db_read_all_values(qa_db_query_sub(
			"SELECT postid FROM ^blog_posts WHERE permalink LIKE '%" . $permalink . "%'"
		));
	}

	function bp_db_post_create($type, $userid, $cookieid, $ip, $title, $content, $permalink, $format, $tagstring, $notify, $catid = null, $name = null)
	{
		qa_db_query_sub(
			'INSERT INTO ^blog_posts (catid, type, userid, cookieid, createip, title, content, permalink, format, tags, notify, name, created) ' .
			'VALUES (#, $, #, $, #, UNHEX($), $, $, $, $, $, $, NOW())',
			$catid, $type, $userid, $cookieid, bin2hex(@inet_pton($ip)), $title, $content, $permalink, $format, $tagstring, $notify, $name
		);
		return qa_db_last_insert_id();
	}

	function bp_db_post_get_cat_path($postid)
	{
		return qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT catid, catidpath1, catidpath2, catidpath3 FROM ^blog_posts WHERE postid=#',
			$postid
		)); // requires QA_CATEGORY_DEPTH=4
	}

		
	function bp_db_bpqueuedcount_update()
	{
		if (qa_should_update_counts()) {
			qa_db_query_sub(
				"INSERT INTO ^options (title, content) " .
				"SELECT 'cache_bpqueuedcount', COUNT(*) FROM ^blog_posts " .
				"WHERE type IN ('P_QUEUED', 'C_QUEUED') " .
				"ON DUPLICATE KEY UPDATE content = VALUES(content)"
			);
		}
	}

	function bp_db_catslugs_sql_args($categoryslugs, &$arguments)
	{
		if (!is_array($categoryslugs)) {
			// accept old-style string arguments for one category deep
			$categoryslugs = strlen($categoryslugs) ? array($categoryslugs) : array();
		}

		$levels = count($categoryslugs);

		if ($levels > 0 && $levels <= QA_CATEGORY_DEPTH) {
			$arguments[] = qa_db_slugs_to_backpath($categoryslugs);
			return (($levels == QA_CATEGORY_DEPTH) ? 'catid' : ('catidpath' . $levels)) . '=(SELECT catid FROM ^blog_cats WHERE backpath=$ LIMIT 1) AND ';
		}

		return '';
	}

	function bp_db_cat_path_pcount_update($path)
	{
		bp_db_ifcat_pcount_update($path['catid']); // requires QA_CATEGORY_DEPTH=4
		bp_db_ifcat_pcount_update($path['catidpath1']);
		bp_db_ifcat_pcount_update($path['catidpath2']);
		bp_db_ifcat_pcount_update($path['catidpath3']);
	}

	function bp_db_ifcat_pcount_update($catid)
	{
		if (qa_should_update_counts() && isset($catid)) {
			qa_db_query_sub(
				"UPDATE ^blog_cats SET pcount=GREATEST( (SELECT COUNT(*) FROM ^blog_posts WHERE catid=# AND type='P'), (SELECT COUNT(*) FROM ^blog_posts WHERE catidpath1=# AND type='P'), (SELECT COUNT(*) FROM ^blog_posts WHERE catidpath2=# AND type='P'), (SELECT COUNT(*) FROM ^blog_posts WHERE catidpath3=# AND type='P') ) WHERE catid=#",
				$catid, $catid, $catid, $catid, $catid
			); // requires QA_CATEGORY_DEPTH=4
		}
	}

	function bp_db_bpcount_update()
	{
		if (qa_should_update_counts()) {
			qa_db_query_sub(
				"INSERT INTO ^options (title, content) " .
				"SELECT 'cache_bpcount', COUNT(*) FROM ^blog_posts " .
				"WHERE type = 'P' " .
				"ON DUPLICATE KEY UPDATE content = VALUES(content)"
			);
		}
	}

	function bp_db_bccount_update()
	{
		if (qa_should_update_counts()) {
			qa_db_query_sub(
				"INSERT INTO ^options (title, content) " .
				"SELECT 'cache_bccount', COUNT(*) FROM ^blog_posts " .
				"WHERE type = 'C' " .
				"ON DUPLICATE KEY UPDATE content = VALUES(content)"
			);
		}
	}

	//SELECTION FUNCTIONS
	function bp_db_full_cat_selectspec($slugsorid, $isid)
	{
		if ($isid) {
			$identifiersql = 'catid=#';
		} else {
			$identifiersql = 'backpath=$';
			$slugsorid = qa_db_slugs_to_backpath($slugsorid);
		}

		return array(
			'columns' => array('catid', 'parentid', 'title', 'tags', 'pcount', 'content', 'backpath'),
			'source' => '^blog_cats WHERE ' . $identifiersql,
			'arguments' => array($slugsorid),
			'single' => 'true',
		);
	}

	function bp_db_full_post_selectspec($voteuserid, $postid)
	{
		$selectspec = bp_db_posts_basic_selectspec($voteuserid, true);

		$selectspec['source'] .= " WHERE ^blog_posts.postid=#";
		$selectspec['arguments'][] = $postid;
		$selectspec['single'] = true;

		return $selectspec;
	}

	function bp_db_full_child_posts_selectspec($voteuserid, $parentid)
	{
		$selectspec = bp_db_posts_basic_selectspec($voteuserid, true);

		$selectspec['source'] .= " WHERE ^blog_posts.parentid=#";
		$selectspec['arguments'][] = $parentid;

		return $selectspec;
	}

	function bp_db_full_c_child_posts_selectspec($voteuserid, $articleid)
	{
		$selectspec = bp_db_posts_basic_selectspec($voteuserid, true);

		$selectspec['source'] .= " JOIN ^blog_posts AS parents ON ^blog_posts.parentid=parents.postid WHERE parents.parentid=# AND LEFT(parents.type, 1)='C'";
		$selectspec['arguments'][] = $articleid;
		return $selectspec;
	}

	function bp_db_post_parent_p_selectspec($postid)
	{
		$selectspec = bp_db_posts_basic_selectspec();

		$selectspec['source'] .= " WHERE ^blog_posts.postid=(SELECT IF(LEFT(parent.type, 1)='C', parent.parentid, parent.postid) FROM ^blog_posts AS child LEFT JOIN ^blog_posts AS parent ON parent.postid=child.parentid WHERE child.postid=# AND parent.type IN('P','C'))";
		$selectspec['arguments'] = array($postid);
		$selectspec['single'] = true;

		return $selectspec;
	}
	
	function bp_db_post_close_post_selectspec($articleid)
	{
		$selectspec = bp_db_posts_basic_selectspec(null, true);

		$selectspec['source'] .= " WHERE ^blog_posts.postid=(SELECT closedbyid FROM ^blog_posts WHERE postid=#)";
		$selectspec['arguments'] = array($articleid);
		$selectspec['single'] = true;

		return $selectspec;
	}

	function bp_db_post_duplicates_selectspec($articleid)
	{
		$selectspec = bp_db_posts_basic_selectspec(null, true);

		$selectspec['source'] .= " WHERE ^blog_posts.closedbyid=#";
		$selectspec['arguments'] = array($articleid);

		return $selectspec;
	}

	function bp_db_post_meta_selectspec($postid, $title)
	{
		$selectspec = array(
			'columns' => array('title', 'content'),
			'source' => "^postmetas WHERE postid=# AND " . (is_array($title) ? "title IN ($)" : "title=$"),
			'arguments' => array($postid, $title),
			'arrayvalue' => 'content',
		);

		if (is_array($title)) {
			$selectspec['arraykey'] = 'title';
		} else {
			$selectspec['single'] = true;
		}

		return $selectspec;
	}

	function bp_db_posts_basic_selectspec($voteuserid = null, $full = false, $user = true)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$selectspec = array(
			'columns' => array(
				'^blog_posts.postid', '^blog_posts.catid', '^blog_posts.type', 'basetype' => 'LEFT(^blog_posts.type, 1)',
				'hidden' => "INSTR(^blog_posts.type, '_HIDDEN')>0", 'queued' => "INSTR(^blog_posts.type, '_QUEUED')>0",
				'^blog_posts.ccount', '^blog_posts.selchildid', '^blog_posts.closedbyid', '^blog_posts.upvotes', '^blog_posts.downvotes', '^blog_posts.netvotes', '^blog_posts.views', '^blog_posts.hotness',
				'^blog_posts.flagcount', '^blog_posts.title', '^blog_posts.permalink', 'summary' => '^blog_posts.content','^blog_posts.tags', 'created' => 'UNIX_TIMESTAMP(^blog_posts.created)', '^blog_posts.name',
				'categoryname' => '^blog_cats.title', 'categorybackpath' => "^blog_cats.backpath",
				'categoryids' => "CONCAT_WS(',', ^blog_posts.catidpath1, ^blog_posts.catidpath2, ^blog_posts.catidpath3, ^blog_posts.catid)",
			),

			'arraykey' => 'postid',
			'source' => '^blog_posts LEFT JOIN ^blog_cats ON ^blog_cats.catid=^blog_posts.catid',
			'arguments' => array(),
		);

		if (isset($voteuserid)) {
			require_once QA_INCLUDE_DIR . 'app/updates.php';

			$selectspec['columns']['uservote'] = '^uservotes.vote';
			$selectspec['columns']['userflag'] = '^uservotes.flag';
			$selectspec['columns']['userfavoriteq'] = '^userfavorites.entityid<=>^blog_posts.postid';
			$selectspec['source'] .= ' LEFT JOIN ^uservotes ON ^blog_posts.postid=^uservotes.postid AND ^uservotes.userid=$';
			$selectspec['source'] .= ' LEFT JOIN ^userfavorites ON ^blog_posts.postid=^userfavorites.entityid AND ^userfavorites.userid=$ AND ^userfavorites.entitytype=$';
			array_push($selectspec['arguments'], $voteuserid, $voteuserid, QA_ENTITY_QUESTION);
		}

		if ($full) {
			$selectspec['columns']['content'] = '^blog_posts.content';
			$selectspec['columns']['notify'] = '^blog_posts.notify';
			$selectspec['columns']['updated'] = 'UNIX_TIMESTAMP(^blog_posts.updated)';
			$selectspec['columns']['updatetype'] = '^blog_posts.updatetype';
			$selectspec['columns'][] = '^blog_posts.format';
			$selectspec['columns'][] = '^blog_posts.lastuserid';
			$selectspec['columns']['lastip'] = '^blog_posts.lastip';
			$selectspec['columns'][] = '^blog_posts.parentid';
			$selectspec['columns']['lastviewip'] = '^blog_posts.lastviewip';
		}

		if ($user) {
			$selectspec['columns'][] = '^blog_posts.userid';
			$selectspec['columns'][] = '^blog_posts.cookieid';
			$selectspec['columns']['createip'] = '^blog_posts.createip';
			$selectspec['columns'][] = '^userpoints.points';

			if (!QA_FINAL_EXTERNAL_USERS) {
				$selectspec['columns'][] = '^users.flags';
				$selectspec['columns'][] = '^users.level';
				$selectspec['columns']['email'] = '^users.email';
				$selectspec['columns']['handle'] = '^users.handle';
				$selectspec['columns']['avatarblobid'] = 'BINARY ^users.avatarblobid';
				$selectspec['columns'][] = '^users.avatarwidth';
				$selectspec['columns'][] = '^users.avatarheight';
				$selectspec['source'] .= ' LEFT JOIN ^users ON ^blog_posts.userid=^users.userid';

				if ($full) {
					$selectspec['columns']['lasthandle'] = 'lastusers.handle';
					$selectspec['source'] .= ' LEFT JOIN ^users AS lastusers ON ^blog_posts.lastuserid=lastusers.userid';
				}
			}

			$selectspec['source'] .= ' LEFT JOIN ^userpoints ON ^blog_posts.userid=^userpoints.userid';
		}

		return $selectspec;
	}


	function bp_db_cat_nav_selectspec($slugsorid, $isid, $ispostid = false, $full = false)
	{
		if ($isid) {
			if ($ispostid) {
				$identifiersql = 'catid=(SELECT catid FROM ^blog_posts WHERE postid=#)';
			} else {
				$identifiersql = 'catid=#';
			}
		} else {
			$identifiersql = 'backpath=$';
			$slugsorid = qa_db_slugs_to_backpath($slugsorid);
		}

		$parentselects = array( // requires QA_CATEGORY_DEPTH=4
			'SELECT NULL AS parentkey', // top level
			'SELECT grandparent.parentid FROM ^blog_cats JOIN ^blog_cats AS parent ON ^blog_cats.parentid=parent.catid JOIN ^blog_cats AS grandparent ON parent.parentid=grandparent.catid WHERE ^blog_cats.' . $identifiersql, // 2 gens up
			'SELECT parent.parentid FROM ^blog_cats JOIN ^blog_cats AS parent ON ^blog_cats.parentid=parent.catid WHERE ^blog_cats.' . $identifiersql,
			// 1 gen up
			'SELECT parentid FROM ^blog_cats WHERE ' . $identifiersql, // same gen
			'SELECT catid FROM ^blog_cats WHERE ' . $identifiersql, // gen below
		);

		$columns = array(
			'parentid' => '^blog_cats.parentid',
			'title' => '^blog_cats.title',
			'tags' => '^blog_cats.tags',
			'pcount' => '^blog_cats.pcount',
			'position' => '^blog_cats.position',
		);

		if ($full) {
			foreach ($columns as $alias => $column) {
				$columns[$alias] = 'MAX(' . $column . ')';
			}

			$columns['childcount'] = 'COUNT(child.catid)';
			$columns['content'] = 'MAX(^blog_cats.content)';
			$columns['backpath'] = 'MAX(^blog_cats.backpath)';
		}

		array_unshift($columns, '^blog_cats.catid');
		$selectspec = array(
			'columns' => $columns,
			'source' => '^blog_cats JOIN (' . implode(' UNION ', $parentselects) . ') y ON ^blog_cats.parentid<=>parentkey' .
				($full ? ' LEFT JOIN ^blog_cats AS child ON child.parentid=^blog_cats.catid GROUP BY ^blog_cats.catid' : '') .
				' ORDER BY ^blog_cats.position',
			'arguments' => array($slugsorid, $slugsorid, $slugsorid, $slugsorid),
			'arraykey' => 'catid',
			'sortasc' => 'position',
		);
		return $selectspec;
	}

	function bp_db_cat_sub_selectspec($catid)
	{
		return array(
			'columns' => array('catid', 'title', 'tags', 'pcount', 'position'),
			'source' => '^blog_cats WHERE parentid<=># ORDER BY position',
			'arguments' => array($catid),
			'arraykey' => 'catid',
			'sortasc' => 'position',
		);
	}


	function bp_db_slugs_to_cat_id_selectspec($slugs)
	{
		return array(
			'columns' => array('catid'),
			'source' => '^blog_cats WHERE backpath=$',
			'arguments' => array(qa_db_slugs_to_backpath($slugs)),
			'arrayvalue' => 'catid',
			'single' => true,
		);
	}

	function bp_db_ps_selectspec($voteuserid, $sort, $start, $categoryslugs = null, $createip = null, $specialtype = false, $full = false, $count = null)
	{
		if ($specialtype == 'P' || $specialtype == 'P_QUEUED') $type = $specialtype;
		else $type = $specialtype ? 'P_HIDDEN' : 'P'; // for backwards compatibility
		
		$count = isset($count) ? min($count, QA_DB_RETRIEVE_PS_AS) : QA_DB_RETRIEVE_PS_AS;

		switch ($sort) {
			case 'ccount':
			case 'flagcount':
			case 'netvotes':
			case 'views':
				$sortsql = 'ORDER BY ^blog_posts.' . $sort . ' DESC, ^blog_posts.created DESC';
				break;

			case 'created':
			case 'hotness':
				$sortsql = 'ORDER BY ^blog_posts.' . $sort . ' DESC';
				break;

			default:
				qa_fatal_error('bp_db_ps_selectspec() called with illegal sort value');
				break;
		}

		$selectspec = bp_db_posts_basic_selectspec($voteuserid, $full);
		$selectspec['source'] .=
			" JOIN (SELECT postid FROM ^blog_posts WHERE " .
			bp_db_catslugs_sql_args($categoryslugs, $selectspec['arguments']) .
			(isset($createip) ? "createip=UNHEX($) AND " : "") .
			"type=$ " . $sortsql . " LIMIT #,#) y ON ^blog_posts.postid=y.postid";

		if (isset($createip)) {
			$selectspec['arguments'][] = bin2hex(@inet_pton($createip));
		}

		array_push($selectspec['arguments'], $type, $start, $count);

		$selectspec['sortdesc'] = $sort;

		return $selectspec;
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/