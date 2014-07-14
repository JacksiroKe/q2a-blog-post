<?php
/*
	Plugin Name: Blog Post
	Plugin URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html
	Plugin Description: The Blog module allows registered users to maintain an online journal, or blog. The blog entries are displayed by creation time in descending order.
	Plugin Version: 2.5
	Plugin Date: 2014-04-01
	Plugin Author: Jackson Silla
	Plugin Author URI: http://question2answer.org/qa/user/jaxila
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: http://tujuane.net/websmata/qtoa/plugins/12-blog-post.html


*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	

}
	// language file
	qa_register_plugin_phrases('qa-blog-lang-*.php', 'qa_blog_lang');

	// latest articles page
	qa_register_plugin_module('page', 'qa-blog.php', 'qa_blog', 'Blog Post');
	
	// new article page
	qa_register_plugin_module('page', 'qa-articles.php', 'qa_articles', 'blog');
	
	// edit article page
	qa_register_plugin_module('page', 'qa-edit.php', 'qa_edit', 'blog edit');
	
	// users layer
	qa_register_plugin_layer('qa-users-layer.php', 'Blog Post layer');
	
	// admin
	qa_register_plugin_module('module', 'qa-blog-admin.php', 'qa_blog_admin', 'Blog Admin');
   
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
