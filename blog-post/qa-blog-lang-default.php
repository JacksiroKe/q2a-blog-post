<?php
/*
	Plugin Name: Blog Post
	Plugin URI: http://websmata.tujuane.net/qtoa/blog
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 3.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Siro
	Plugin Author URI: http://question2answer.org/qa/user/jaxila
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: 

*/

	return array(
		// Admin page
		'enable_plugin' => 'Enable the Blog Post Plugin',
		'default_cat_1' => 'Category 1',
		'default_cat_2' => 'Category 2',
		'default_cat_3' => 'Category 3',
		'default_cat_4' => 'Category 4',
		'default_cat_5' => 'Category 5',
		'default_blog_title' => 'My QtoA Blog',
		'default_blog_tagline' => '<strong>These posts are made by members on this website. You can make some yourself!</strong>',
		'cat_1' => '<font style="color:#ff0000;font-weight:bold;">Category 1</font> Label',
		'cat_2' => '<font style="color:#ff0000;font-weight:bold;">Category 2</font> Label',
		'cat_3' => '<font style="color:#ff0000;font-weight:bold;">Category 3</font> Label',
		'cat_4' => '<font style="color:#ff0000;font-weight:bold;">Category 4</font> Label',
		'cat_5' => '<font style="color:#ff0000;font-weight:bold;">Category 5</font> Label',
		'blog_title' => 'Your <font style="color:#ff0000;font-weight:bold;">Blog\'s Title</font>',
		'blog_tagline' => '<strong>Hey, How about a <font style="color:#ff0000;font-weight:bold;">Blog Tagline</font>?</strong> 
		<br><div style="background:#000;color:#fff;padding:5px;"> This will show just below your blog title
		<br>Please include some HTML here ...<br>so that it looks appealing</div>',
		'content_max' => 'The maximum length of <font style="color:#ff0000;font-weight:bold;">content preview</font> is',
		'suffix' => '<font style="color:#ff0000;font-weight:bold;">characters</font> on your blog\'s homepage',
		'default_blog_rules' => 'Your articles are viewable by you and other site visitors to your profile and in the blog. You can post new content using the form below',
		'blog_rules' => '<strong><font style="color:#ff0000;font-weight:bold;">Instructions</font> on the Posting page</strong> 
		<br><div style="background:#000;color:#fff;padding:5px;"> This will show on the posting page
		<br>Please include some HTML here ...<br>so that it looks appealing</div>',
		'blog_language' => 'Choose your language',
		'blog_save' => 'Blog Settings have been saved!',
		'enable_widget' => 'Show Blog Latest Widget Title',
		'suffix2' => '',
		'tagline_scrol'	=> 'Display a scrolling tagline',
		'suffix3' => 'on your blog\'s homepage',
		'blog_editor' => '<font style="color:#ff0000;font-weight:bold;">Default editor</font> for Posting Articles',
		'blogger_info' => '<font style="color:#ff0000;font-weight:bold;">Default information about a blogger</font>
							<br><div style="background:#000;color:#fff;padding:5px;">If a blogger has not updated the
							about me<br> section of his/her profile then this text will<br> 
							show on the article page instead of blank <br>space . . .</div>',
		'blog_spam_post' => 'Who can <font style="color:#ff0000;font-weight:bold;">post</font> articles',
		'blog_spam_view' => 'Who can <font style="color:#ff0000;font-weight:bold;">view</font> articles',
		'blog_spam_edit' => 'Who can <font style="color:#ff0000;font-weight:bold;">edit</font> or 
							<font style="color:#ff0000;font-weight:bold;">delete</font> articles',
		
		'nof_articles' => '<font style="color:#ff0000;font-weight:bold;">Number of Articles</font> per page',
		'author_info_show' => '<font style="color:#ff0000;font-weight:bold;">Show Author\'s information</font> on article page',
		'home_avatar' => '<font style="color:#ff0000;font-weight:bold;">Show Author\'s Avatar</font> in blog home page',
		'home_avatar_size' => '<font style="color:#ff0000;font-weight:bold;">Size of Author\'s Avatar</font> in Blog home page',
		'home_avatar_suffix' => ' px',
		'article_avatar' => '<font style="color:#ff0000;font-weight:bold;">Size of Author\'s Avatar</font> in article page',		
		'article_avatar_suffix' => ' px',
		'comment_avatar' => '<font style="color:#ff0000;font-weight:bold;">Size of Commenter\'s Avatar</font>',		
		'comment_avatar_suffix' => ' px',
		'nof_comments' => '<font style="color:#ff0000;font-weight:bold;">Number of Comments</font> per page',
		'written_by' => ' Written by ',
		'in' => ' in ',
		
		// Blog Page
		'nav_all' => 'All posts',
		'nav_post' => 'New Article!',
		'userid_null' => 'Anonymous',
		'posted' => 'Posted ',
		'Posted_in' => 'Posted in ',
		'posted_in' => ' posted in ',
		'written_on' => 'Written on ',
		'by' => ' by ',
		'articles_no' => ' Articles, ',
		'comments_no' => ' Comments ',
		'post_view' => ' hit ',
		'post_views' => ' hits ',
		'post_comment' => ' comment  ',
		'post_comments' => ' comments  ',
		'post_null' => 'No such article!',
		'posts_null' => 'Sorry there are currently no blog posts here yet. Get things started by being the first to post here!',
		
		 // Articles page
		'articles_page' => 'New Article',
		'articles_title' => 'Post a New Article',
		'post_title' => 'Title of your Article:',
		'post_cat' => 'Category:',
		'my_articles' => 'My Articles',
		'post_button' => 'Publish Article',
		'draft_button' => 'Save Draft',
		'cancel_button' => 'Cancel',
		'post_null' => 'No posts yet!',
		'past_post' => 'Some of your past blog posts . . .',
		'error_title' => 'Title has to be at least 10 characters.',
		'error_content' => 'Article has to be at least 50 characters.',
		'access_error' => 'Please ^1log in^2 or ^3register^4 to publish an article on our blog!',
		
	
		// User Layer
		'nav_user' => 'User ',
		'nav_wall' => 'Wall',
		'articles' => ' Articles, ',
		'comments' => ' Comments',
		'blog_post' => 'Blog Post: ',
		'nav_activity' => 'All Activity',
		'nav_questions' => 'All Questions',
		'nav_answers' => 'All Answers',
		'nav_articles' => 'All Articles',
		'new_articles' => 'New Article',		
		'title_recent' => 'Recent articles by ',
		'oops' => 'Oops! It looks like', 
		'no_post' => 'hasn\'t posted any articles yet',

		// Comments Section
		'leave_comment' => 'You can leave a comment:',
		'comment_error' => 'Please ^1log in^2 or ^3register^4 to comment on this post!',
		'comment' => 'Commented ',
		'by' => ' by ',
		
		// Edit Page
		'title_error' => 'Oops! This is not your article.',
		'edit_error' => 'Sorry, Your are not the author of this article! Go back to ',
		'edit_error1' => 'the blog homepage',
		'edit_note' => 'Sorry! You are trying to edit somebody else\'s article. You can only edit your very own article not unless you have been assigned the duty of a moderator by
		the administrator of this site',
		'update_button' => 'Update',
		'hide_button' => 'Hide',
		'delete_button' => 'Delete',
		'cancel_button' => 'Cancel',
		
		// Latest Blog Posts Widget
		'widget_title' => 'Latest Blog Posts',
		'more_posts' => 'Read More Blog Posts',
		'read_more' => 'Read More',
		
	);


/*
	Omit PHP closing tag to help avoid accidental output
*/