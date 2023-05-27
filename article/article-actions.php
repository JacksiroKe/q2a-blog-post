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

function qa_blog_hide($userid, $articleid)
{
    $findpost = qa_bp_post_find_by_postid($articleid);
    if (!count($findpost))
        return qa_article_not_found();

    $article = qa_db_select_with_pending(
        bp_db_full_post_selectspec($userid, $articleid),
    );
    bp_post_hide($articleid, $userid);
    qa_redirect(bp_p_request($article['permalink']));
}

function qa_blog_delete($userid, $articleid)
{
    $findpost = qa_bp_post_find_by_postid($articleid);
    if (!count($findpost))
        return qa_article_not_found();

    bp_post_delete($articleid);
    qa_redirect('blog');
}