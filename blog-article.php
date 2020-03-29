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

	function bp_page_p_load_as($article, $childposts)
	{
		$comments = array();
		foreach ($childposts as $postid => $post) {
			switch ($post['type']) {
				case 'P':
				case 'P_HIDDEN':
				case 'P_QUEUED':
					$comments[$postid] = $post;
					break;
			}
		}

		return $comments;
	}

	function bp_page_p_article_view($article, $parentarticle, $closepost, $usershtml, $formrequested)
	{
		$articleid = $article['postid'];
		$userid = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();

		$htmloptions = qa_post_html_options($article, null, true);
		$htmloptions['commentsview'] = false; // comment count is displayed separately so don't show it here
		$htmloptions['avatarsize'] = qa_opt('avatar_q_page_q_size');
		$htmloptions['q_request'] = qa_q_request($article['postid'], $article['title']);
		$q_view = bp_post_html_fields($article, $userid, $cookieid, $usershtml, null, $htmloptions);


		$q_view['main_form_tags'] = 'method="post" action="' . qa_self_html() . '"';
		$q_view['voting_form_hidden'] = array('code' => qa_get_form_security_code('vote'));
		$q_view['buttons_form_hidden'] = array('code' => qa_get_form_security_code('buttons-' . $articleid), 'qa_click' => '');

		// Buttons for operating on the article
		if (!$formrequested) { // don't show if another form is currently being shown on page
			$clicksuffix = ' onclick="qa_show_waiting_after(this, false);"'; // add to operations that write to database
			$buttons = array();

			if ($article['editbutton']) {
				$buttons['edit'] = array(
					'tags' => 'name="q_doedit"',
					'label' => qa_lang_html('bp_lang/edit_button'),
					'popup' => qa_lang_html('bp_lang/edit_p_popup'),
				);
			}

			$hascategories = qa_using_categories();
			if ($article['retagcatbutton']) {
				$buttons['retagcat'] = array(
					'tags' => 'name="q_doedit"',
					'label' => qa_lang_html($hascategories ? 'bp_lang/recat_button' : 'bp_lang/retag_button'),
					'popup' => qa_lang_html($hascategories
						? (qa_using_tags() ? 'bp_lang/retag_cat_popup' : 'bp_lang/recat_popup')
						: 'bp_lang/retag_popup'
					),
				);
			}

			if ($article['flagbutton']) {
				$buttons['flag'] = array(
					'tags' => 'name="q_doflag"' . $clicksuffix,
					'label' => qa_lang_html($article['flagtohide'] ? 'bp_lang/flag_hide_button' : 'bp_lang/flag_button'),
					'popup' => qa_lang_html('bp_lang/flag_p_popup'),
				);
			}

			if ($article['unflaggable']) {
				$buttons['unflag'] = array(
					'tags' => 'name="q_dounflag"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/unflag_button'),
					'popup' => qa_lang_html('bp_lang/unflag_popup'),
				);
			}

			if ($article['clearflaggable']) {
				$buttons['clearflags'] = array(
					'tags' => 'name="q_doclearflags"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/clear_flags_button'),
					'popup' => qa_lang_html('bp_lang/clear_flags_popup'),
				);
			}

			if ($article['closeable']) {
				$buttons['close'] = array(
					'tags' => 'name="q_doclose"',
					'label' => qa_lang_html('bp_lang/close_button'),
					'popup' => qa_lang_html('bp_lang/close_p_popup'),
				);
			}

			if ($article['reopenable']) {
				$buttons['reopen'] = array(
					'tags' => 'name="q_doreopen"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reopen_button'),
					'popup' => qa_lang_html('bp_lang/reopen_p_popup'),
				);
			}

			if ($article['moderatable']) {
				$buttons['approve'] = array(
					'tags' => 'name="q_doapprove"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/approve_button'),
					'popup' => qa_lang_html('bp_lang/approve_p_popup'),
				);

				$buttons['reject'] = array(
					'tags' => 'name="q_doreject"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reject_button'),
					'popup' => qa_lang_html('bp_lang/reject_p_popup'),
				);
			}

			if ($article['hideable']) {
				$buttons['hide'] = array(
					'tags' => 'name="q_dohide"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/hide_button'),
					'popup' => qa_lang_html('bp_lang/hide_p_popup'),
				);
			}

			if ($article['reshowable']) {
				$buttons['reshow'] = array(
					'tags' => 'name="q_doreshow"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reshow_button'),
					'popup' => qa_lang_html('bp_lang/reshow_p_popup'),
				);
			}

			if ($article['deleteable']) {
				$buttons['delete'] = array(
					'tags' => 'name="q_dodelete"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/delete_button'),
					'popup' => qa_lang_html('bp_lang/delete_p_popup'),
				);
			}

			if ($article['claimable']) {
				$buttons['claim'] = array(
					'tags' => 'name="q_doclaim"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/claim_button'),
					'popup' => qa_lang_html('bp_lang/claim_p_popup'),
				);
			}

			if ($article['commentbutton']) {
				$buttons['comment'] = array(
					'tags' => 'name="p_docomment" id="p_docomment" onclick="return qa_toggle_element(\'c' . $articleid . '\')"',
					'label' => qa_lang_html('bp_lang/comment_button'),
					'popup' => qa_lang_html('bp_lang/comment_p_popup'),
				);
			}

			$q_view['form'] = array(
				'style' => 'light',
				'buttons' => $buttons,
			);
		}


		// Information about the article of the comment that this article follows on from (or a article directly)

		if (isset($parentarticle)) {
			$q_view['follows'] = array(
				'label' => qa_lang_html(($article['parentid'] == $parentarticle['postid']) ? 'bp_lang/follows_p' : 'bp_lang/follows_a'),
				'title' => qa_html(qa_block_words_replace($parentarticle['title'], qa_get_block_words_preg())),
				'url' => qa_q_path_html($parentarticle['postid'], $parentarticle['title'], false,
					($article['parentid'] == $parentarticle['postid']) ? 'P' : 'R', $article['parentid']),
			);
		}


		// Information about the article that this article is a duplicate of (if appropriate)

		if (isset($closepost)) {
			if ($closepost['basetype'] == 'P') {
				if ($closepost['hidden']) {
					// don't show link for hidden articles
					$q_view['closed'] = array(
						'state' => qa_lang_html('main/closed'),
						'label' => qa_lang_html('main/closed'),
						'content' => '',
					);
				} else {
					$q_view['closed'] = array(
						'state' => qa_lang_html('main/closed'),
						'label' => qa_lang_html('bp_lang/closed_as_duplicate'),
						'content' => qa_html(qa_block_words_replace($closepost['title'], qa_get_block_words_preg())),
						'url' => qa_q_path_html($closepost['postid'], $closepost['title']),
					);
				}

			} elseif ($closepost['type'] == 'NOTE') {
				$viewer = qa_load_viewer($closepost['content'], $closepost['format']);

				$q_view['closed'] = array(
					'state' => qa_lang_html('main/closed'),
					'label' => qa_lang_html('bp_lang/closed_with_note'),
					'content' => $viewer->get_html($closepost['content'], $closepost['format'], array(
						'blockwordspreg' => qa_get_block_words_preg(),
					)),
				);
			}
		}


		// Extra value display

		if (strlen(@$article['extra']) && qa_opt('extra_field_active') && qa_opt('extra_field_display')) {
			$q_view['extra'] = array(
				'label' => qa_html(qa_opt('extra_field_label')),
				'content' => qa_html(qa_block_words_replace($article['extra'], qa_get_block_words_preg())),
			);
		}


		return $q_view;
	}

	function qa_page_q_answer_view($article, $comment, $isselected, $usershtml, $formrequested)
	{
		$commentid = $comment['postid'];
		$userid = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();

		$htmloptions = qa_post_html_options($comment, null, true);
		$htmloptions['isselected'] = $isselected;
		$htmloptions['avatarsize'] = qa_opt('avatar_q_page_a_size');
		$htmloptions['q_request'] = qa_q_request($article['postid'], $article['title']);
		$a_view = bp_post_html_fields($comment, $userid, $cookieid, $usershtml, null, $htmloptions);

		if ($comment['queued'])
			$a_view['error'] = $comment['isbyuser'] ? qa_lang_html('bp_lang/a_your_waiting_approval') : qa_lang_html('bp_lang/a_waiting_your_approval');

		$a_view['main_form_tags'] = 'method="post" action="' . qa_self_html() . '"';
		$a_view['voting_form_hidden'] = array('code' => qa_get_form_security_code('vote'));
		$a_view['buttons_form_hidden'] = array('code' => qa_get_form_security_code('buttons-' . $commentid), 'qa_click' => '');


		// Selection/unselect buttons and others for operating on the comment

		if (!$formrequested) { // don't show if another form is currently being shown on page
			$prefix = 'a' . qa_html($commentid) . '_';
			$clicksuffix = ' onclick="return qa_answer_click(' . qa_js($commentid) . ', ' . qa_js($article['postid']) . ', this);"';

			if ($article['aselectable'] && !$comment['hidden'] && !$comment['queued']) {
				if ($isselected)
					$a_view['unselect_tags'] = 'title="' . qa_lang_html('bp_lang/unselect_popup') . '" name="' . $prefix . 'dounselect"' . $clicksuffix;
				else
					$a_view['select_tags'] = 'title="' . qa_lang_html('bp_lang/select_popup') . '" name="' . $prefix . 'doselect"' . $clicksuffix;
			}

			$buttons = array();

			if ($comment['editbutton']) {
				$buttons['edit'] = array(
					'tags' => 'name="' . $prefix . 'doedit"',
					'label' => qa_lang_html('bp_lang/edit_button'),
					'popup' => qa_lang_html('bp_lang/edit_a_popup'),
				);
			}

			if ($comment['flagbutton']) {
				$buttons['flag'] = array(
					'tags' => 'name="' . $prefix . 'doflag"' . $clicksuffix,
					'label' => qa_lang_html($comment['flagtohide'] ? 'bp_lang/flag_hide_button' : 'bp_lang/flag_button'),
					'popup' => qa_lang_html('bp_lang/flag_a_popup'),
				);
			}

			if ($comment['unflaggable']) {
				$buttons['unflag'] = array(
					'tags' => 'name="' . $prefix . 'dounflag"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/unflag_button'),
					'popup' => qa_lang_html('bp_lang/unflag_popup'),
				);
			}

			if ($comment['clearflaggable']) {
				$buttons['clearflags'] = array(
					'tags' => 'name="' . $prefix . 'doclearflags"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/clear_flags_button'),
					'popup' => qa_lang_html('bp_lang/clear_flags_popup'),
				);
			}

			if ($comment['moderatable']) {
				$buttons['approve'] = array(
					'tags' => 'name="' . $prefix . 'doapprove"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/approve_button'),
					'popup' => qa_lang_html('bp_lang/approve_a_popup'),
				);

				$buttons['reject'] = array(
					'tags' => 'name="' . $prefix . 'doreject"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reject_button'),
					'popup' => qa_lang_html('bp_lang/reject_a_popup'),
				);
			}

			if ($comment['hideable']) {
				$buttons['hide'] = array(
					'tags' => 'name="' . $prefix . 'dohide"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/hide_button'),
					'popup' => qa_lang_html('bp_lang/hide_a_popup'),
				);
			}

			if ($comment['reshowable']) {
				$buttons['reshow'] = array(
					'tags' => 'name="' . $prefix . 'doreshow"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reshow_button'),
					'popup' => qa_lang_html('bp_lang/reshow_a_popup'),
				);
			}

			if ($comment['deleteable']) {
				$buttons['delete'] = array(
					'tags' => 'name="' . $prefix . 'dodelete"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/delete_button'),
					'popup' => qa_lang_html('bp_lang/delete_a_popup'),
				);
			}

			if ($comment['claimable']) {
				$buttons['claim'] = array(
					'tags' => 'name="' . $prefix . 'doclaim"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/claim_button'),
					'popup' => qa_lang_html('bp_lang/claim_a_popup'),
				);
			}

			if ($comment['followable']) {
				$buttons['follow'] = array(
					'tags' => 'name="' . $prefix . 'dofollow"',
					'label' => qa_lang_html('bp_lang/follow_button'),
					'popup' => qa_lang_html('bp_lang/follow_a_popup'),
				);
			}

			if ($comment['commentbutton']) {
				$buttons['reply'] = array(
					'tags' => 'name="' . $prefix . 'doreply" onclick="return qa_toggle_element(\'c' . $commentid . '\')"',
					'label' => qa_lang_html('bp_lang/comment_button'),
					'popup' => qa_lang_html('bp_lang/comment_a_popup'),
				);
			}

			$a_view['form'] = array(
				'style' => 'light',
				'buttons' => $buttons,
			);
		}

		return $a_view;
	}

	function qa_page_q_comment_view($article, $parent, $reply, $usershtml, $formrequested)
	{
		$replyid = $reply['postid'];
		$articleid = ($parent['basetype'] == 'P') ? $parent['postid'] : $parent['parentid'];
		$commentid = ($parent['basetype'] == 'P') ? null : $parent['postid'];
		$userid = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();

		$htmloptions = qa_post_html_options($reply, null, true);
		$htmloptions['avatarsize'] = qa_opt('avatar_q_page_c_size');
		$htmloptions['q_request'] = qa_q_request($article['postid'], $article['title']);
		$c_view = bp_post_html_fields($reply, $userid, $cookieid, $usershtml, null, $htmloptions);

		if ($reply['queued'])
			$c_view['error'] = $reply['isbyuser'] ? qa_lang_html('bp_lang/c_your_waiting_approval') : qa_lang_html('bp_lang/c_waiting_your_approval');

		$c_view['main_form_tags'] = 'method="post" action="' . qa_self_html() . '"';
		$c_view['voting_form_hidden'] = array('code' => qa_get_form_security_code('vote'));
		$c_view['buttons_form_hidden'] = array('code' => qa_get_form_security_code('buttons-' . $parent['postid']), 'qa_click' => '');


		// Buttons for operating on this reply

		if (!$formrequested) { // don't show if another form is currently being shown on page
			$prefix = 'c' . qa_html($replyid) . '_';
			$clicksuffix = ' onclick="return qa_comment_click(' . qa_js($replyid) . ', ' . qa_js($articleid) . ', ' . qa_js($parent['postid']) . ', this);"';

			$buttons = array();

			if ($reply['editbutton']) {
				$buttons['edit'] = array(
					'tags' => 'name="' . $prefix . 'doedit"',
					'label' => qa_lang_html('bp_lang/edit_button'),
					'popup' => qa_lang_html('bp_lang/edit_c_popup'),
				);
			}

			if ($reply['flagbutton']) {
				$buttons['flag'] = array(
					'tags' => 'name="' . $prefix . 'doflag"' . $clicksuffix,
					'label' => qa_lang_html($reply['flagtohide'] ? 'bp_lang/flag_hide_button' : 'bp_lang/flag_button'),
					'popup' => qa_lang_html('bp_lang/flag_c_popup'),
				);
			}

			if ($reply['unflaggable']) {
				$buttons['unflag'] = array(
					'tags' => 'name="' . $prefix . 'dounflag"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/unflag_button'),
					'popup' => qa_lang_html('bp_lang/unflag_popup'),
				);
			}

			if ($reply['clearflaggable']) {
				$buttons['clearflags'] = array(
					'tags' => 'name="' . $prefix . 'doclearflags"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/clear_flags_button'),
					'popup' => qa_lang_html('bp_lang/clear_flags_popup'),
				);
			}

			if ($reply['moderatable']) {
				$buttons['approve'] = array(
					'tags' => 'name="' . $prefix . 'doapprove"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/approve_button'),
					'popup' => qa_lang_html('bp_lang/approve_c_popup'),
				);

				$buttons['reject'] = array(
					'tags' => 'name="' . $prefix . 'doreject"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reject_button'),
					'popup' => qa_lang_html('bp_lang/reject_c_popup'),
				);
			}

			if ($reply['hideable']) {
				$buttons['hide'] = array(
					'tags' => 'name="' . $prefix . 'dohide"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/hide_button'),
					'popup' => qa_lang_html('bp_lang/hide_c_popup'),
				);
			}

			if ($reply['reshowable']) {
				$buttons['reshow'] = array(
					'tags' => 'name="' . $prefix . 'doreshow"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/reshow_button'),
					'popup' => qa_lang_html('bp_lang/reshow_c_popup'),
				);
			}

			if ($reply['deleteable']) {
				$buttons['delete'] = array(
					'tags' => 'name="' . $prefix . 'dodelete"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/delete_button'),
					'popup' => qa_lang_html('bp_lang/delete_c_popup'),
				);
			}

			if ($reply['claimable']) {
				$buttons['claim'] = array(
					'tags' => 'name="' . $prefix . 'doclaim"' . $clicksuffix,
					'label' => qa_lang_html('bp_lang/claim_button'),
					'popup' => qa_lang_html('bp_lang/claim_c_popup'),
				);
			}

			if ($parent['commentbutton'] && qa_opt('show_c_comment_buttons') && $reply['type'] == 'R') {
				$buttons['reply'] = array(
					'tags' => 'name="' . (($parent['basetype'] == 'P') ? 'q' : ('a' . qa_html($parent['postid']))) .
						'_doreply" onclick="return qa_toggle_element(\'c' . qa_html($parent['postid']) . '\')"',
					'label' => qa_lang_html('bp_lang/comment_button'),
					'popup' => qa_lang_html('bp_lang/comment_c_popup'),
				);
			}

			$c_view['form'] = array(
				'style' => 'light',
				'buttons' => $buttons,
			);
		}

		return $c_view;
	}
	
	function bp_page_p_load_c_follows($article, $childposts, $cchildposts, $duplicateposts = array())
	{
		$commentsfollows = array();

		foreach ($childposts as $postid => $post) {
			switch ($post['basetype']) {
				case 'P':
				case 'R':
					$commentsfollows[$postid] = $post;
					break;
			}
		}

		foreach ($cchildposts as $postid => $post) {
			switch ($post['basetype']) {
				case 'P':
				case 'R':
					$commentsfollows[$postid] = $post;
					break;
			}
		}

		foreach ($duplicateposts as $postid => $post) {
			$commentsfollows[$postid] = $post;
		}

		return $commentsfollows;
	}
	

	function bp_page_p_post_rules($post, $parentpost = null, $siblingposts = null, $childposts = null)
	{
		if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

		$userid = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();
		$userlevel = qa_user_level_for_post($post);

		$userfields = qa_get_logged_in_user_cache();
		if (!isset($userfields)) {
			$userfields = array(
				'userid' => null,
				'level' => null,
				'flags' => null,
			);
		}

		$rules['isbyuser'] = qa_post_is_by_user($post, $userid, $cookieid);
		$rules['closed'] = $post['basetype'] == 'P' && (isset($post['closedbyid']) || (isset($post['selchildid']) && qa_opt('do_close_on_select')));

		// Cache some responses to the user permission checks

		$permiterror_post_q = qa_user_permit_error('permit_post_q', null, $userlevel, true, $userfields); // don't check limits here, so we can show error message
		$permiterror_post_a = qa_user_permit_error('permit_post_a', null, $userlevel, true, $userfields);
		$permiterror_post_c = qa_user_permit_error('permit_post_c', null, $userlevel, true, $userfields);

		$edit_option = $post['basetype'] == 'P' ? 'permit_edit_q' : ($post['basetype'] == 'C' ? 'permit_edit_a' : 'permit_edit_c');
		$permiterror_edit = qa_user_permit_error($edit_option, null, $userlevel, true, $userfields);
		$permiterror_retagcat = qa_user_permit_error('permit_retag_cat', null, $userlevel, true, $userfields);
		$permiterror_flag = qa_user_permit_error('permit_flag', null, $userlevel, true, $userfields);
		$permiterror_hide_show = qa_user_permit_error('permit_hide_show', null, $userlevel, true, $userfields);
		$permiterror_hide_show_self = $rules['isbyuser'] ? qa_user_permit_error(null, null, $userlevel, true, $userfields) : $permiterror_hide_show;

		$close_option = $rules['isbyuser'] && qa_opt('allow_close_own_articles') ? null : 'permit_close_q';
		$permiterror_close_open = qa_user_permit_error($close_option, null, $userlevel, true, $userfields);
		$permiterror_moderate = qa_user_permit_error('permit_moderate', null, $userlevel, true, $userfields);

		// General permissions

		$rules['authorlast'] = !isset($post['lastuserid']) || $post['lastuserid'] === $post['userid'];
		$rules['viewable'] = $post['hidden'] ? !$permiterror_hide_show_self : ($post['queued'] ? ($rules['isbyuser'] || !$permiterror_moderate) : true);

		// Answer, reply and edit might show the button even if the user still needs to do something (e.g. log in)

		$rules['answerbutton'] = $post['type'] == 'P' && $permiterror_post_a != 'level' && !$rules['closed']
			&& (qa_opt('allow_self_comment') || !$rules['isbyuser']);

		$rules['commentbutton'] = ($post['type'] == 'P' || $post['type'] == 'C') && $permiterror_post_c != 'level'
			&& qa_opt($post['type'] == 'P' ? 'comment_on_qs' : 'comment_on_as');
		$rules['commentable'] = $rules['commentbutton'] && !$permiterror_post_c;

		$button_errors = array('login', 'level', 'approve');

		$rules['editbutton'] = !$post['hidden'] && !$rules['closed']
			&& ($rules['isbyuser'] || (!in_array($permiterror_edit, $button_errors) && (!$post['queued'])));
		$rules['editable'] = $rules['editbutton'] && ($rules['isbyuser'] || !$permiterror_edit);

		$rules['retagcatbutton'] = $post['basetype'] == 'P' && (qa_using_tags() || qa_using_categories())
			&& !$post['hidden'] && ($rules['isbyuser'] || !in_array($permiterror_retagcat, $button_errors));
		$rules['retagcatable'] = $rules['retagcatbutton'] && ($rules['isbyuser'] || !$permiterror_retagcat);

		if ($rules['editbutton'] && $rules['retagcatbutton']) {
			// only show one button since they lead to the same form
			if ($rules['retagcatable'] && !$rules['editable'])
				$rules['editbutton'] = false; // if we can do this without getting an error, show that as the title
			else
				$rules['retagcatbutton'] = false;
		}

		$rules['aselectable'] = $post['type'] == 'P' && !qa_user_permit_error($rules['isbyuser'] ? null : 'permit_select_a', null, $userlevel, true, $userfields);

		$rules['flagbutton'] = qa_opt('flagging_of_posts') && !$rules['isbyuser'] && !$post['hidden'] && !$post['queued']
			&& !@$post['userflag'] && !in_array($permiterror_flag, $button_errors);
		$rules['flagtohide'] = $rules['flagbutton'] && !$permiterror_flag && ($post['flagcount'] + 1) >= qa_opt('flagging_hide_after');
		$rules['unflaggable'] = @$post['userflag'] && !$post['hidden'];
		$rules['clearflaggable'] = $post['flagcount'] >= (@$post['userflag'] ? 2 : 1) && !$permiterror_hide_show;

		// Other actions only show the button if it's immediately possible

		$notclosedbyother = !($rules['closed'] && isset($post['closedbyid']) && !$rules['authorlast']);
		$nothiddenbyother = !($post['hidden'] && !$rules['authorlast']);

		$rules['closeable'] = qa_opt('allow_close_articles') && $post['type'] == 'P' && !$rules['closed'] && $permiterror_close_open === false;
		// cannot reopen a article if it's been hidden, or if it was closed by someone else and you don't have global closing permissions
		$rules['reopenable'] = $rules['closed'] && isset($post['closedbyid']) && $permiterror_close_open === false && !$post['hidden']
			&& ($notclosedbyother || !qa_user_permit_error('permit_close_q', null, $userlevel, true, $userfields));

		$rules['moderatable'] = $post['queued'] && !$permiterror_moderate;
		// cannot hide a article if it was closed by someone else and you don't have global hiding permissions
		$rules['hideable'] = !$post['hidden'] && ($rules['isbyuser'] || !$post['queued']) && !$permiterror_hide_show_self
			&& ($notclosedbyother || !$permiterror_hide_show);
		// means post can be reshown immediately without checking whether it needs moderation
		$rules['reshowimmed'] = $post['hidden'] && !$permiterror_hide_show;
		// cannot reshow a article if it was hidden by someone else, or if it has flags - unless you have global hide/show permissions
		$rules['reshowable'] = $post['hidden'] && (!$permiterror_hide_show_self) &&
			($rules['reshowimmed'] || ($nothiddenbyother && !$post['flagcount']));

		$rules['deleteable'] = $post['hidden'] && !qa_user_permit_error('permit_delete_hidden', null, $userlevel, true, $userfields);
		$rules['claimable'] = !isset($post['userid']) && isset($userid) && strlen(@$post['cookieid']) && (strcmp(@$post['cookieid'], $cookieid) == 0)
			&& !($post['basetype'] == 'P' ? $permiterror_post_q : ($post['basetype'] == 'C' ? $permiterror_post_a : $permiterror_post_c));
		$rules['followable'] = $post['type'] == 'C' ? qa_opt('follow_on_as') : false;

		// Check for claims that could break rules about self commenting and multiple comments

		if ($rules['claimable'] && $post['basetype'] == 'C') {
			if (!qa_opt('allow_self_comment') && isset($parentpost) && qa_post_is_by_user($parentpost, $userid, $cookieid))
				$rules['claimable'] = false;

			if (isset($siblingposts) && !qa_opt('allow_multi_comments')) {
				foreach ($siblingposts as $siblingpost) {
					if ($siblingpost['parentid'] == $post['parentid'] && $siblingpost['basetype'] == 'C' && qa_post_is_by_user($siblingpost, $userid, $cookieid))
						$rules['claimable'] = false;
				}
			}
		}

		// Now make any changes based on the child posts

		if (isset($childposts)) {
			foreach ($childposts as $childpost) {
				if ($childpost['parentid'] == $post['postid']) {
					// this post has replys
					$rules['deleteable'] = false;

					if ($childpost['basetype'] == 'C' && qa_post_is_by_user($childpost, $userid, $cookieid)) {
						if (!qa_opt('allow_multi_comments'))
							$rules['answerbutton'] = false;

						if (!qa_opt('allow_self_comment'))
							$rules['claimable'] = false;
					}
				}

				if ($childpost['closedbyid'] == $post['postid']) {
					// other articles are closed as duplicates of this one
					$rules['deleteable'] = false;
				}
			}
		}

		// Return the resulting rules

		return $rules;
	}
	
	function qa_page_q_add_a_form(&$qa_content, $formid, $captchareason, $question, $in, $errors, $loadnow, $formrequested)
{
	// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the answer button being shown, in qa_page_q_post_rules(...)

	switch (qa_user_post_permit_error('permit_post_a', $question, QA_LIMIT_ANSWERS)) {
		case 'login':
			$form = array(
				'title' => qa_insert_login_links(qa_lang_html('question/answer_must_login'), qa_request()),
			);
			break;

		case 'confirm':
			$form = array(
				'title' => qa_insert_login_links(qa_lang_html('question/answer_must_confirm'), qa_request()),
			);
			break;

		case 'approve':
			$form = array(
				'title' => strtr(qa_lang_html('question/answer_must_be_approved'), array(
					'^1' => '<a href="' . qa_path_html('account') . '">',
					'^2' => '</a>',
				)),
			);
			break;

		case 'limit':
			$form = array(
				'title' => qa_lang_html('question/answer_limit'),
			);
			break;

		default:
			$form = array(
				'title' => qa_lang_html('users/no_permission'),
			);
			break;

		case false:
			$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_as');
			$editor = qa_load_editor(@$in['content'], @$in['format'], $editorname);

			if (method_exists($editor, 'update_script'))
				$updatescript = $editor->update_script('a_content');
			else
				$updatescript = '';

			$custom = qa_opt('show_custom_answer') ? trim(qa_opt('custom_answer')) : '';

			$form = array(
				'tags' => 'method="post" action="' . qa_self_html() . '" name="a_form"',

				'title' => qa_lang_html('question/your_answer_title'),

				'fields' => array(
					'custom' => array(
						'type' => 'custom',
						'note' => $custom,
					),

					'content' => array_merge(
						qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'a_content', 12, $formrequested, $loadnow),
						array(
							'error' => qa_html(@$errors['content']),
						)
					),
				),

				'buttons' => array(
					'answer' => array(
						'tags' => 'onclick="' . $updatescript . ' return qa_submit_answer(' . qa_js($question['postid']) . ', this);"',
						'label' => qa_lang_html('question/add_answer_button'),
					),
				),

				'hidden' => array(
					'a_editor' => qa_html($editorname),
					'a_doadd' => '1',
					'code' => qa_get_form_security_code('answer-' . $question['postid']),
				),
			);

			if (!strlen($custom))
				unset($form['fields']['custom']);

			if ($formrequested || !$loadnow)
				$form['buttons']['cancel'] = array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				);

			if (!qa_is_logged_in() && qa_opt('allow_anonymous_naming'))
				qa_set_up_name_field($qa_content, $form['fields'], @$in['name'], 'a_');

			qa_set_up_notify_fields($qa_content, $form['fields'], 'A', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), @$in['email'], @$errors['email'], 'a_');

			$onloads = array();

			if ($captchareason) {
				$captchaloadscript = qa_set_up_captcha_field($qa_content, $form['fields'], $errors, qa_captcha_reason_note($captchareason));

				if (strlen($captchaloadscript))
					$onloads[] = 'document.getElementById(' . qa_js($formid) . ').qa_show = function() { ' . $captchaloadscript . ' };';
			}

			if (!$loadnow) {
				if (method_exists($editor, 'load_script'))
					$onloads[] = 'document.getElementById(' . qa_js($formid) . ').qa_load = function() { ' . $editor->load_script('a_content') . ' };';

				$form['buttons']['cancel']['tags'] .= ' onclick="return qa_toggle_element();"';
			}

			if (!$formrequested) {
				if (method_exists($editor, 'focus_script'))
					$onloads[] = 'document.getElementById(' . qa_js($formid) . ').qa_focus = function() { ' . $editor->focus_script('a_content') . ' };';
			}

			if (count($onloads)) {
				$qa_content['script_onloads'][] = $onloads;
			}

			break;
	}

	$form['id'] = $formid;
	$form['collapse'] = !$loadnow;
	$form['style'] = 'tall';

	return $form;
}

