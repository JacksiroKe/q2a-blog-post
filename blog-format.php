<?php
/*
	Blog Post by Jack Siro
	https://github.com/JacksiroKe/q2a-blog-post

	Description: Basic and Database functions for the blog post plugin

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	function bp_set_up_cat_field(&$qa_content, &$field, $fieldname, $navcategories, $catid, $allownone, $allownosub, $maxdepth = null, $excludecatid = null)
	{
		$pathcategories = bp_cat_path($navcategories, $catid);

		$startpath = '';
		foreach ($pathcategories as $category)
			$startpath .= '/' . $category['catid'];

		if (isset($maxdepth))
			$maxdepth = min(QA_CATEGORY_DEPTH, $maxdepth);
		else
			$maxdepth = QA_CATEGORY_DEPTH;

		$qa_content['script_onloads'][] = sprintf('qa_cat_select(%s, %s);', qa_js($fieldname), qa_js($startpath));

		$qa_content['script_var']['qa_cat_exclude'] = $excludecatid;
		$qa_content['script_var']['qa_cat_allownone'] = (int)$allownone;
		$qa_content['script_var']['qa_cat_allownosub'] = (int)$allownosub;
		$qa_content['script_var']['qa_cat_maxdepth'] = $maxdepth;

		$field['type'] = 'select';
		$field['tags'] = sprintf('name="%s_0" id="%s_0" onchange="qa_cat_select(%s);"', $fieldname, $fieldname, qa_js($fieldname));
		$field['options'] = array();

		// create the menu that will be shown if Javascript is disabled

		if ($allownone)
			$field['options'][''] = qa_lang_html('main/no_category'); // this is also copied to first menu created by Javascript

		$keycatids = array();

		if ($allownosub) {
			$category = @$navcategories[$catid];

			$upcategory = @$navcategories[$category['parentid']]; // first get supercategories
			while (isset($upcategory)) {
				$keycatids[$upcategory['catid']] = true;
				$upcategory = @$navcategories[$upcategory['parentid']];
			}

			$keycatids = array_reverse($keycatids, true);

			$depth = count($keycatids); // number of levels above

			if (isset($category)) {
				$depth++; // to count category itself

				foreach ($navcategories as $navcategory) // now get siblings and self
					if (!strcmp($navcategory['parentid'], $category['parentid']))
						$keycatids[$navcategory['catid']] = true;
			}

			if ($depth < $maxdepth)
				foreach ($navcategories as $navcategory) // now get children, if not too deep
					if (!strcmp($navcategory['parentid'], $catid))
						$keycatids[$navcategory['catid']] = true;

		} else {
			$haschildren = false;

			foreach ($navcategories as $navcategory) {
				// check if it has any children
				if (!strcmp($navcategory['parentid'], $catid)) {
					$haschildren = true;
					break;
				}
			}

			if (!$haschildren)
				$keycatids[$catid] = true; // show this category if it has no children
		}

		foreach ($keycatids as $keycatid => $dummy)
			if (strcmp($keycatid, $excludecatid))
				$field['options'][$keycatid] = bp_cat_path_html($navcategories, $keycatid);

		$field['value'] = @$field['options'][$catid];
		$field['note'] =
			'<div id="' . $fieldname . '_note">' .
			'<noscript style="color:red;">' . qa_lang_html('article/category_js_note') . '</noscript>' .
			'</div>';
	}

	function bp_b_list_page_content($articles, $pagesize, $start, $count, $sometitle, $nonetitle,
		$navcategories, $catid, $categorypcount, $categorypathprefix, $feedpathprefix, $suggest,
		$pagelinkparams = null, $categoryparams = null, $dummy = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/format.php';
		require_once QA_INCLUDE_DIR . 'app/updates.php';

		$userid = qa_get_logged_in_userid();

		// Chop down to size, get user information for display
		if (isset($pagesize)) {
			$articles = array_slice($articles, 0, $pagesize);
		}

		$usershtml = qa_userids_handles_html(qa_any_get_userids_handles($articles));

		// Prepare content for theme
		$qa_content = qa_content_prepare(true, array_keys(bp_cat_path($navcategories, $catid)));

		$qa_content['q_list']['form'] = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'hidden' => array(
				'code' => qa_get_form_security_code('vote'),
			),
		);

		$qa_content['q_list']['qs'] = array();
		$custom_title = (strlen(qa_opt('bp_blog_title')) > 3) ? ' - ' .qa_opt('bp_blog_title') : '';
					
		if (count($articles)) {
			$qa_content['title'] = $sometitle .$custom_title;

			$defaults = qa_post_html_defaults('P');
			if (isset($categorypathprefix)) {
				$defaults['categorypathprefix'] = $categorypathprefix;
			}

			foreach ($articles as $article) {
				$fields = bp_any_to_p_html_fields($article, $userid, qa_cookie_get(), $usershtml, null, qa_post_html_options($article, $defaults));

				if (!empty($fields['raw']['closedbyid'])) {
					$fields['closed'] = array(
						'state' => qa_lang_html('main/closed'),
					);
				}

				$qa_content['q_list']['qs'][] = $fields;
			}
		} else {
			$qa_content['title'] = $nonetitle . $custom_title;
		}

		if (isset($userid) && isset($catid)) {
			$favoritemap = qa_get_favorite_non_qs_map();
			$categoryisfavorite = @$favoritemap['blogcat'][$navcategories[$catid]['backpath']];

			$qa_content['favorite'] = qa_favorite_form(QA_ENTITY_CATEGORY, $catid, $categoryisfavorite,
				qa_lang_sub($categoryisfavorite ? 'main/remove_x_favorites' : 'main/add_category_x_favorites', $navcategories[$catid]['title']));
		}

		if (isset($count) && isset($pagesize)) {
			$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'), $pagelinkparams);
		}

		$qa_content['canonical'] = qa_get_canonical();

		if (empty($qa_content['page_links'])) {
			$qa_content['suggest_next'] = $suggest;
		}

		if (qa_using_categories() && count($navcategories) && isset($categorypathprefix)) {
			$qa_content['navigation']['cat'] = bp_cat_navigation($navcategories, $catid, $categorypathprefix, $categorypcount, $categoryparams);
		}

		// set meta description on category pages
		if (!empty($navcategories[$catid]['content'])) {
			$qa_content['description'] = qa_html($navcategories[$catid]['content']);
		}

		if (isset($feedpathprefix) && (qa_opt('feed_per_category') || !isset($catid))) {
			$qa_content['feed'] = array(
				'url' => qa_path_html(qa_feed_request($feedpathprefix . (isset($catid) ? ('/' . bp_cat_path_request($navcategories, $catid)) : ''))),
				'label' => strip_tags($sometitle),
			);
		}

		return $qa_content;
	}

	function bp_any_to_p_html_fields($article, $userid, $cookieid, $usershtml, $dummy, $options)
	{
		if (isset($article['opostid']))
			$fields = bp_other_to_p_html_fields($article, $userid, $cookieid, $usershtml, null, $options);
		else
			$fields = bp_post_html_fields($article, $userid, $cookieid, $usershtml, null, $options);

		return $fields;
	}

	function bp_other_to_p_html_fields($article, $userid, $cookieid, $usershtml, $dummy, $options)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/updates.php';

		$fields = bp_post_html_fields($article, $userid, $cookieid, $usershtml, null, $options);

		switch ($article['obasetype'] . '-' . @$article['oupdatetype']) {
			case 'P-':
				$langstring = 'bp_lang/posted';
				break;

			case 'P-' . QA_UPDATE_VISIBLE:
				if (@$article['opersonal'])
					$langstring = $article['hidden'] ? 'misc/your_p_hidden' : 'misc/your_p_reshown';
				else
					$langstring = $article['hidden'] ? 'main/hidden' : 'main/reshown';
				break;

			case 'P-' . QA_UPDATE_CLOSED:
				if (@$article['opersonal'])
					$langstring = isset($article['closedbyid']) ? 'misc/your_q_closed' : 'misc/your_q_reopened';
				else
					$langstring = isset($article['closedbyid']) ? 'main/closed' : 'main/reopened';
				break;

			case 'P-' . QA_UPDATE_TAGS:
				$langstring = @$article['opersonal'] ? 'misc/your_q_retagged' : 'main/retagged';
				break;

			case 'P-' . QA_UPDATE_CATEGORY:
				$langstring = @$article['opersonal'] ? 'misc/your_q_recategorized' : 'main/recategorized';
				break;

			case 'C-':
				$langstring = @$article['opersonal'] ? 'misc/your_q_answered' : 'main/answered';
				break;

			case 'C-' . QA_UPDATE_SELECTED:
				$langstring = @$article['opersonal'] ? 'misc/your_a_selected' : 'main/answer_selected';
				break;

			case 'C-' . QA_UPDATE_VISIBLE:
				if (@$article['opersonal'])
					$langstring = $article['ohidden'] ? 'misc/your_a_hidden' : 'misc/your_a_reshown';
				else
					$langstring = $article['ohidden'] ? 'main/hidden' : 'main/answer_reshown';
				break;

			case 'C-':
				$langstring = 'main/commented';
				break;

			case 'C-' . QA_UPDATE_C_FOR_P:
				$langstring = @$article['opersonal'] ? 'misc/your_p_commented' : 'main/commented';
				break;

			case 'C-' . QA_UPDATE_FOLLOWS:
				$langstring = @$article['opersonal'] ? 'misc/your_c_followed' : 'main/commented';
				break;

			case 'C-' . QA_UPDATE_TYPE:
				$langstring = @$article['opersonal'] ? 'misc/your_c_moved' : 'main/comment_moved';
				break;

			case 'C-' . QA_UPDATE_VISIBLE:
				if (@$article['opersonal'])
					$langstring = $article['ohidden'] ? 'misc/your_c_hidden' : 'misc/your_c_reshown';
				else
					$langstring = $article['ohidden'] ? 'main/hidden' : 'main/comment_reshown';
				break;

			case 'C-' . QA_UPDATE_CONTENT:
				$langstring = @$article['opersonal'] ? 'misc/your_c_edited' : 'main/comment_edited';
				break;

			case 'P-' . QA_UPDATE_CONTENT:
			default:
				$langstring = @$article['opersonal'] ? 'misc/your_q_edited' : 'main/edited';
				break;
		}

		$fields['what'] = qa_lang_html($langstring);

		if (@$article['opersonal'])
			$fields['what_your'] = true;

		if ($article['obasetype'] != 'P' || @$article['oupdatetype'] == QA_UPDATE_FOLLOWS)
			$fields['what_url'] = bp_p_path_html($article['postid'], $article['created'], $article['title'], false, $article['obasetype'], $article['opostid']);

		if (@$options['contentview'] && !empty($article['ocontent'])) {
			$viewer = qa_load_viewer($article['ocontent'], $article['oformat']);

			$fields['content'] = $viewer->get_html($article['ocontent'], $article['oformat'], array(
				'blockwordspreg' => @$options['blockwordspreg'],
				'showurllinks' => @$options['showurllinks'],
				'linksnewwindow' => @$options['linksnewwindow'],
			));
		}

		if (@$options['whenview'])
			$fields['when'] = qa_when_to_html($article['otime'], @$options['fulldatedays']);

		if (@$options['whoview']) {
			$isbyuser = qa_post_is_by_user(array('userid' => $article['ouserid'], 'cookieid' => @$article['ocookieid']), $userid, $cookieid);

			$fields['who'] = qa_who_to_html($isbyuser, $article['ouserid'], $usershtml, @$options['ipview'] ? @inet_ntop(@$article['oip']) : null, false, @$article['oname']);
			if (isset($article['opoints'])) {
				if (@$options['pointsview'])
					$fields['who']['points'] = ($article['opoints'] == 1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
						: qa_lang_html_sub_split('main/x_points', qa_format_number($article['opoints'], 0, true));

				if (isset($options['pointstitle']))
					$fields['who']['title'] = qa_get_points_title_html($article['opoints'], $options['pointstitle']);
			}

			if (isset($article['olevel']))
				$fields['who']['level'] = qa_html(qa_user_level_string($article['olevel']));
		}

		unset($fields['flags']);
		if (@$options['flagsview'] && @$article['oflagcount']) {
			$fields['flags'] = ($article['oflagcount'] == 1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
				: qa_lang_html_sub_split('main/x_flags', $article['oflagcount']);
		}

		unset($fields['avatar']);
		if (@$options['avatarsize'] > 0) {
			if (QA_FINAL_EXTERNAL_USERS)
				$fields['avatar'] = qa_get_external_avatar_html($article['ouserid'], $options['avatarsize'], false);
			else
				$fields['avatar'] = qa_get_user_avatar_html($article['oflags'], $article['oemail'], $article['ohandle'],
					$article['oavatarblobid'], $article['oavatarwidth'], $article['oavatarheight'], $options['avatarsize']);
		}

		return $fields;
	}

	function bp_post_html_fields($post, $userid, $cookieid, $usershtml, $dummy, $options = array())
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/updates.php';

		if (isset($options['blockwordspreg']))
			require_once QA_INCLUDE_DIR . 'util/string.php';

		$fields = array('raw' => $post);

		// Useful stuff used throughout function
		$postid = $post['postid'];
		$isarticle = $post['basetype'] == 'P';
		$iscomment = $post['basetype'] == 'C';
		$isbyuser = qa_post_is_by_user($post, $userid, $cookieid);
		$anchor = urlencode(qa_anchor($post['basetype'], $postid));
		$elementid = isset($options['elementid']) ? $options['elementid'] : $anchor;
		$microdata = qa_opt('use_microdata') && !empty($options['contentview']);
		$isselected = @$options['isselected'];
		$favoritedview = @$options['favoritedview'];
		$favoritemap = $favoritedview ? qa_get_favorite_non_qs_map() : array();

		// High level information
		$fields['hidden'] = isset($post['hidden']) ? $post['hidden'] : null;
		$fields['queued'] = isset($post['queued']) ? $post['queued'] : null;
		$fields['tags'] = 'id="' . qa_html($elementid) . '"';

		$fields['classes'] = ($isarticle && $favoritedview && @$post['userfavoriteq']) ? 'qa-q-favorited' : '';
		if ($isarticle && isset($post['closedbyid']))
			$fields['classes'] = ltrim($fields['classes'] . ' qa-q-closed');

		if ($microdata) {
			if ($iscomment) {
				$fields['tags'] .= ' itemscope itemtype="http://schema.org/Comment"';
			}
		}

		// Question-specific stuff (title, URL, tags, answer count, category)

		if ($isarticle) {
			if (isset($post['title'])) {
				if (isset($options['blockwordspreg']))
					$post['title'] = qa_block_words_replace($post['title'], $options['blockwordspreg']);

				$fields['title'] = qa_html($post['title']);
				if ($microdata) {
					$fields['title'] = '<span itemprop="name">' . $fields['title'] . '</span>';
				}
				/*if (isset($post['score'])) // useful for setting match thresholds
					$fields['title'].=' <small>('.$post['score'].')</small>';*/
			} else $fields['title'] = 'Untitled Article';

			$fields['url'] = bp_p_request( $post['permalink'] );

			if (isset($post['summary'])) {
				$limit = strlen(qa_opt('bp_content_max')) ? qa_opt('bp_content_max') : 100;
				if (strlen($post['summary']) > $limit) 
					$fields['content'] = substr(strip_tags($post['summary']), 0, $limit). ' ...';
				else $fields['content'] = substr(strip_tags($post['summary']), 0, $limit);
			}

			$fields['q_tags'] = array();
			$tags = qa_tagstring_to_tags($post['tags']);
			foreach ($tags as $tag) {
				if (isset($options['blockwordspreg']) && count(qa_block_words_match_all($tag, $options['blockwordspreg']))) continue;
				$fields['q_tags'][] = bp_tag_html($tag, $microdata, @$favoritemap['tag'][qa_strtolower($tag)]);
			}

			$fields['views_raw'] = $post['views'];

			$fields['views'] = ($post['views'] == 1) ? qa_lang_html_sub_split('main/1_view', '1', '1') :
				qa_lang_html_sub_split('main/x_views', qa_format_number($post['views'], 0, true));
			
			$favoriteclass = '';
			
			if (is_array(@$favoritemap['category']) && count(@$favoritemap['category'])) {
				if (@$favoritemap['category'][$post['categorybackpath']]) {
					$favoriteclass = ' qa-cat-favorited';
				} else {
					foreach ($favoritemap['category'] as $categorybackpath => $dummy) {
						if (substr('/' . $post['categorybackpath'], -strlen($categorybackpath)) == $categorybackpath)
							$favoriteclass = ' qa-cat-parent-favorited';
					}
				}
			}

			$fields['where'] = qa_lang_html_sub_split('main/in_category_x',
				'<a href="blog/' . qa_path_html(@$options['categorypathprefix'] . implode('/', array_reverse(explode('/', $post['categorybackpath'])))) .
				'" class="qa-category-link' . $favoriteclass . '">' . qa_html($post['categoryname']) . '</a>');
		}

		// Post content
		if (@$options['contentview'] && isset($post['content'])) {
			$viewer = qa_load_viewer($post['content'], $post['format']);

			$fields['content'] = $viewer->get_html($post['content'], $post['format'], array(
				'blockwordspreg' => @$options['blockwordspreg'],
				'showurllinks' => @$options['showurllinks'],
				'linksnewwindow' => @$options['linksnewwindow'],
			));

			if ($microdata) {
				$fields['content'] = '<div itemprop="text">' . $fields['content'] . '</div>';
			}

			// this is for backwards compatibility with any existing links using the old style of anchor
			// that contained the post id only (changed to be valid under W3C specifications)
			$fields['content'] = '<a name="' . qa_html($postid) . '"></a>' . $fields['content'];
		}

		// Voting stuff
		//if (@$options['voteview']) {
			$voteview = $options['voteview'];

			// Calculate raw values and pass through

			if (@$options['ovoteview'] && isset($post['opostid'])) {
				$upvotes = (int)@$post['oupvotes'];
				$downvotes = (int)@$post['odownvotes'];
				$fields['vote_opostid'] = true; // for voters/flaggers layer
			} else {
				$upvotes = (int)@$post['upvotes'];
				$downvotes = (int)@$post['downvotes'];
			}

			$netvotes = $upvotes - $downvotes;

			$fields['upvotes_raw'] = $upvotes;
			$fields['downvotes_raw'] = $downvotes;
			$fields['netvotes_raw'] = $netvotes;

			// Create HTML versions...

			$upvoteshtml = qa_html(qa_format_number($upvotes, 0, true));
			$downvoteshtml = qa_html(qa_format_number($downvotes, 0, true));

			if ($netvotes >= 1)
				$netvotesPrefix = '+';
			elseif ($netvotes <= -1)
				$netvotesPrefix = '&ndash;';
			else
				$netvotesPrefix = '';

			$netvotes = abs($netvotes);
			$netvoteshtml = $netvotesPrefix . qa_html(qa_format_number($netvotes, 0, true));

			$fields['vote_view'] = (substr($voteview, 0, 6) == 'updown') ? 'updown' : 'net';
			$fields['vote_on_page'] = strpos($voteview, '-disabled-page') ? 'disabled' : 'enabled';

			if ($iscomment) {
				// for comments just show number, no additional text
				$fields['upvotes_view'] = array('prefix' => '', 'data' => $upvoteshtml, 'suffix' => '');
				$fields['downvotes_view'] = array('prefix' => '', 'data' => $downvoteshtml, 'suffix' => '');
				$fields['netvotes_view'] = array('prefix' => '', 'data' => $netvoteshtml, 'suffix' => '');
			} else {
				$fields['upvotes_view'] = $upvotes == 1
					? qa_lang_html_sub_split('main/1_liked', $upvoteshtml, '1')
					: qa_lang_html_sub_split('main/x_liked', $upvoteshtml);
				$fields['downvotes_view'] = $downvotes == 1
					? qa_lang_html_sub_split('main/1_disliked', $downvoteshtml, '1')
					: qa_lang_html_sub_split('main/x_disliked', $downvoteshtml);
				$fields['netvotes_view'] = $netvotes == 1
					? qa_lang_html_sub_split('main/1_vote', $netvoteshtml, '1')
					: qa_lang_html_sub_split('main/x_votes', $netvoteshtml);
			}

			// schema.org microdata - vote display might be formatted (e.g. '2k') so we use meta tag for true count
			if ($microdata) {
				$fields['netvotes_view']['suffix'] .= ' <meta itemprop="upvoteCount" content="' . qa_html($netvotes) . '"/>';
				$fields['upvotes_view']['suffix'] .= ' <meta itemprop="upvoteCount" content="' . qa_html($upvotes) . '"/>';
			}

			// Voting buttons

			$fields['vote_tags'] = 'id="voting_' . qa_html($postid) . '"';
			$onclick = 'onclick="return qa_vote_click(this);"';

			if ($fields['hidden']) {
				$fields['vote_state'] = 'disabled';
				$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_hidden_post') . '"';
				$fields['vote_down_tags'] = $fields['vote_up_tags'];

			} elseif ($fields['queued']) {
				$fields['vote_state'] = 'disabled';
				$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_queued') . '"';
				$fields['vote_down_tags'] = $fields['vote_up_tags'];

			} elseif ($isbyuser) {
				$fields['vote_state'] = 'disabled';
				$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_my_post') . '"';
				$fields['vote_down_tags'] = $fields['vote_up_tags'];

			} elseif (strpos($voteview, '-disabled-')) {
				$fields['vote_state'] = (@$post['uservote'] > 0) ? 'voted_up_disabled' : ((@$post['uservote'] < 0) ? 'voted_down_disabled' : 'disabled');

				if (strpos($voteview, '-disabled-page'))
					$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_q_page_only') . '"';
				elseif (strpos($voteview, '-disabled-approve'))
					$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_approve') . '"';
				else
					$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_level') . '"';

				$fields['vote_down_tags'] = $fields['vote_up_tags'];

			} elseif (@$post['uservote'] > 0) {
				$fields['vote_state'] = 'voted_up';
				$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/voted_up_popup') . '" name="' . qa_html('vote_' . $postid . '_0_' . $elementid) . '" ' . $onclick;
				$fields['vote_down_tags'] = ' ';

			} elseif (@$post['uservote'] < 0) {
				$fields['vote_state'] = 'voted_down';
				$fields['vote_up_tags'] = ' ';
				$fields['vote_down_tags'] = 'title="' . qa_lang_html('main/voted_down_popup') . '" name="' . qa_html('vote_' . $postid . '_0_' . $elementid) . '" ' . $onclick;

			} else {
				$fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_up_popup') . '" name="' . qa_html('vote_' . $postid . '_1_' . $elementid) . '" ' . $onclick;

				if (strpos($voteview, '-uponly-level')) {
					$fields['vote_state'] = 'up_only';
					$fields['vote_down_tags'] = 'title="' . qa_lang_html('main/vote_disabled_down') . '"';

				} elseif (strpos($voteview, '-uponly-approve')) {
					$fields['vote_state'] = 'up_only';
					$fields['vote_down_tags'] = 'title="' . qa_lang_html('main/vote_disabled_down_approve') . '"';

				} else {
					$fields['vote_state'] = 'enabled';
					$fields['vote_down_tags'] = 'title="' . qa_lang_html('main/vote_down_popup') . '" name="' . qa_html('vote_' . $postid . '_-1_' . $elementid) . '" ' . $onclick;
				}
			}
		//}

		// Flag count
		if (@$options['flagsview'] && @$post['flagcount']) {
			$fields['flags'] = ($post['flagcount'] == 1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
				: qa_lang_html_sub_split('main/x_flags', $post['flagcount']);
		}

		// Created when and by whom
		$fields['meta_order'] = qa_lang_html('main/meta_order'); // sets ordering of meta elements which can be language-specific

		if (@$options['whatview']) {
			$fields['what'] = qa_lang_html($isarticle ? 'bp_lang/posted' : 'main/commented');

			if (@$options['whatlink'] && strlen(@$options['p_request'])) {
				$fields['what_url'] = ($post['basetype'] == 'P') ? qa_path_html($options['p_request'])
					: qa_path_html($options['p_request'], array('show' => $postid), null, null, qa_anchor($post['basetype'], $postid));
			}
		}

		if (isset($post['created']) && @$options['whenview']) {
			$fields['when'] = qa_when_to_html($post['created'], @$options['fulldatedays']);

			if ($microdata) {
				$gmdate = gmdate('Y-m-d\TH:i:sO', $post['created']);
				$fields['when']['data'] = '<time itemprop="dateCreated" datetime="' . $gmdate . '" title="' . $gmdate . '">' . $fields['when']['data'] . '</time>';
			}
		}

		if (@$options['whoview']) {
			$fields['who'] = qa_who_to_html($isbyuser, @$post['userid'], $usershtml, @$options['ipview'] ? @inet_ntop(@$post['createip']) : null, $microdata, $post['name']);

			if (isset($post['points'])) {
				if (@$options['pointsview'])
					$fields['who']['points'] = ($post['points'] == 1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
						: qa_lang_html_sub_split('main/x_points', qa_format_number($post['points'], 0, true));

				if (isset($options['pointstitle']))
					$fields['who']['title'] = qa_get_points_title_html($post['points'], $options['pointstitle']);
			}

			if (isset($post['level']))
				$fields['who']['level'] = qa_html(qa_user_level_string($post['level']));
		}

		if (@$options['avatarsize'] > 0) {
			if (QA_FINAL_EXTERNAL_USERS)
				$fields['avatar'] = qa_get_external_avatar_html($post['userid'], $options['avatarsize'], false);
			else
				$fields['avatar'] = qa_get_user_avatar_html(@$post['flags'], @$post['email'], @$post['handle'],
					@$post['avatarblobid'], @$post['avatarwidth'], @$post['avatarheight'], $options['avatarsize']);
		}

		// Updated when and by whom
		if (@$options['updateview'] && isset($post['updated']) &&
			($post['updatetype'] != QA_UPDATE_SELECTED || $isselected) && // only show selected change if it's still selected
			( // otherwise check if one of these conditions is fulfilled...
				(!isset($post['created'])) || // ... we didn't show the created time (should never happen in practice)
				($post['hidden'] && ($post['updatetype'] == QA_UPDATE_VISIBLE)) || // ... the post was hidden as the last action
				(isset($post['closedbyid']) && ($post['updatetype'] == QA_UPDATE_CLOSED)) || // ... the post was closed as the last action
				(abs($post['updated'] - $post['created']) > 300) || // ... or over 5 minutes passed between create and update times
				($post['lastuserid'] != $post['userid']) // ... or it was updated by a different user
			)
		) {
			switch ($post['updatetype']) {
				case QA_UPDATE_TYPE:
				case QA_UPDATE_PARENT:
					$langstring = 'main/moved';
					break;

				case QA_UPDATE_CATEGORY:
					$langstring = 'main/recategorized';
					break;

				case QA_UPDATE_VISIBLE:
					$langstring = $post['hidden'] ? 'main/hidden' : 'main/reshown';
					break;

				case QA_UPDATE_CLOSED:
					$langstring = isset($post['closedbyid']) ? 'main/closed' : 'main/reopened';
					break;

				case QA_UPDATE_TAGS:
					$langstring = 'main/retagged';
					break;

				case QA_UPDATE_SELECTED:
					$langstring = 'main/selected';
					break;

				default:
					$langstring = 'main/edited';
					break;
			}

			$fields['what_2'] = qa_lang_html($langstring);

			if (@$options['whenview']) {
				$fields['when_2'] = qa_when_to_html($post['updated'], @$options['fulldatedays']);

				if ($microdata) {
					$gmdate = gmdate('Y-m-d\TH:i:sO', $post['updated']);
					$fields['when_2']['data'] = '<time itemprop="dateModified" datetime="' . $gmdate . '" title="' . $gmdate . '">' . $fields['when_2']['data'] . '</time>';
				}
			}

			if (isset($post['lastuserid']) && @$options['whoview'])
				$fields['who_2'] = qa_who_to_html(isset($userid) && ($post['lastuserid'] == $userid), $post['lastuserid'], $usershtml, @$options['ipview'] ? @inet_ntop($post['lastip']) : null, false);
		}
		
		return $fields;
	}

	function bp_tag_html($tag, $microdata = false, $favorited = false)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$url = qa_path_html('blog/tag/' . $tag);
		$attrs = $microdata ? ' rel="tag"' : '';
		$class = $favorited ? ' qa-tag-favorited' : '';

		return '<a href="' . $url . '"' . $attrs . ' class="qa-tag-link' . $class . '">' . qa_html($tag) . '</a>';
	}

	function bp_setup_notify_fields(&$qa_content, &$fields, $basetype, $login_email, $innotify, $inemail, $errors_email, $fieldprefix = '')
	{
		$fields['notify'] = array(
			'tags' => 'name="' . $fieldprefix . 'notify"',
			'type' => 'checkbox',
			'value' => qa_html($innotify),
		);

		switch ($basetype) {
			case 'P':
				$labelaskemail = qa_lang_html('bp_lang/p_notify_email');
				$labelonly = qa_lang_html('bp_lang/p_notify_label');
				$labelgotemail = qa_lang_html('bp_lang/p_notify_x_label');
				break;

			case 'C':
				$labelaskemail = qa_lang_html('article/c_notify_email');
				$labelonly = qa_lang_html('article/c_notify_label');
				$labelgotemail = qa_lang_html('article/c_notify_x_label');
				break;
		}

		if (empty($login_email)) {
			$fields['notify']['label'] =
				'<span id="' . $fieldprefix . 'email_shown">' . $labelaskemail . '</span>' .
				'<span id="' . $fieldprefix . 'email_hidden" style="display:none;">' . $labelonly . '</span>';

			$fields['notify']['tags'] .= ' id="' . $fieldprefix . 'notify" onclick="if (document.getElementById(\'' . $fieldprefix . 'notify\').checked) document.getElementById(\'' . $fieldprefix . 'email\').focus();"';
			$fields['notify']['tight'] = true;

			$fields['email'] = array(
				'id' => $fieldprefix . 'email_display',
				'tags' => 'name="' . $fieldprefix . 'email" id="' . $fieldprefix . 'email"',
				'value' => qa_html($inemail),
				'note' => qa_lang_html('article/notify_email_note'),
				'error' => qa_html($errors_email),
			);

			qa_set_display_rules($qa_content, array(
				$fieldprefix . 'email_display' => $fieldprefix . 'notify',
				$fieldprefix . 'email_shown' => $fieldprefix . 'notify',
				$fieldprefix . 'email_hidden' => '!' . $fieldprefix . 'notify',
			));

		} else {
			$fields['notify']['label'] = str_replace('^', qa_html($login_email), $labelgotemail);
		}
	}

	function bp_user_level_for_cats($catids)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/updates.php';

		$level = qa_get_logged_in_level();

		if (count($catids)) {
			$userlevels = qa_get_logged_in_levels();

			$categorylevels = array(); // create a map
			foreach ($userlevels as $userlevel) {
				if ($userlevel['entitytype'] == QA_ENTITY_CATEGORY)
					$categorylevels[$userlevel['entityid']] = $userlevel['level'];
			}

			foreach ($catids as $catid) {
				$level = max($level, @$categorylevels[$catid]);
			}
		}

		return $level;
	}
		

/*
	Omit PHP closing tag to help avoid accidental output
*/
