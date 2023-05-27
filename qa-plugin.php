<?php
/*
	Plugin Name: Blog Post
	Plugin URI: https://github.com/JaxiroKe/q2a-blog-post
	Plugin Description: Allows registered users to maintain a blog on your Q2A site
	Plugin Version: 5.2
	Plugin Date: 2015-02-04
	Plugin Author: Jack Siro
	Plugin Author URI: https://github.com/JaxiroKehttps://www.linkedin.com/in/jaxiroke/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: https://github.com/JaxiroKe/q2a-blog-post/master/VERSION.txt

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	
}
	
	define( "QA_LIMIT_BLOG_POSTS", "BP");
	define( "QA_LIMIT_BLOG_COMMENTS", "BC");
	define( "QA_DB_RETRIEVE_PS_AS", 50 );
	define( "QA_DB_RETRIEVE_WRITE_TAG_PS", 500 );
	define( "QA_DB_MAX_EVENTS_PER_P", 5 );
	
	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/q2a-blog-post';

	qa_register_layer('admin/blog-settings.php', 'Admin Blog Settings', $plugin_dir, $plugin_url );
	qa_register_layer('admin/blog-categories.php', 'Admin Blog Categories', $plugin_dir, $plugin_url );
	
	qa_register_plugin_phrases('langs/blog-lang-*.php', 'bp_lang');
	qa_register_plugin_module('page', 'user/blog-post.php', 'blog_post', 'Blog Post');
	qa_register_plugin_layer('user/blog-layer.php', 'Blog Post: User Page');
		
/*
	Omit PHP closing tag to help avoid accidental output
*/
