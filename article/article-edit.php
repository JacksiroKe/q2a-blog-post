<?php
/*
 Blog Post by Jack Siro
 https://github.com/JaxiroKe/q2a-blog-post
 Description: Blog Post Plugin database checker and user pages manager
 */

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../../');
    exit;
}

function qa_blog_edit($userid, $blog_cats, $articleid)
{
    $findpost = qa_bp_post_find_by_postid($articleid);
    if (!count($findpost))
        return qa_article_not_found();

    $article = qa_db_select_with_pending(
        bp_db_full_post_selectspec($userid, $articleid),
    );

    $in = array();
    $in['catid'] = qa_clicked('dosave') ? qa_get_category_field_value('category') : qa_get('cat');
    $completetags = qa_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS);

    // Check for permission error
    $permiterror = qa_user_maximum_permit_error('bp_permit_post_p', QA_LIMIT_QUESTIONS);

    $qa_content = qa_content_prepare(false, array_keys(bp_cat_path($blog_cats, @$in['catid'])));

    if ($permiterror) {
        switch ($permiterror) {
            case 'login':
                $qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/write_must_login'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
                break;

            case 'confirm':
                $qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/write_must_confirm'), qa_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
                break;

            case 'limit':
                $qa_content['error'] = qa_lang_html('bp_lang/write_limit');
                break;

            case 'approve':
                $qa_content['error'] = strtr(qa_lang_html('bp_lang/write_must_be_approved'), array(
                    '^1' => '<a href="' . qa_path_html('account') . '">',
                    '^2' => '</a>',
                )
                );
                break;

            default:
                $qa_content['error'] = qa_lang_html('users/no_permission');
                break;
        }
        return $qa_content;
    }

    // Process input
    $captchareason = qa_user_captcha_reason();

    $in['title'] = $article['title'];
    $in['content'] = $article['content'];
    $in['catid'] = $article['catid'];

    $in['extra'] = qa_opt('extra_field_active') ? qa_post_text('extra') : null;
    if (qa_using_tags())
        $in['tags'] = qa_get_tags_field_value('tags');

    if (qa_clicked('dosave')) {
        require_once QA_INCLUDE_DIR . 'app/post-create.php';

        $catids = array_keys(bp_cat_path($blog_cats, @$in['catid']));
        $userlevel = bp_user_level_for_cats($catids);

        $in['name'] = qa_opt('allow_anonymous_naming') ? qa_post_text('name') : null;
        $in['notify'] = strlen(qa_post_text('notify')) > 0;
        $in['email'] = qa_post_text('email');
        $in['queued'] = qa_user_moderation_reason($userlevel) !== false;

        qa_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

        $errors = array();
        if (!qa_check_form_security_code('edit-' . $articleid, qa_post_text('code'))) {
            $errors['page'] = qa_lang_html('misc/form_security_again');
        } else {
            $filtermodules = qa_load_modules_with('filter', 'filter_article');
            foreach ($filtermodules as $filtermodule) {
                $oldin = $in;
                $filtermodule->filter_article($in, $errors, null);
                qa_update_post_text($in, $oldin);
            }

            if (qa_using_categories() && count($blog_cats) && (!qa_opt('bp_allow_no_blogcat')) && !isset($in['catid'])) {
                // check this here because we need to know count($blog_cats)
                $errors['catid'] = qa_lang_html('bp_lang/category_required');
            } elseif (qa_user_permit_error('bp_permit_post_p', null, $userlevel)) {
                $errors['catid'] = qa_lang_html('bp_lang/category_write_not_allowed');
            }

            if ($captchareason) {
                require_once QA_INCLUDE_DIR . 'app/captcha.php';
                qa_captcha_validate_post($errors);
            }

            if (empty($errors)) {
                // check if the article is already posted
                $testTitleWords = implode(' ', qa_string_to_words($in['title']));
                $testContentWords = implode(' ', qa_string_to_words($in['content']));
                $recentArticles = qa_db_select_with_pending(bp_db_ps_selectspec(null, 'created', 0, null, null, false, true, 5));

                foreach ($recentArticles as $article) {
                    if (!$article['hidden']) {
                        $pTitleWords = implode(' ', qa_string_to_words($article['title']));
                        $pContentWords = implode(' ', qa_string_to_words($article['content']));

                        if ($pTitleWords == $testTitleWords && $pContentWords == $testContentWords) {
                            $errors['page'] = qa_lang_html('bp_lang/duplicate_content');
                            break;
                        }
                    }
                }
            }

            if (empty($errors)) {
                $in['text'] = qa_viewer_text($in['content'], $in['format']);

                $title = qa_block_words_replace($in['title'], qa_get_block_words_preg());
                $slug = qa_slugify($title, qa_opt('q_urls_remove_accents'), qa_opt('q_urls_title_length'));
                $permalink = gmdate('Y/m/d', $article['created']) . '/' . $slug;

                bp_post_update($article, $userid, $in['title'], $permalink, $in['content'], $in['format'], $in['text'], isset($in['tags']) ? qa_tags_to_tagstring($in['tags']) : '', $in['notify'], $in['email'], $in['catid'], $in['extra'], $in['queued'], $in['name']);
                qa_redirect(bp_p_request($permalink));
            }
        }
    }

    $qa_content['title'] = qa_lang('bp_lang/edit_article') . $article['title'];
    $qa_content['error'] = @$errors['page'];

    $editorname = isset($in['editor']) ? $in['editor'] : qa_opt('bp_blog_editor');
    $editor = qa_load_editor(@$in['content'], @$in['format'], $editorname);

    $field = qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
    $field['label'] = qa_lang_html('bp_lang/content_label');
    $field['error'] = qa_html(@$errors['content']);

    $custom = qa_opt('bp_show_custom_write') ? trim(qa_opt('bp_custom_write')) : '';

    $qa_content['form'] = array(
        'tags' => 'name="write" method="post" action="' . qa_self_html() . '"',
        'style' => 'tall',

        'fields' => array(
            'custom' => array(
                'type' => 'custom',
                'note' => $custom,
            ),

            'title' => array(
                'label' => qa_lang_html('bp_lang/title_label'),
                'tags' => 'name="title" id="title" autocomplete="off"',
                'value' => qa_html(@$in['title']),
                'error' => qa_html(@$errors['title']),
            ),

            'similar' => array(
                'type' => 'custom',
                'html' => '<span id="similar"></span>',
            ),

            'content' => $field,
        ),

        'buttons' => array(
            'save' => array(
                'tags' => 'onclick="qa_show_waiting_after(this, false); ' .
                (method_exists($editor, 'update_script') ? $editor->update_script('content') : '') . '"',
                'label' => qa_lang_html('main/save_button'),
            ),

            'cancel' => array(
                'tags' => 'name="docancel"',
                'label' => qa_lang_html('main/cancel_button'),
            ),
        ),

        'hidden' => array(
            'code' => qa_get_form_security_code('edit-' . $articleid),
            'editor' => qa_html($editorname),
            'dosave' => '1',
        ),
    );

    if (!strlen($custom))
        unset($qa_content['form']['fields']['custom']);

    if (qa_opt('do_ask_check_qs') || qa_opt('do_example_tags')) {
        $qa_content['form']['fields']['title']['tags'] .= ' onchange="qa_title_change(this.value);"';

        if (strlen(@$in['title'])) {
            $qa_content['script_onloads'][] = 'qa_title_change(' . qa_js($in['title']) . ');';
        }
    }

    if (qa_using_categories() && count($blog_cats)) {
        $field = array(
            'label' => qa_lang_html('bp_lang/blogcat_label'),
            'error' => qa_html(@$errors['catid']),
        );

        bp_set_up_cat_field($qa_content, $field, 'category', $blog_cats, $in['catid'], true, true, null, false);
        if (!qa_opt('allow_no_category'))
            $field['options'][''] = '';
        qa_array_insert($qa_content['form']['fields'], 'content', array('category' => $field));
    }

    if (qa_opt('extra_field_active')) {
        $field = array(
            'label' => qa_html(qa_opt('extra_field_prompt')),
            'tags' => 'name="extra"',
            'value' => qa_html(@$in['extra']),
            'error' => qa_html(@$errors['extra']),
        );

        qa_array_insert($qa_content['form']['fields'], null, array('extra' => $field));
    }

    if (qa_using_tags()) {
        $field = array(
            'error' => qa_html(@$errors['tags']),
        );

        qa_set_up_tag_field(
            $qa_content,
            $field,
            'tags', isset($in['tags']) ? $in['tags'] : array(),
            array(),
            qa_opt('do_complete_tags') ? array_keys($completetags) : array(),
            qa_opt('page_size_ask_tags')
        );

        qa_array_insert($qa_content['form']['fields'], null, array('tags' => $field));
    }

    if (!isset($userid) && qa_opt('allow_anonymous_naming')) {
        qa_set_up_name_field($qa_content, $qa_content['form']['fields'], @$in['name']);
    }

    bp_setup_notify_fields(
        $qa_content, $qa_content['form']['fields'],
        'P',
        qa_get_logged_in_email(),
        isset($in['notify']) ? $in['notify'] : qa_opt('notify_users_default'),
        @$in['email'],
        @$errors['email']
    );

    if ($captchareason) {
        require_once QA_INCLUDE_DIR . 'app/captcha.php';
        qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
    }

    $qa_content['focusid'] = 'title';
    return $qa_content;
}