function qa_page_q_add_c_form(&$qa_content, $question, $parent, $formid, $captchareason, $in, $errors, $loadfocusnow)
{
	// The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the comment button being shown, in qa_page_q_post_rules(...)

	switch (qa_user_post_permit_error('permit_post_c', $parent, QA_LIMIT_COMMENTS)) {
		case 'login':
			$form = array(
				'title' => qa_insert_login_links(qa_lang_html('question/comment_must_login'), qa_request()),
			);
			break;

		case 'confirm':
			$form = array(
				'title' => qa_insert_login_links(qa_lang_html('question/comment_must_confirm'), qa_request()),
			);
			break;

		case 'approve':
			$form = array(
				'title' => strtr(qa_lang_html('question/comment_must_be_approved'), array(
					'^1' => '<a href="' . qa_path_html('account') . '">',
					'^2' => '</a>',
				)),
			);
			break;

		case 'limit':
			$form = array(
				'title' => qa_lang_html('question/comment_limit'),
			);
			break;

		default:
			$form = array(
				'title' => qa_lang_html('users/no_permission'),
			);
			break;

		case false:
			$prefix = 'c' . $parent['postid'] . '_';

			$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_cs');
			$editor = qa_load_editor(@$in['content'], @$in['format'], $editorname);

			if (method_exists($editor, 'update_script'))
				$updatescript = $editor->update_script($prefix . 'content');
			else
				$updatescript = '';

			$custom = qa_opt('show_custom_comment') ? trim(qa_opt('custom_comment')) : '';

			$form = array(
				'tags' => 'method="post" action="' . qa_self_html() . '" name="c_form_' . qa_html($parent['postid']) . '"',

				'title' => qa_lang_html(($question['postid'] == $parent['postid']) ? 'question/your_comment_q' : 'question/your_comment_a'),

				'fields' => array(
					'custom' => array(
						'type' => 'custom',
						'note' => $custom,
					),

					'content' => array_merge(
						qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], $prefix . 'content', 4, $loadfocusnow, $loadfocusnow),
						array(
							'error' => qa_html(@$errors['content']),
						)
					),
				),

				'buttons' => array(
					'comment' => array(
						'tags' => 'onclick="' . $updatescript . ' return qa_submit_comment(' . qa_js($question['postid']) . ', ' . qa_js($parent['postid']) . ', this);"',
						'label' => qa_lang_html('question/add_comment_button'),
					),

					'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => qa_lang_html('main/cancel_button'),
					),
				),

				'hidden' => array(
					$prefix . 'editor' => qa_html($editorname),
					$prefix . 'doadd' => '1',
					$prefix . 'code' => qa_get_form_security_code('comment-' . $parent['postid']),
				),
			);

			if (!strlen($custom))
				unset($form['fields']['custom']);

			if (!qa_is_logged_in() && qa_opt('allow_anonymous_naming'))
				qa_set_up_name_field($qa_content, $form['fields'], @$in['name'], $prefix);

			qa_set_up_notify_fields($qa_content, $form['fields'], 'C', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'), $in['email'], @$errors['email'], $prefix);

			$onloads = array();

			if ($captchareason) {
				$captchaloadscript = qa_set_up_captcha_field($qa_content, $form['fields'], $errors, qa_captcha_reason_note($captchareason));

				if (strlen($captchaloadscript))
					$onloads[] = 'document.getElementById(' . qa_js($formid) . ').qa_show = function() { ' . $captchaloadscript . ' };';
			}

			if (!$loadfocusnow) {
				if (method_exists($editor, 'load_script'))
					$onloads[] = 'document.getElementById(' . qa_js($formid) . ').qa_load = function() { ' . $editor->load_script($prefix . 'content') . ' };';
				if (method_exists($editor, 'focus_script'))
					$onloads[] = 'document.getElementById(' . qa_js($formid) . ').qa_focus = function() { ' . $editor->focus_script($prefix . 'content') . ' };';

				$form['buttons']['cancel']['tags'] .= ' onclick="return qa_toggle_element()"';
			}

			if (count($onloads)) {
				$qa_content['script_onloads'][] = $onloads;
			}

			break;
	}

	$form['id'] = $formid;
	$form['collapse'] = !$loadfocusnow;
	$form['style'] = 'tall';

	return $form;
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
