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

	function bp_page_p_permit_edit($post, $permitoption, &$error, $permitoption2 = null)
	{
		$permiterror = qa_user_post_permit_error($post['isbyuser'] ? null : $permitoption, $post);
		// if it's by the user, this will only check whether they are blocked

		if ($permiterror && isset($permitoption2)) {
			$permiterror2 = qa_user_post_permit_error($post['isbyuser'] ? null : $permitoption2, $post);

			if ($permiterror == 'level' || $permiterror == 'approve' || !$permiterror2) // if it's a less strict error
				$permiterror = $permiterror2;
		}

		switch ($permiterror) {
			case 'login':
				$error = qa_insert_login_links(qa_lang_html('article/edit_must_login'), qa_request());
				break;

			case 'confirm':
				$error = qa_insert_login_links(qa_lang_html('article/edit_must_confirm'), qa_request());
				break;

			default:
				$error = qa_lang_html('users/no_permission');
				break;

			case false:
				break;
		}

		return !$permiterror;
	}


	/*
		Returns a $qa_content form for editing the article and sets up other parts of $qa_content accordingly
	*/
	function qa_page_q_edit_q_form(&$qa_content, $article, $in, $errors, $completetags, $categories)
	{
		$form = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'style' => 'tall',

			'fields' => array(
				'title' => array(
					'type' => $article['editable'] ? 'text' : 'static',
					'label' => qa_lang_html('article/q_title_label'),
					'tags' => 'name="q_title"',
					'value' => qa_html(($article['editable'] && isset($in['title'])) ? $in['title'] : $article['title']),
					'error' => qa_html(@$errors['title']),
				),

				'category' => array(
					'label' => qa_lang_html('article/q_category_label'),
					'error' => qa_html(@$errors['categoryid']),
				),

				'content' => array(
					'label' => qa_lang_html('article/q_content_label'),
					'error' => qa_html(@$errors['content']),
				),

				'extra' => array(
					'label' => qa_html(qa_opt('extra_field_prompt')),
					'tags' => 'name="q_extra"',
					'value' => qa_html(isset($in['extra']) ? $in['extra'] : $article['extra']),
					'error' => qa_html(@$errors['extra']),
				),

				'tags' => array(
					'error' => qa_html(@$errors['tags']),
				),

			),

			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false);"',
					'label' => qa_lang_html('main/save_button'),
				),

				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),

			'hidden' => array(
				'q_dosave' => '1',
				'code' => qa_get_form_security_code('edit-' . $article['postid']),
			),
		);

		if ($article['editable']) {
			$content = isset($in['content']) ? $in['content'] : $article['content'];
			$format = isset($in['format']) ? $in['format'] : $article['format'];

			$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_qs');
			$editor = qa_load_editor($content, $format, $editorname);

			$form['fields']['content'] = array_merge($form['fields']['content'],
				qa_editor_load_field($editor, $qa_content, $content, $format, 'q_content', 12, true));

			if (method_exists($editor, 'update_script'))
				$form['buttons']['save']['tags'] = 'onclick="qa_show_waiting_after(this, false); ' . $editor->update_script('q_content') . '"';

			$form['hidden']['q_editor'] = qa_html($editorname);

		} else
			unset($form['fields']['content']);

		if (qa_using_categories() && count($categories) && $article['retagcatable']) {
			qa_set_up_category_field($qa_content, $form['fields']['category'], 'q_category', $categories,
				isset($in['categoryid']) ? $in['categoryid'] : $article['categoryid'],
				qa_opt('allow_no_category') || !isset($article['categoryid']), qa_opt('allow_no_sub_category'));
		} else {
			unset($form['fields']['category']);
		}

		if (!($article['editable'] && qa_opt('extra_field_active')))
			unset($form['fields']['extra']);

		if (qa_using_tags() && $article['retagcatable']) {
			qa_set_up_tag_field($qa_content, $form['fields']['tags'], 'q_tags', isset($in['tags']) ? $in['tags'] : qa_tagstring_to_tags($article['tags']),
				array(), $completetags, qa_opt('page_size_ask_tags'));
		} else {
			unset($form['fields']['tags']);
		}

		if ($article['isbyuser']) {
			if (!qa_is_logged_in() && qa_opt('allow_anonymous_naming'))
				qa_set_up_name_field($qa_content, $form['fields'], isset($in['name']) ? $in['name'] : @$article['name'], 'q_');

			qa_set_up_notify_fields($qa_content, $form['fields'], 'P', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : !empty($article['notify']),
				isset($in['email']) ? $in['email'] : @$article['notify'], @$errors['email'], 'q_');
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $article)) {
			$form['fields']['silent'] = array(
				'type' => 'checkbox',
				'label' => qa_lang_html('article/save_silent_label'),
				'tags' => 'name="q_silent"',
				'value' => qa_html(@$in['silent']),
			);
		}

		return $form;
	}


	/*
		Processes a POSTed form for editing the article and returns true if successful
	*/
	function qa_page_q_edit_q_submit($article, $comments, $replysfollows, $closepost, &$in, &$errors)
	{
		$in = array();

		if ($article['editable']) {
			$in['title'] = qa_get_post_title('q_title');
			qa_get_post_content('q_editor', 'q_content', $in['editor'], $in['content'], $in['format'], $in['text']);
			$in['extra'] = qa_opt('extra_field_active') ? qa_post_text('q_extra') : null;
		}

		if ($article['retagcatable']) {
			if (qa_using_tags())
				$in['tags'] = qa_get_tags_field_value('q_tags');

			if (qa_using_categories())
				$in['categoryid'] = qa_get_category_field_value('q_category');
		}

		if (array_key_exists('categoryid', $in)) { // need to check if we can move it to that category, and if we need moderation
			$categories = qa_db_select_with_pending(qa_db_category_nav_selectspec($in['categoryid'], true));
			$categoryids = array_keys(qa_category_path($categories, $in['categoryid']));
			$userlevel = qa_user_level_for_categories($categoryids);

		} else
			$userlevel = null;

		if ($article['isbyuser']) {
			$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text('q_name') : null;
			$in['notify'] = qa_post_text('q_notify') !== null;
			$in['email'] = qa_post_text('q_email');
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $article))
			$in['silent'] = qa_post_text('q_silent');

		// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

		$errors = array();

		if (!qa_check_form_security_code('edit-' . $article['postid'], qa_post_text('code')))
			$errors['page'] = qa_lang_html('misc/form_security_again');

		else {
			$in['queued'] = qa_opt('moderate_edited_again') && qa_user_moderation_reason($userlevel);

			$filtermodules = qa_load_modules_with('filter', 'filter_article');
			foreach ($filtermodules as $filtermodule) {
				$oldin = $in;
				$filtermodule->filter_article($in, $errors, $article);

				if ($article['editable'])
					qa_update_post_text($in, $oldin);
			}

			if (array_key_exists('categoryid', $in) && strcmp($in['categoryid'], $article['categoryid'])) {
				if (qa_user_permit_error('permit_post_q', null, $userlevel))
					$errors['categoryid'] = qa_lang_html('article/category_ask_not_allowed');
			}

			if (empty($errors)) {
				$userid = qa_get_logged_in_userid();
				$handle = qa_get_logged_in_handle();
				$cookieid = qa_cookie_get();

				// now we fill in the missing values in the $in array, so that we have everything we need for qa_article_set_content()
				// we do things in this way to avoid any risk of a validation failure on elements the user can't see (e.g. due to admin setting changes)

				if (!$article['editable']) {
					$in['title'] = $article['title'];
					$in['content'] = $article['content'];
					$in['format'] = $article['format'];
					$in['text'] = qa_viewer_text($in['content'], $in['format']);
					$in['extra'] = $article['extra'];
				}

				if (!isset($in['tags']))
					$in['tags'] = qa_tagstring_to_tags($article['tags']);

				if (!array_key_exists('categoryid', $in))
					$in['categoryid'] = $article['categoryid'];

				if (!isset($in['silent']))
					$in['silent'] = false;

				$setnotify = $article['isbyuser'] ? qa_combine_notify_email($article['userid'], $in['notify'], $in['email']) : $article['notify'];

				qa_article_set_content($article, $in['title'], $in['content'], $in['format'], $in['text'], qa_tags_to_tagstring($in['tags']),
					$setnotify, $userid, $handle, $cookieid, $in['extra'], @$in['name'], $in['queued'], $in['silent']);

				if (qa_using_categories() && strcmp($in['categoryid'], $article['categoryid'])) {
					qa_article_set_category($article, $in['categoryid'], $userid, $handle, $cookieid,
						$comments, $replysfollows, $closepost, $in['silent']);
				}

				return true;
			}
		}

		return false;
	}


	/*
		Returns a $qa_content form for closing the article and sets up other parts of $qa_content accordingly
	*/
	function qa_page_q_close_q_form(&$qa_content, $article, $id, $in, $errors)
	{
		$form = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'id' => $id,

			'style' => 'tall',

			'title' => qa_lang_html('article/close_form_title'),

			'fields' => array(
				'details' => array(
					'tags' => 'name="q_close_details" id="q_close_details"',
					'label' =>
						'<span id="close_label_other">' . qa_lang_html('article/close_reason_title') . '</span>',
					'value' => @$in['details'],
					'error' => qa_html(@$errors['details']),
				),
			),

			'buttons' => array(
				'close' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false);"',
					'label' => qa_lang_html('article/close_form_button'),
				),

				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),

			'hidden' => array(
				'doclose' => '1',
				'code' => qa_get_form_security_code('close-' . $article['postid']),
			),
		);

		$qa_content['focusid'] = 'q_close_details';

		return $form;
	}


	/*
		Processes a POSTed form for closing the article and returns true if successful
	*/
	function qa_page_q_close_q_submit($article, $closepost, &$in, &$errors)
	{
		$in = array(
			'details' => trim(qa_post_text('q_close_details')),
		);

		$userid = qa_get_logged_in_userid();
		$handle = qa_get_logged_in_handle();
		$cookieid = qa_cookie_get();

		$sanitizedUrl = filter_var($in['details'], FILTER_SANITIZE_URL);
		$isduplicateurl = filter_var($sanitizedUrl, FILTER_VALIDATE_URL);

		if (!qa_check_form_security_code('close-' . $article['postid'], qa_post_text('code'))) {
			$errors['details'] = qa_lang_html('misc/form_security_again');
		} elseif ($isduplicateurl) {
			// be liberal in what we accept, but there are two potential unlikely pitfalls here:
			// a) URLs could have a fixed numerical path, e.g. http://qa.mysite.com/1/478/...
			// b) There could be a article title which is just a number, e.g. http://qa.mysite.com/478/12345/...
			// so we check if more than one article could match, and if so, show an error

			$parts = preg_split('|[=/&]|', $sanitizedUrl, -1, PREG_SPLIT_NO_EMPTY);
			$keypostids = array();

			foreach ($parts as $part) {
				if (preg_match('/^[0-9]+$/', $part))
					$keypostids[$part] = true;
			}

			$articleids = qa_db_posts_filter_q_postids(array_keys($keypostids));

			if (count($articleids) == 1 && $articleids[0] != $article['postid']) {
				qa_article_close_duplicate($article, $closepost, $articleids[0], $userid, $handle, $cookieid);
				return true;

			} else
				$errors['details'] = qa_lang('article/close_duplicate_error');

		} else {
			if (strlen($in['details']) > 0) {
				qa_article_close_other($article, $closepost, $in['details'], $userid, $handle, $cookieid);
				return true;

			} else
				$errors['details'] = qa_lang('main/field_required');
		}

		return false;
	}


	/*
		Returns a $qa_content form for editing an comment and sets up other parts of $qa_content accordingly
	*/
	function bp_page_p_edit_c_form(&$qa_content, $id, $comment, $article, $comments, $replysfollows, $in, $errors)
	{
		require_once QA_INCLUDE_DIR . 'util/string.php';

		$commentid = $comment['postid'];
		$prefix = 'a' . $commentid . '_';

		$content = isset($in['content']) ? $in['content'] : $comment['content'];
		$format = isset($in['format']) ? $in['format'] : $comment['format'];

		$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_as');
		$editor = qa_load_editor($content, $format, $editorname);

		$hasreplys = false;
		foreach ($replysfollows as $replyfollow) {
			if ($replyfollow['parentid'] == $commentid)
				$hasreplys = true;
		}

		$form = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'id' => $id,

			'title' => qa_lang_html('article/edit_a_title'),

			'style' => 'tall',

			'fields' => array(
				'content' => array_merge(
					qa_editor_load_field($editor, $qa_content, $content, $format, $prefix . 'content', 12),
					array(
						'error' => qa_html(@$errors['content']),
					)
				),
			),

			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false); ' .
						(method_exists($editor, 'update_script') ? $editor->update_script($prefix . 'content') : '') . '"',
					'label' => qa_lang_html('main/save_button'),
				),

				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),

			'hidden' => array(
				$prefix . 'editor' => qa_html($editorname),
				$prefix . 'dosave' => '1',
				$prefix . 'code' => qa_get_form_security_code('edit-' . $commentid),
			),
		);

		// Show option to convert this comment to a reply, if appropriate

		$replyonoptions = array();

		$lastbeforeid = $article['postid']; // used to find last post created before this comment - this is default given
		$lastbeforetime = $article['created'];

		if ($article['replyable']) {
			$replyonoptions[$article['postid']] =
				qa_lang_html('article/reply_on_q') . qa_html(qa_shorten_string_line($article['title'], 80));
		}

		foreach ($comments as $othercomment) {
			if ($othercomment['postid'] != $commentid && $othercomment['created'] < $comment['created'] && $othercomment['replyable'] && !$othercomment['hidden']) {
				$replyonoptions[$othercomment['postid']] =
					qa_lang_html('article/reply_on_a') . qa_html(qa_shorten_string_line(qa_viewer_text($othercomment['content'], $othercomment['format']), 80));

				if ($othercomment['created'] > $lastbeforetime) {
					$lastbeforeid = $othercomment['postid'];
					$lastbeforetime = $othercomment['created'];
				}
			}
		}

		if (count($replyonoptions)) {
			$form['fields']['toreply'] = array(
				'tags' => 'name="' . $prefix . 'dotoc" id="' . $prefix . 'dotoc"',
				'label' => '<span id="' . $prefix . 'toshown">' . qa_lang_html('article/a_convert_to_c_on') . '</span>' .
					'<span id="' . $prefix . 'tohidden" style="display:none;">' . qa_lang_html('article/a_convert_to_c') . '</span>',
				'type' => 'checkbox',
				'tight' => true,
			);

			$form['fields']['replyon'] = array(
				'tags' => 'name="' . $prefix . 'replyon"',
				'id' => $prefix . 'replyon',
				'type' => 'select',
				'note' => qa_lang_html($hasreplys ? 'article/a_convert_warn_cs' : 'article/a_convert_warn'),
				'options' => $replyonoptions,
				'value' => @$replyonoptions[$lastbeforeid],
			);

			qa_set_display_rules($qa_content, array(
				$prefix . 'replyon' => $prefix . 'dotoc',
				$prefix . 'toshown' => $prefix . 'dotoc',
				$prefix . 'tohidden' => '!' . $prefix . 'dotoc',
			));
		}

		// Show name and notification field if appropriate

		if ($comment['isbyuser']) {
			if (!qa_is_logged_in() && qa_opt('allow_anonymous_naming'))
				qa_set_up_name_field($qa_content, $form['fields'], isset($in['name']) ? $in['name'] : @$comment['name'], $prefix);

			qa_set_up_notify_fields($qa_content, $form['fields'], 'C', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : !empty($comment['notify']),
				isset($in['email']) ? $in['email'] : @$comment['notify'], @$errors['email'], $prefix);
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $comment)) {
			$form['fields']['silent'] = array(
				'type' => 'checkbox',
				'label' => qa_lang_html('article/save_silent_label'),
				'tags' => 'name="' . $prefix . 'silent"',
				'value' => qa_html(@$in['silent']),
			);
		}

		return $form;
	}


	/*
		Processes a POSTed form for editing an comment and returns the new type of the post if successful
	*/
	function qa_page_q_edit_a_submit($comment, $article, $comments, $replysfollows, &$in, &$errors)
	{
		$commentid = $comment['postid'];
		$prefix = 'a' . $commentid . '_';

		$in = array(
			'dotoc' => qa_post_text($prefix . 'dotoc'),
			'replyon' => qa_post_text($prefix . 'replyon'),
		);

		if ($comment['isbyuser']) {
			$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text($prefix . 'name') : null;
			$in['notify'] = qa_post_text($prefix . 'notify') !== null;
			$in['email'] = qa_post_text($prefix . 'email');
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $comment))
			$in['silent'] = qa_post_text($prefix . 'silent');

		qa_get_post_content($prefix . 'editor', $prefix . 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

		// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

		$errors = array();

		if (!qa_check_form_security_code('edit-' . $commentid, qa_post_text($prefix . 'code')))
			$errors['content'] = qa_lang_html('misc/form_security_again');

		else {
			$in['queued'] = qa_opt('moderate_edited_again') && qa_user_moderation_reason(qa_user_level_for_post($comment));

			$filtermodules = qa_load_modules_with('filter', 'filter_comment');
			foreach ($filtermodules as $filtermodule) {
				$oldin = $in;
				$filtermodule->filter_comment($in, $errors, $article, $comment);
				qa_update_post_text($in, $oldin);
			}

			if (empty($errors)) {
				$userid = qa_get_logged_in_userid();
				$handle = qa_get_logged_in_handle();
				$cookieid = qa_cookie_get();

				if (!isset($in['silent']))
					$in['silent'] = false;

				$setnotify = $comment['isbyuser'] ? qa_combine_notify_email($comment['userid'], $in['notify'], $in['email']) : $comment['notify'];

				if ($in['dotoc'] && (
						(($in['replyon'] == $article['postid']) && $article['replyable']) ||
						(($in['replyon'] != $commentid) && @$comments[$in['replyon']]['replyable'])
					)
				) { // convert to a reply

					if (qa_user_limits_remaining(QA_LIMIT_COMMENTS)) { // already checked 'permit_post_c'
						qa_comment_to_reply($comment, $in['replyon'], $in['content'], $in['format'], $in['text'], $setnotify,
							$userid, $handle, $cookieid, $article, $comments, $replysfollows, @$in['name'], $in['queued'], $in['silent']);

						return 'R'; // to signify that redirect should be to the reply

					} else
						$errors['content'] = qa_lang_html('article/reply_limit'); // not really best place for error, but it will do

				} else {
					qa_comment_set_content($comment, $in['content'], $in['format'], $in['text'], $setnotify,
						$userid, $handle, $cookieid, $article, @$in['name'], $in['queued'], $in['silent']);

					return 'C';
				}
			}
		}

		return null;
	}


	/*
		Processes a request to add a reply to $parent, with antecedent $article, checking for permissions errors
	*/
	function qa_page_q_do_reply($article, $parent, $replysfollows, $pagestart, $usecaptcha, &$cnewin, &$cnewerrors, &$formtype, &$formpostid, &$error)
	{
		// The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
		// The other option ('level') prevents the reply button being shown, in qa_page_q_post_rules(...)

		$parentid = $parent['postid'];

		switch (qa_user_post_permit_error('permit_post_c', $parent, QA_LIMIT_COMMENTS)) {
			case 'login':
				$error = qa_insert_login_links(qa_lang_html('article/reply_must_login'), qa_request());
				break;

			case 'confirm':
				$error = qa_insert_login_links(qa_lang_html('article/reply_must_confirm'), qa_request());
				break;

			case 'approve':
				$error = strtr(qa_lang_html('article/reply_must_be_approved'), array(
					'^1' => '<a href="' . qa_path_html('account') . '">',
					'^2' => '</a>',
				));
				break;

			case 'limit':
				$error = qa_lang_html('article/reply_limit');
				break;

			default:
				$error = qa_lang_html('users/no_permission');
				break;

			case false:
				if (qa_clicked('c' . $parentid . '_doadd')) {
					$replyid = qa_page_q_add_c_submit($article, $parent, $replysfollows, $usecaptcha, $cnewin[$parentid], $cnewerrors[$parentid]);

					if (isset($replyid))
						qa_page_q_refresh($pagestart, null, 'R', $replyid);

					else {
						$formtype = 'c_add';
						$formpostid = $parentid; // show form again
					}

				} else {
					$formtype = 'c_add';
					$formpostid = $parentid; // show form first time
				}
				break;
		}
	}


	/*
		Returns a $qa_content form for editing a reply and sets up other parts of $qa_content accordingly
	*/
	function qa_page_q_edit_c_form(&$qa_content, $id, $reply, $in, $errors)
	{
		$replyid = $reply['postid'];
		$prefix = 'c' . $replyid . '_';

		$content = isset($in['content']) ? $in['content'] : $reply['content'];
		$format = isset($in['format']) ? $in['format'] : $reply['format'];

		$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_cs');
		$editor = qa_load_editor($content, $format, $editorname);

		$form = array(
			'tags' => 'method="post" action="' . qa_self_html() . '"',

			'id' => $id,

			'title' => qa_lang_html('article/edit_c_title'),

			'style' => 'tall',

			'fields' => array(
				'content' => array_merge(
					qa_editor_load_field($editor, $qa_content, $content, $format, $prefix . 'content', 4, true),
					array(
						'error' => qa_html(@$errors['content']),
					)
				),
			),

			'buttons' => array(
				'save' => array(
					'tags' => 'onclick="qa_show_waiting_after(this, false); ' .
						(method_exists($editor, 'update_script') ? $editor->update_script($prefix . 'content') : '') . '"',
					'label' => qa_lang_html('main/save_button'),
				),

				'cancel' => array(
					'tags' => 'name="docancel"',
					'label' => qa_lang_html('main/cancel_button'),
				),
			),

			'hidden' => array(
				$prefix . 'editor' => qa_html($editorname),
				$prefix . 'dosave' => '1',
				$prefix . 'code' => qa_get_form_security_code('edit-' . $replyid),
			),
		);

		if ($reply['isbyuser']) {
			if (!qa_is_logged_in() && qa_opt('allow_anonymous_naming'))
				qa_set_up_name_field($qa_content, $form['fields'], isset($in['name']) ? $in['name'] : @$reply['name'], $prefix);

			qa_set_up_notify_fields($qa_content, $form['fields'], 'R', qa_get_logged_in_email(),
				isset($in['notify']) ? $in['notify'] : !empty($reply['notify']),
				isset($in['email']) ? $in['email'] : @$reply['notify'], @$errors['email'], $prefix);
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $reply)) {
			$form['fields']['silent'] = array(
				'type' => 'checkbox',
				'label' => qa_lang_html('article/save_silent_label'),
				'tags' => 'name="' . $prefix . 'silent"',
				'value' => qa_html(@$in['silent']),
			);
		}

		return $form;
	}


	/*
		Processes a POSTed form for editing a reply and returns true if successful
	*/
	function qa_page_q_edit_c_submit($reply, $article, $parent, &$in, &$errors)
	{
		$replyid = $reply['postid'];
		$prefix = 'c' . $replyid . '_';

		$in = array();

		if ($reply['isbyuser']) {
			$in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text($prefix . 'name') : null;
			$in['notify'] = qa_post_text($prefix . 'notify') !== null;
			$in['email'] = qa_post_text($prefix . 'email');
		}

		if (!qa_user_post_permit_error('permit_edit_silent', $reply))
			$in['silent'] = qa_post_text($prefix . 'silent');

		qa_get_post_content($prefix . 'editor', $prefix . 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

		// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

		$errors = array();

		if (!qa_check_form_security_code('edit-' . $replyid, qa_post_text($prefix . 'code')))
			$errors['content'] = qa_lang_html('misc/form_security_again');

		else {
			$in['queued'] = qa_opt('moderate_edited_again') && qa_user_moderation_reason(qa_user_level_for_post($reply));

			$filtermodules = qa_load_modules_with('filter', 'filter_reply');
			foreach ($filtermodules as $filtermodule) {
				$oldin = $in;
				$filtermodule->filter_reply($in, $errors, $article, $parent, $reply);
				qa_update_post_text($in, $oldin);
			}

			if (empty($errors)) {
				$userid = qa_get_logged_in_userid();
				$handle = qa_get_logged_in_handle();
				$cookieid = qa_cookie_get();

				if (!isset($in['silent']))
					$in['silent'] = false;

				$setnotify = $reply['isbyuser'] ? qa_combine_notify_email($reply['userid'], $in['notify'], $in['email']) : $reply['notify'];

				qa_reply_set_content($reply, $in['content'], $in['format'], $in['text'], $setnotify,
					$userid, $handle, $cookieid, $article, $parent, @$in['name'], $in['queued'], $in['silent']);

				return true;
			}
		}

		return false;
	}
	
	function bp_page_p_reply_follow_list($article, $parent, $replysfollows, $alwaysfull, $usershtml, $formrequested, $formpostid)
	{
		$parentid = $parent['postid'];
		$userid = qa_get_logged_in_userid();
		$cookieid = qa_cookie_get();

		$replylist = array(
			'tags' => 'id="c' . qa_html($parentid) . '_list"',
			'cs' => array(),
		);

		$showreplys = array();

		// $replysfollows contains ALL replys on the article and all comments, so here we filter the replys viewable for this context
		foreach ($replysfollows as $replyfollowid => $replyfollow) {
			$showreply = $replyfollow['parentid'] == $parentid && $replyfollow['viewable'] && $replyfollowid != $formpostid;
			// show hidden follow-on articles only if the parent is hidden
			if ($showreply && $replyfollow['basetype'] == 'P' && $replyfollow['hidden']) {
				$showreply = $parent['hidden'];
			}
			// show articles closed as duplicate of this one, only if this article is hidden
			$showduplicate = $article['hidden'] && $replyfollow['closedbyid'] == $parentid;

			if ($showreply || $showduplicate) {
				$showreplys[$replyfollowid] = $replyfollow;
			}
		}

		$countshowreplys = count($showreplys);

		if (!$alwaysfull && $countshowreplys > qa_opt('show_fewer_cs_from'))
			$skipfirst = $countshowreplys - qa_opt('show_fewer_cs_count');
		else
			$skipfirst = 0;

		if ($skipfirst == $countshowreplys) { // showing none
			if ($skipfirst == 1)
				$expandtitle = qa_lang_html('article/show_1_reply');
			else
				$expandtitle = qa_lang_html_sub('article/show_x_replys', $skipfirst);

		} else {
			if ($skipfirst == 1)
				$expandtitle = qa_lang_html('article/show_1_previous_reply');
			else
				$expandtitle = qa_lang_html_sub('article/show_x_previous_replys', $skipfirst);
		}

		if ($skipfirst > 0) {
			$replylist['cs'][$parentid] = array(
				'url' => qa_html('?state=showreplys-' . $parentid . '&show=' . $parentid . '#' . urlencode(qa_anchor($parent['basetype'], $parentid))),

				'expand_tags' => 'onclick="return qa_show_replys(' . qa_js($article['postid']) . ', ' . qa_js($parentid) . ', this);"',

				'title' => $expandtitle,
			);
		}

		foreach ($showreplys as $replyfollowid => $replyfollow) {
			if ($skipfirst > 0) {
				$skipfirst--;
			} elseif ($replyfollow['basetype'] == 'R') {
				$replylist['cs'][$replyfollowid] = qa_page_q_reply_view($article, $parent, $replyfollow, $usershtml, $formrequested);

			} elseif ($replyfollow['basetype'] == 'P') {
				$htmloptions = qa_post_html_options($replyfollow);
				$htmloptions['avatarsize'] = qa_opt('avatar_q_page_c_size');

				$replylist['cs'][$replyfollowid] = qa_post_html_fields($replyfollow, $userid, $cookieid, $usershtml, null, $htmloptions);
			}
		}

		if (!count($replylist['cs']))
			$replylist['hidden'] = true;

		return $replylist;
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
