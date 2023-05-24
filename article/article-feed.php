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

function qa_article_not_found()
{
    qa_set_template('not-found');
    $custom_title = (strlen(qa_opt('bp_blog_title')) > 3) ? ' - ' . qa_opt('bp_blog_title') : '';
    $qa_content = qa_content_prepare();
    $qa_content['title'] = qa_lang_html('bp_lang/article_not_found') . $custom_title;
    $qa_content['error'] = qa_lang_html('bp_lang/article_not_found_page');
    $qa_content['suggest_next'] = bp_html_suggest_ps_tags(qa_using_tags());
    return $qa_content;
}

function qa_article_feed()
{
    require_once QA_INCLUDE_DIR . 'app/format.php';
    require_once QA_INCLUDE_DIR . 'app/updates.php';

    $start = qa_get_start();
    $categoryslugs = qa_request_parts(1);
    $countslugs = count($categoryslugs);
    $userid = qa_get_logged_in_userid();
    $sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');

    $pagesize = (strlen(qa_opt('bp_page_size')) > 0) ? qa_opt('bp_page_size') : 10;
    
    list($articles, $blog_cats, $catid) = qa_db_select_with_pending(
        bp_db_ps_selectspec($userid, 'created', $start, $categoryslugs, false, false, qa_opt_if_loaded('page_size_qs')),
        bp_db_cat_nav_selectspec($categoryslugs, false, false, true),
        $countslugs ? bp_db_slugs_to_cat_id_selectspec($categoryslugs) : null
    );

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
    $custom_title = (strlen(qa_opt('bp_blog_title')) > 3) ? ' - ' . qa_opt('bp_blog_title') : '';
    $categorytitlehtml = qa_html($blog_cats[$catid]['title']);

    if (count($articles)) {
        $sometitle = $countslugs ? qa_lang_html_sub('bp_lang/recent_ps_in_x', $categorytitlehtml) : qa_lang_html('bp_lang/recent_ps_title');
        $qa_content['title'] = $sometitle . $custom_title;

        $defaults = qa_post_html_defaults('P');
        if (isset($categorypathprefix)) {
            //$defaults['categorypathprefix'] = $categorypathprefix;
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
        $nonetitle = qa_lang_html_sub('bp_lang/no_articles_in_x', $categorytitlehtml);
        $qa_content['title'] = $nonetitle . $custom_title;
    }

    if (isset($userid) && isset($catid)) {
        $favoritemap = qa_get_favorite_non_qs_map();
        $categoryisfavorite = @$favoritemap['blogcat'][$navcategories[$catid]['backpath']];

        $qa_content['favorite'] = qa_favorite_form(
            QA_ENTITY_CATEGORY,
            $catid,
            $categoryisfavorite,
            qa_lang_sub($categoryisfavorite ? 'main/remove_x_favorites' : 'main/add_category_x_favorites', $navcategories[$catid]['title'])
        );
    }

    if (isset($count) && isset($pagesize)) {
        //$qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'), $pagelinkparams);
    }

    $qa_content['canonical'] = qa_get_canonical();

    if (empty($qa_content['page_links'])) {
        $qa_content['suggest_next'] = $suggest;
    }

    if (qa_using_categories() && count($navcategories) && isset($categorypathprefix)) {
        //$qa_content['navigation']['cat'] = bp_cat_navigation($navcategories, $catid, $categorypathprefix, $categorypcount, $categoryparams);
    }

    // set meta description on category pages
    if (!empty($navcategories[$catid]['content'])) {
        $qa_content['description'] = qa_html($navcategories[$catid]['content']);
    }

    if (isset($feedpathprefix) && (qa_opt('feed_per_category') || !isset($catid))) {
        /*$qa_content['feed'] = array(
            'url' => qa_path_html(qa_feed_request($feedpathprefix . (isset($catid) ? ('/' . bp_cat_path_request($navcategories, $catid)) : ''))),
            'label' => strip_tags($sometitle),
        );*/
    }

    return $qa_content;
}