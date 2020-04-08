<?php
/*
	Blog Post by Jackson Siro
	https://github.com/JacksiroKe/Q2A-Blog-Post-Plugin

	Description: User's Page customization for the blog post plugin

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	require_once QA_INCLUDE_DIR . 'app/blobs.php';

	class qa_html_theme_layer extends qa_html_theme_base 
	{
		function nav_list($navigation, $class, $level=null)
		{
			switch ( $this->template ) {
				case 'user':
				case 'user-wall':
				case 'user-activity':
				case 'user-questions':
				case 'user-answers':
				case 'user-articles':
					$navigation['articles'] = array(
						  'label' => qa_lang('bp_lang/nav_articles'),
						  'url' => qa_path_html('user/'.qa_request_part(1).'/articles'),
					);
					break;				
				case 'account':
				case 'messages':
				case 'favorites':
					$navigation['articles'] = array(
						  'label' => qa_lang('bp_lang/nav_articles'),
						  'url' => qa_path_html('user/'.qa_get_logged_in_handle().'/articles'),
					);
					break;
			}
			if(count($navigation) > 1 ) 
				qa_html_theme_base::nav_list($navigation, $class, $level=null);
		}
		
		function doctype()
		{
			if (qa_request_part(0) == 'user') {
				$handle = qa_request_part(1);
				$usersection = qa_request_part(2);
				if (!strlen($handle)) {
					$handle = qa_get_logged_in_handle();
					qa_redirect(!empty($handle) ? 'user/' . $handle : 'users');
				}

				if (QA_FINAL_EXTERNAL_USERS) {
					$userid = qa_handle_to_userid($handle);
					if (!isset($userid))
						return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

					$usershtml = qa_get_users_html(array($userid), false, qa_path_to_root(), true);
					$userhtml = @$usershtml[$userid];
				} else $userhtml = qa_html($handle);

				if ($usersection == 'articles') {
					$this->template = 'user-articles';
					$this->content = $this->bp_user_articles($handle, $userhtml);
				}
			}
			qa_html_theme_base::doctype();
		}
		
		function bp_user_articles($handle, $userhtml)
		{			
			//require_once QA_INCLUDE_DIR . 'db/selects.php';
			require_once QA_INCLUDE_DIR . 'app/format.php';
			require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-db.php';
			require_once QA_PLUGIN_DIR . 'q2a-blog-post/blog-format.php';
			
			$start = qa_get_start();
			$loginuserid = qa_get_logged_in_userid();
			$identifier = QA_FINAL_EXTERNAL_USERS ? $userid : $handle;

			list($useraccount, $userpoints, $articles) = qa_db_select_with_pending(
				QA_FINAL_EXTERNAL_USERS ? null : bp_db_user_account_selectspec($handle, false),
				bp_db_user_points_selectspec($identifier),
				bp_db_user_recent_ps_selectspec($loginuserid, $identifier, qa_opt_if_loaded('page_size_qs'), $start)
			);

			if (!QA_FINAL_EXTERNAL_USERS && !is_array($useraccount)) // check the user exists
				return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

			// Get information on user articles
			$pagesize = qa_opt('page_size_qs');
			$count = (int)@$userpoints['qposts'];
			$articles = array_slice($articles, 0, $pagesize);
			$usershtml = qa_userids_handles_html($articles, false);

			$qa_content = qa_content_prepare(true);

			if (count($articles)) $qa_content['title'] = qa_lang_html_sub('bp_lang/articles_by_x', $userhtml);
			else $qa_content['title'] = qa_lang_html_sub('bp_lang/no_articles_by_x', $userhtml);

			$qa_content['q_list']['form'] = array(
				'tags' => 'method="post" action="' . qa_self_html() . '"',
				'hidden' => array( 'code' => qa_get_form_security_code('vote') ),
			);

			$qa_content['q_list']['qs'] = array();

			$htmldefaults = qa_post_html_defaults('P');
			$htmldefaults['whoview'] = false;
			$htmldefaults['avatarsize'] = 0;

			foreach ($articles as $article) {
				$qa_content['q_list']['qs'][] = bp_post_html_fields($article, $loginuserid, qa_cookie_get(),
					$usershtml, null, qa_post_html_options($article, $htmldefaults));
			}

			$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'));

			$ismyuser = isset($loginuserid) && $loginuserid == (QA_FINAL_EXTERNAL_USERS ? $userid : $useraccount['userid']);
			$qa_content['navigation']['sub'] = qa_user_sub_navigation($handle, 'articles', $ismyuser);
			$qa_content['navigation']['sub']['articles'] = array(
				'label' => qa_lang('bp_lang/nav_articles'),
				'url' => qa_path_html('user/'.$handle.'/articles'),
				'selected' => true,
			);

			return $qa_content;
		}
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
