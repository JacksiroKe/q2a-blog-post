<?php
/*
	Plugin Name: Blog Post
	Plugin URI: https://www.github.com/jacksiro/Q2A-Blog-Post-Plugin
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 4.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Siro
	Plugin Author URI: https://www.github.com/jacksiro
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: https://www.github.com/jacksiro/Q2A-Blog-Post-Plugin/master/blog-post/VERSION.txt

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
	$plugin_url = qa_path_to_root().'qa-plugin/blog-post';
	define( "QA_BLOGSPOT_DIR", qa_path_to_root().'qa-plugin/blog-post/' );

	qa_register_layer('blog-admin.php', 'Blog Settings', $plugin_dir, $plugin_url );
	
	qa_register_plugin_phrases('blog-lang-*.php', 'bp_lang');
	qa_register_plugin_module('page', 'blog-post.php', 'blog_post', 'Blog Post');
	qa_register_plugin_layer('blog-layer.php', 'Blog Post: User Page');
		
/*
	Omit PHP closing tag to help avoid accidental output
*/
