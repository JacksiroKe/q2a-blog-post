<?php
/*
	Plugin Name: Blog Post
	Plugin URI: https://github.com/JackSiro/Q2A-Blog-Post-Plugin
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 3.0
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Siro
	Plugin Author URI: https://github.com/JackSiro
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.7
	Plugin Update Check URI: https://github.com/JackSiro/Q2A-Blog-Post-Plugin/master/blog-post/qa-plugin.php

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	

}

	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/blog-post';

	qa_register_layer('qa-blog-admin.php', 'Blog Settings', $plugin_dir , $plugin_url );
	
	qa_register_plugin_phrases('qa-blog-lang-*.php', 'qa_blog_lang');
	qa_register_plugin_module('page', 'qa-blog.php', 'qa_blog', 'Blog Post');
	qa_register_plugin_module('page', 'qa-articles.php', 'qa_articles', 'Blog Post: Articles');
	
	qa_register_plugin_module('page', 'article/qa-edit.php', 'qa_edit', 'Blog Post: Edit');
	qa_register_plugin_module('page', 'article/qa-delete.php', 'qa_delete', 'Blog Post: Delete');
	qa_register_plugin_module('page', 'article/qa-hide.php', 'qa_hide', 'Blog Post: Hide');
	qa_register_plugin_module('page', 'article/qa-reshow.php', 'qa_reshow', 'Blog Post: Reshow');
	qa_register_plugin_module('page', 'article/qa-close.php', 'qa_close', 'Blog Post: Close');
	qa_register_plugin_module('page', 'article/qa-open.php', 'qa_open', 'Blog Post: Open');
	qa_register_plugin_module('page', 'article/qa-flag.php', 'qa_flag', 'Blog Post: Flag');
	
	qa_register_plugin_layer('qa-users-layer.php', 'Blog Post: User Page');
	
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
