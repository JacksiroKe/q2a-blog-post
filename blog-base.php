<?php
/*
	Blog Post by Jackson Siro
	https://github.com/JacksiroKe/Q2A-Blog-Post-Plugin

	Description: Basic and Database functions for the blog post plugin

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	function bp_p_path_html($permalink, $absolute = false, $showtype = null, $showid = null)
	{
		return qa_html(bp_p_path($permalink, $absolute, $showtype, $showid));
	}

	function bp_p_path($permalink, $absolute = false, $showtype = null, $showid = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		if (($showtype == 'P' || $showtype == 'C') && isset($showid)) {
			$params = array('show' => $showid); // due to pagination
			$anchor = qa_anchor($showtype, $showid);

		} else {
			$params = null;
			$anchor = null;
		}

		return qa_path(bp_p_request($permalink), $params, $absolute ? qa_opt('site_url') : null, null, $anchor);
	}


	function bp_p_request($permalink)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/options.php';
		require_once QA_INCLUDE_DIR . 'util/string.php';

		return 'blog/' . $permalink;
	}

	function bp_cat_path($navcategories, $catid)
	{
		$upcategories = array();

		for ($upcategory = @$navcategories[$catid]; isset($upcategory); $upcategory = @$navcategories[$upcategory['parentid']])
			$upcategories[$upcategory['catid']] = $upcategory;

		return array_reverse($upcategories, true);
	}

	function bp_cat_path_html($navcategories, $catid)
	{
		$blog_cats = bp_cat_path($navcategories, $catid);

		$html = '';
		foreach ($blog_cats as $category)
			$html .= (strlen($html) ? ' / ' : '') . qa_html($category['title']);

		return $html;
	}

	function bp_cat_path_request($navcategories, $catid)
	{
		$blog_cats = bp_cat_path($navcategories, $catid);

		$request = '';
		foreach ($blog_cats as $category)
			$request .= (strlen($request) ? '/' : '') . $category['tags'];

		return $request;
	}

	function bp_permit_options()
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$permits = array('bp_permit_view_p_page', 'bp_permit_post_p', 'bp_permit_post_c', 'bp_permit_vote', 'bp_permit_flag', 'bp_permit_moderate', 'bp_permit_hide_show', 'bp_permit_delete_hidden');

		return $permits;
	}
	
	function bp_user_maximum_permit_error($permitoption, $limitaction = null, $checkblocks = true)
	{
		return bp_user_permit_error($permitoption, $limitaction, qa_user_level_maximum(), $checkblocks);
	}
	
	function bp_user_permit_error($permitoption = null, $limitaction = null, $userlevel = null, $checkblocks = true, $userfields = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		require_once QA_INCLUDE_DIR . 'app/limits.php';

		if (!isset($userfields))
			$userfields = qa_get_logged_in_user_cache();

		$userid = isset($userfields['userid']) ? $userfields['userid'] : null;

		if (!isset($userlevel))
			$userlevel = isset($userfields['level']) ? $userfields['level'] : null;

		$flags = isset($userfields['flags']) ? $userfields['flags'] : null;
		if (!$checkblocks)
			$flags &= ~QA_USER_FLAGS_USER_BLOCKED;

		$error = qa_permit_error($permitoption, $userid, $userlevel, $flags);

		if ($checkblocks && !$error && qa_is_ip_blocked())
			$error = 'ipblock';

		if (!$error && isset($userid) && ($flags & QA_USER_FLAGS_MUST_CONFIRM) && qa_opt('confirm_user_emails'))
			$error = 'confirm';

		if (isset($limitaction) && !$error) {
			if (bp_user_limits_remaining($limitaction) <= 0)
				$error = 'limit';
		}

		return $error;
	}
	
	function bp_user_limits_remaining($action)
	{
		$userlimits = qa_db_get_pending_result('userlimits', qa_db_user_limits_selectspec(qa_get_logged_in_userid()));
		$iplimits = qa_db_get_pending_result('iplimits', qa_db_ip_limits_selectspec(qa_remote_ip_address()));

		return bp_limits_calc_remaining($action, @$userlimits[$action], @$iplimits[$action]);
	}

	
	function bp_limits_calc_remaining($action, $userlimits, $iplimits)
	{
		switch ($action) {
			case QA_LIMIT_BLOG_POSTS:
				$usermax = qa_opt('bp_max_rate_user_ps');
				$ipmax = qa_opt('bp_max_rate_ip_ps');
				break;
				
			case QA_LIMIT_BLOG_COMMENTS:
				$usermax = qa_opt('bp_max_rate_user_cs');
				$ipmax = qa_opt('bp_max_rate_ip_cs');
				break;
			default:
				qa_fatal_error('Unknown limit code in qa_limits_calc_remaining: ' . $action);
				break;
		}

		$period = (int)(qa_opt('db_time') / 3600);

		return max(0, min(
			$usermax - (@$userlimits['period'] == $period ? $userlimits['count'] : 0),
			$ipmax - (@$iplimits['period'] == $period ? $iplimits['count'] : 0)
		));
	}

	function bp_default_option($name)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$fixed_defaults = array(
			'bp_avatar_p_page_c_size' => 20,
			'bp_avatar_p_page_p_size' => 50,
			'bp_do_write_check_ps' => 0,
			'bp_blog_feed' => 1,
			'bp_feed_for_articles' => 1,
			'bp_match_write_check_ps' => 3,
			'bp_match_related_ps' => 3,
			'bp_max_len_p_title' => 120,
			'bp_min_len_c_content' => 12,
			'bp_min_len_p_content' => 0,
			'bp_min_len_p_title' => 12,
			'bp_permit_close_p' => QA_PERMIT_EDITORS,
			'bp_permit_delete_hidden' => QA_PERMIT_MODERATORS,
			'bp_permit_edit_c' => QA_PERMIT_EDITORS,
			'bp_permit_edit_p' => QA_PERMIT_EDITORS,
			'bp_permit_edit_silent' => QA_PERMIT_MODERATORS,
			'bp_permit_flag' => QA_PERMIT_CONFIRMED,
			'bp_permit_hide_show' => QA_PERMIT_EDITORS,
			'bp_permit_moderate' => QA_PERMIT_EXPERTS,
			'bp_permit_view_p_page' => QA_PERMIT_ALL,
			'bp_points_post_p' => 2,
		);
		if (isset($fixed_defaults[$name])) return $fixed_defaults[$name];

		switch ($name) {
			case 'bp_blog_editor':
				require_once QA_INCLUDE_DIR . 'app/format.php';
				$value = '-'; // to match none by default, i.e. choose based on who is best at editing HTML
				qa_load_editor('', 'html', $value);
				break;

			case 'bp_permit_post_p': // convert from deprecated option if available
				$value = qa_opt('bp_write_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
				break;

			case 'bp_permit_post_c': // convert from deprecated option if available
				$value = qa_opt('bp_comment_needs_login') ? QA_PERMIT_USERS : QA_PERMIT_ALL;
				break;
				
			default: // call option_default method in any registered modules
				$modules = qa_load_all_modules_with('option_default');  // Loads all modules with the 'option_default' method

				foreach ($modules as $module) {
					$value = $module->option_default($name);
					if (strlen($value))
						return $value;
				}

				$value = '';
				break;
		}

		return $value;
	}
	
	function bp_reset_options($names)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		foreach ($names as $name) {
			qa_set_option($name, bp_default_option($name));
		}
	}
	
	function bp_get_options($names)
	{
		global $qa_options_cache, $qa_options_loaded;
		$options = array();
		foreach ($names as $name) {
			if (!isset($qa_options_cache[$name])) {
				$todatabase = true;

				switch ($name) { 
					case 'bp_write_needs_login':
					case 'bp_comment_needs_login':
						$todatabase = false;
						break;
				}

				qa_set_option($name, bp_default_option($name), $todatabase);
			}
			$options[$name] = $qa_options_cache[$name];
		}
		return $options;
	}

	function bp_cat_navigation($categories, $selectedid = null, $pathprefix = '', $showpcount = true, $pathparams = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$parentcategories = array();

		foreach ($categories as $category)
			$parentcategories[$category['parentid']][] = $category;

		$selecteds = bp_cat_path($categories, $selectedid);
		//$favoritemap = qa_get_favorite_non_ps_map();

		return bp_cat_navigation_sub($parentcategories, null, $selecteds, $pathprefix, $showpcount, $pathparams, null);//$favoritemap);
	}

	function bp_cat_navigation_sub($parentcategories, $parentid, $selecteds, $pathprefix, $showpcount, $pathparams, $favoritemap = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$navigation = array();

		if (!isset($parentid)) {
			$navigation['all'] = array(
				'url' => qa_path_html($pathprefix, $pathparams),
				'label' => qa_lang_html('main/all_categories'),
				'selected' => !count($selecteds),
				'catid' => null,
			);
		}

		if (isset($parentcategories[$parentid])) {
			foreach ($parentcategories[$parentid] as $category) {
				$navigation[qa_html($category['tags'])] = array(
					'url' => qa_path_html($pathprefix . $category['tags'], $pathparams),
					'label' => qa_html($category['title']),
					'popup' => qa_html(@$category['content']),
					'selected' => isset($selecteds[$category['catid']]),
					'note' => $showpcount ? ('(' . qa_html(qa_format_number($category['pcount'], 0, true)) . ')') : null,
					'subnav' => bp_cat_navigation_sub($parentcategories, $category['catid'], $selecteds,
						$pathprefix . $category['tags'] . '/', $showpcount, $pathparams, $favoritemap),
					'catid' => $category['catid'],
					'favorited' => @$favoritemap['blogcat'][$category['backpath']],
				);
			}
		}
		return $navigation;
	}

	function bp_sub_navigation($request, $blog_cats)
	{
		$bp_request	= 'blog';			
		$navigation = array();
		$navigation['all'] = array(	
			'label' => qa_lang('bp_lang/nav_all'),
			'url' => qa_path_html('blog'),
			'selected' => ($request == 'blog' ) ? 'selected' : '',
		);
		foreach ( $blog_cats as $blogcat ) {
			$navigation[$blogcat['tags']] = array(
				'label' => $blogcat['title'],
				'url' => qa_path_html('blog/'.$blogcat['tags']),
				'selected' => ($request == 'blog'.$blogcat['tags'] ) ? 'selected' : '',
			);
		}
		
		//if (qa_user_maximum_permit_error('bp_permit_post_p') != 'level') {
		if (qa_is_logged_in()) {
			$navigation['write'] = array(
				'label' => qa_lang('bp_lang/nav_post'),
				'url' => qa_path_html('blog/write'),
				'selected' => ($request == 'blog/write' ) ? 'selected' : '',
			);
		}
		
		return $navigation;
	}

	function bp_html_suggest_ps_tags($usingtags = false, $categoryrequest = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$hascategory = strlen($categoryrequest);

		$htmlmessage = $hascategory ? qa_lang_html('bp_lang/suggest_category_qs') :
			($usingtags ? qa_lang_html('bp_lang/suggest_ps_tags') : qa_lang_html('bp_lang/suggest_ps'));

		return strtr(
			$htmlmessage,

			array(
				'^1' => '<a href="' . qa_path_html('blog' . ($hascategory ? ('/' . $categoryrequest) : '')) . '">',
				'^2' => '</a>',
				'^3' => '<a href="' . qa_path_html('tags') . '">',
				'^4' => '</a>',
			)
		);
	}

	function bp_html_suggest_write($catid = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$htmlmessage = qa_lang_html('bp_lang/suggest_write');

		return strtr(
			$htmlmessage,

			array(
				'^1' => '<a href="' . qa_path_html('blog/write', strlen($catid) ? array('blogcat' => $catid) : null) . '">',
				'^2' => '</a>',
			)
		);
	}

	function bp_post_create($userid, $handle, $cookieid, $title, $content, $permalink, $format, $text, $tagstring, $notify, $email,	$catid = null, $extravalue = null, $queued = false, $name = null)
	{
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		$postid = bp_db_post_create($queued ? 'P_QUEUED' : 'P', $userid, isset($userid) ? null : $cookieid,
			qa_remote_ip_address(), $title, $content, $permalink, $format, $tagstring, qa_combine_notify_email($userid, $notify, $email), $catid, isset($userid) ? null : $name);
		
		//$blogusers = bp_db_user_find_by_handle($userid);
		//if (!count($blogusers)) bp_db_user_create($userid);
		//else
		//if ($queued) qa_db_bqueuedcount_update();
		//else bp_update_counts_for_p($postid);
		return $postid;
	}

	function bp_update_counts_for_p($postid)
	{
		bp_db_cat_path_pcount_update(bp_db_post_get_cat_path($postid));
		bp_db_bpcount_update();
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
