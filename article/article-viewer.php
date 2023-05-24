<?php
/*
 Blog Post by Jack Siro
 https://github.com/JaxiroKe/q2a-blog-post
 Description: Basic and Database functions for the blog post plugin
 */

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../../');
    exit;
}

function qa_article_viewer($userid, $permalink)
{
    require_once QA_INCLUDE_DIR . 'app/cookies.php';
    require_once QA_INCLUDE_DIR . 'db/selects.php';
    require_once QA_INCLUDE_DIR . 'util/sort.php';
    require_once QA_INCLUDE_DIR . 'app/captcha.php';
    require_once QA_INCLUDE_DIR . 'app/updates.php';

    require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-view.php';
    require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-article.php';
    require_once QA_PLUGIN_DIR . 'q2a-blog-post/article/article-feed.php';
    $qa_content = qa_content_prepare();

    $findpost = qa_bp_post_find_by_permalink($permalink);
    if (!count($findpost))
        return qa_article_not_found();

    $articleid = $findpost[0];
    $cookieid = qa_cookie_get();
    $pagestate = qa_get_state();

    $articleData = qa_db_select_with_pending(
        bp_db_full_post_selectspec($userid, $articleid),
        bp_db_full_child_posts_selectspec($userid, $articleid),
        bp_db_full_c_child_posts_selectspec($userid, $articleid),
        bp_db_post_parent_p_selectspec($articleid),
        bp_db_post_close_post_selectspec($articleid),
        bp_db_post_duplicates_selectspec($articleid),
        bp_db_post_meta_selectspec($articleid, 'qa_q_extra'),
        bp_db_cat_nav_selectspec($articleid, true, true, true),
        isset($userid) ? qa_db_is_favorite_selectspec($userid, QA_ENTITY_QUESTION, $articleid) : null
    );
    list($article, $childposts, $cchildposts, $parentarticle, $closepost, $duplicateposts, $extravalue, $categories, $favorite) = $articleData;

    if ($article['basetype'] != 'P')
        $article = null;
    if (isset($article)) {
        $p_request = bp_p_request($permalink);
        if (trim($p_request, '/') !== trim(qa_request(), '/'))
            qa_redirect($p_request);

        $article['extra'] = $extravalue;

        $comments = bp_page_p_load_as($article, $childposts);
        $replysfollows = bp_page_p_load_c_follows($article, $childposts, $cchildposts, $duplicateposts);

        $article = $article + bp_page_p_post_rules($article, null, null, $childposts + $duplicateposts); // array union

        if ($article['selchildid'] && (@$comments[$article['selchildid']]['type'] != 'A'))
            $article['selchildid'] = null; // if selected comment is hidden or somehow not there, consider it not selected

        foreach ($comments as $key => $comment) {
            $comments[$key] = $comment + bp_page_p_post_rules($comment, $article, $comments, $achildposts);
            $comments[$key]['isselected'] = ($comment['postid'] == $article['selchildid']);
        }

        foreach ($replysfollows as $key => $replyfollow) {
            $parent = ($replyfollow['parentid'] == $articleid) ? $article : @$comments[$replyfollow['parentid']];
            $replysfollows[$key] = $replyfollow + bp_page_p_post_rules($replyfollow, $parent, $replysfollows, null);
        }
    }
    if (!isset($article))
        return qa_article_not_found();

    if (!$article['viewable']) {
        $qa_content = qa_content_prepare();

        if ($article['queued'])
            $qa_content['error'] = qa_lang_html('bp_lang/p_waiting_approval');
        elseif ($article['flagcount'] && !isset($article['lastuserid']))
            $qa_content['error'] = qa_lang_html('bp_lang/p_hidden_flagged');
        elseif ($article['authorlast'])
            $qa_content['error'] = qa_lang_html('bp_lang/p_hidden_author');
        else
            $qa_content['error'] = qa_lang_html('bp_lang/p_hidden_other');

        $qa_content['suggest_next'] = qa_html_suggest_qs_tags(qa_using_tags());

        return $qa_content;
    }
    $permiterror = qa_user_post_permit_error('bp_permit_view_p_page', $article, null, false);

    if ($permiterror && (qa_is_human_probably() || !qa_opt('allow_view_q_bots'))) {
        $qa_content = qa_content_prepare();
        $topage = bp_p_request($article['permalink']);

        switch ($permiterror) {
            case 'login':
                $qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/view_p_must_login'), $topage);
                break;

            case 'confirm':
                $qa_content['error'] = qa_insert_login_links(qa_lang_html('bp_lang/view_p_must_confirm'), $topage);
                break;

            case 'approve':
                $qa_content['error'] = strtr(
                    qa_lang_html('bp_lang/view_p_must_be_approved'),
                    array(
                        '^1' => '<a href="' . qa_path_html('cccount') . '">',
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

    $captchareason = qa_user_captcha_reason(qa_user_level_for_post($article));
    $usecaptcha = ($captchareason != false);

    $pagestart = qa_get_start();
    $showid = qa_get('show');
    $pageerror = null;
    $formtype = null;
    $formpostid = null;
    $jumptoanchor = null;
    $replysall = null;

    if (substr($pagestate, 0, 13) == 'showreplys-') {
        $replysall = substr($pagestate, 13);
        $pagestate = null;

    } elseif (isset($showid)) {
        foreach ($replysfollows as $reply) {
            if ($reply['postid'] == $showid) {
                $replysall = $reply['parentid'];
                break;
            }
        }
    }

    //if (qa_is_http_post() || strlen($pagestate)) require QA_PLUGIN_DIR . 'q2a-blog-post/blog-view.php';

    $formrequested = isset($formtype);

    if (!$formrequested && $article['answerbutton']) {
        $immedoption = qa_opt('show_a_form_immediate');

        if ($immedoption == 'always' || ($immedoption == 'if_no_as' && !$article['isbyuser']))
            $formtype = 'a_add'; // show comment form by default
    }


    // Get information on the users referenced
    $usershtml = qa_userids_handles_html(array_merge(array($article), $comments, $replysfollows), true);

    // Prepare content for theme
    $qa_content = qa_content_prepare(true, array_keys(bp_cat_path($categories, $article['catid'])));

    if (isset($userid) && !$formrequested)
        $qa_content['favorite'] = qa_favorite_form(
            QA_ENTITY_QUESTION,
            $articleid,
            $favorite,
            qa_lang($favorite ? 'article/remove_q_favorites' : 'article/add_q_favorites')
        );

    if (isset($pageerror))
        $qa_content['error'] = $pageerror; // might also show voting error set in qa-index.php
    elseif ($article['queued'])
        $qa_content['error'] = $article['isbyuser'] ? qa_lang_html('bp_lang/p_your_waiting_approval') : qa_lang_html('bp_lang/p_waiting_your_approval');

    if ($article['hidden'])
        $qa_content['hidden'] = true;

    qa_sort_by($replysfollows, 'created');

    // Prepare content for the article...
    if ($formtype == 'q_edit') { // ...in edit mode
        $qa_content['title'] = qa_lang_html($article['editable'] ? 'article/edit_q_title' :
            (qa_using_categories() ? 'article/recat_q_title' : 'article/retag_q_title'));
        $qa_content['form_q_edit'] = qa_page_q_edit_q_form($qa_content, $article, @$qin, @$qerrors, $completetags, $categories);
        $qa_content['q_view']['raw'] = $article;

    } else { // ...in view mode
        $qa_content['q_view'] = bp_page_p_article_view($article, $parentarticle, $closepost, $usershtml, $formrequested);

        if (array_key_exists('title', $qa_content['q_view']))
            $qa_content['title'] = $qa_content['q_view']['title'];
        else
            $qa_content['title'] = qa_lang_html('bp_lang/blog_post_title');

        $qa_content['description'] = qa_html(qa_shorten_string_line(qa_viewer_text($article['content'], $article['format']), 150));

        $categorykeyword = @$categories[$article['catid']]['title'];

        $qa_content['keywords'] = qa_html(
            implode(
                ',',
                array_merge(
                    (qa_using_categories() && strlen($categorykeyword)) ? array($categorykeyword) : array(),
                    qa_tagstring_to_tags($article['tags'])
                )
            )
        ); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
    }

    $microdata = qa_opt('use_microdata');
    if ($microdata) {
        $qa_content['head_lines'][] = '<meta itemprop="name" content="' . qa_html($qa_content['q_view']['raw']['title']) . '">';
        $qa_content['html_tags'] .= ' itemscope itemtype="http://schema.org/QAPage"';
        $qa_content['main_tags'] = ' itemscope itemtype="http://schema.org/Question"';
    }

    if ($formtype == 'a_edit') {
        $qa_content['a_form'] = bp_page_p_edit_c_form(
            $qa_content, 'a' . $formpostid, $comments[$formpostid],
            $article,
            $comments,
            $replysfollows,
            @$aeditin[$formpostid],
            @$aediterrors[$formpostid]
        );

        $qa_content['a_form']['c_list'] = bp_page_p_reply_follow_list(
            $article, $comments[$formpostid],
            $replysfollows,
            true,
            $usershtml,
            $formrequested,
            $formpostid
        );

        $jumptoanchor = 'a' . $formpostid;

    }

    if ($formtype == 'q_close') {
        $qa_content['q_view']['c_form'] = qa_page_q_close_q_form($qa_content, $article, 'close', @$closein, @$closeerrors);
        $jumptoanchor = 'close';

    } elseif (($formtype == 'c_add' && $formpostid == $articleid) || ($article['commentbutton'] && !$formrequested)) { // ...to be added
        $qa_content['q_view']['c_form'] = qa_page_q_add_c_form(
            $qa_content,
            $article,
            $article, 'c' . $articleid,
            $captchareason,
            @$cnewin[$articleid],
            @$cnewerrors[$articleid], $formtype == 'c_add'
        );

        if ($formtype == 'c_add' && $formpostid == $articleid) {
            $jumptoanchor = 'c' . $articleid;
            $replysall = $articleid;
        }

    } elseif ($formtype == 'c_edit' && @$replysfollows[$formpostid]['parentid'] == $articleid) { // ...being edited
        $qa_content['q_view']['c_form'] = qa_page_q_edit_c_form(
            $qa_content, 'c' . $formpostid, $replysfollows[$formpostid],
            @$ceditin[$formpostid],
            @$cediterrors[$formpostid]
        );

        $jumptoanchor = 'c' . $formpostid;
        $replysall = $articleid;
    }

    $qa_content['q_view']['c_list'] = bp_page_p_reply_follow_list(
        $article,
        $article,
        $replysfollows,
        $replysall == $articleid,
        $usershtml,
        $formrequested,
        $formpostid
    ); // ...for viewing


    // Prepare content for existing comments (could be added to by Ajax)

    $qa_content['a_list'] = array(
        'tags' => 'id="a_list"',
        'as' => array(),
    );

    // sort according to the site preferences

    if (qa_opt('sort_comments_by') == 'votes') {
        foreach ($comments as $commentid => $comment)
            $comments[$commentid]['sortvotes'] = $comment['downvotes'] - $comment['upvotes'];

        qa_sort_by($comments, 'sortvotes', 'created');

    } else {
        qa_sort_by($comments, 'created');
    }

    // further changes to ordering to deal with queued, hidden and selected comments

    $countfortitle = $article['ccount'];
    $nextposition = 10000;
    $commentposition = array();

    foreach ($comments as $commentid => $comment) {
        if ($comment['viewable']) {
            $position = $nextposition++;

            if ($comment['hidden'])
                $position += 10000;
            elseif ($comment['queued']) {
                $position -= 10000;
                $countfortitle++; // include these in displayed count

            } elseif ($comment['isselected'] && qa_opt('show_selected_first'))
                $position -= 5000;

            $commentposition[$commentid] = $position;
        }
    }

    asort($commentposition, SORT_NUMERIC);

    // extract IDs and prepare for pagination

    $commentids = array_keys($commentposition);
    $countforpages = count($commentids);
    $pagesize = qa_opt('page_size_q_as');

    // see if we need to display a particular comment

    if (isset($showid)) {
        if (isset($replysfollows[$showid]))
            $showid = $replysfollows[$showid]['parentid'];

        $position = array_search($showid, $commentids);

        if (is_numeric($position))
            $pagestart = floor($position / $pagesize) * $pagesize;
    }

    // set the canonical url based on possible pagination

    $qa_content['canonical'] = qa_path_html(
        qa_q_request($article['postid'], $article['title']),
        ($pagestart > 0) ? array('start' => $pagestart) : null,
        qa_opt('site_url')
    );

    // build the actual comment list

    $commentids = array_slice($commentids, $pagestart, $pagesize);

    foreach ($commentids as $commentid) {
        $comment = $comments[$commentid];

        if (!($formtype == 'a_edit' && $formpostid == $commentid)) {
            $a_view = qa_page_q_comment_view($article, $comment, $comment['isselected'], $usershtml, $formrequested);

            // Prepare content for replys on this comment, plus add or edit reply forms

            if (($formtype == 'c_add' && $formpostid == $commentid) || ($comment['commentbutton'] && !$formrequested)) { // ...to be added
                $a_view['c_form'] = qa_page_q_add_c_form(
                    $qa_content,
                    $article,
                    $comment, 'c' . $commentid,
                    $captchareason,
                    @$cnewin[$commentid],
                    @$cnewerrors[$commentid], $formtype == 'c_add'
                );

                if ($formtype == 'c_add' && $formpostid == $commentid) {
                    $jumptoanchor = 'c' . $commentid;
                    $replysall = $commentid;
                }

            } elseif ($formtype == 'c_edit' && @$replysfollows[$formpostid]['parentid'] == $commentid) { // ...being edited
                $a_view['c_form'] = qa_page_q_edit_c_form(
                    $qa_content, 'c' . $formpostid, $replysfollows[$formpostid],
                    @$ceditin[$formpostid],
                    @$cediterrors[$formpostid]
                );

                $jumptoanchor = 'c' . $formpostid;
                $replysall = $commentid;
            }

            $a_view['c_list'] = qa_page_q_reply_follow_list(
                $article,
                $comment,
                $replysfollows,
                $replysall == $commentid,
                $usershtml,
                $formrequested,
                $formpostid
            ); // ...for viewing

            // Add the comment to the list

            $qa_content['a_list']['as'][] = $a_view;
        }
    }

    if ($article['basetype'] == 'P') {
        $qa_content['a_list']['title_tags'] = 'id="a_list_title"';

        if ($countfortitle > 0) {
            $split = $countfortitle == 1
                ? qa_lang_html_sub_split('article/1_comment_title', '1', '1')
                : qa_lang_html_sub_split('article/x_comments_title', $countfortitle);

            if ($microdata) {
                $split['data'] = '<span itemprop="commentCount">' . $split['data'] . '</span>';
            }
            $qa_content['a_list']['title'] = $split['prefix'] . $split['data'] . $split['suffix'];
        } else
            $qa_content['a_list']['title_tags'] .= ' style="display:none;" ';
    }

    if (!$formrequested) {
        $qa_content['page_links'] = qa_html_page_links(qa_request(), $pagestart, $pagesize, $countforpages, qa_opt('pages_prev_next'), array(), false, 'a_list_title');
    }


    // Some generally useful stuff

    if (qa_using_categories() && count($categories)) {
        $qa_content['navigation']['cat'] = bp_cat_navigation($categories, $article['catid']);
    }

    if (isset($jumptoanchor)) {
        $qa_content['script_onloads'][] = array(
            'qa_scroll_page_to($("#"+' . qa_js($jumptoanchor) . ').offset().top);'
        );
    }


    // Determine whether this request should be counted for page view statistics.
    // The lastviewip check is now part of the hotness query in order to bypass caching.

    if (
        qa_opt('do_count_q_views') && !$formrequested && !qa_is_http_post() && qa_is_human_probably() &&
        (!$article['views'] || (
                // if it has more than zero views, then it must be different IP & user & cookieid from the creator
            (@inet_ntop($article['createip']) != qa_remote_ip_address() || !isset($article['createip'])) &&
            ($article['userid'] != $userid || !isset($article['userid'])) &&
            ($article['cookieid'] != $cookieid || !isset($article['cookieid']))
        ))
    ) {
        $qa_content['inc_views_postid'] = $articleid;
    }

    return $qa_content;
}