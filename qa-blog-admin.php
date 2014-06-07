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
	class qa_blog_admin 
{


		function option_default($option) {
			switch($option) {
				case 'qa_blog_enabled':
					return 1;
				case 'qa_blog_title':
					return qa_lang('qa_blog_lang/default_blog_title');
				case 'qa_blog_tagline':
					return qa_lang('qa_blog_lang/default_blog_tagline');
				case 'qa_site_url':
					return qa_lang('qa_blog_lang/default_site_url');
				case 'qa_blog_cat_1':
					return qa_lang('qa_blog_lang/default_cat_1');
				case 'qa_blog_cat_2':
					return qa_lang('qa_blog_lang/default_cat_2');
				case 'qa_blog_cat_3':
					return qa_lang('qa_blog_lang/default_cat_3');
				case 'qa_blog_cat_4':
					return qa_lang('qa_blog_lang/default_cat_4');
				case 'qa_blog_cat_5':
					return qa_lang('qa_blog_lang/default_cat_5');
				case 'qa_blog_rules':
					return qa_lang('qa_blog_lang/default_blog_rules');
				case 'qa_blog_content_max':
					return 500;
			}
		}
			
		function allow_template($template) {
			return ($template!='admin');
		}       
			
		function admin_form(&$qa_content){                       

			// process the admin form if admin hit Save-Changes-button
			$ok = null;
			if (qa_clicked('qa_blog_save')) {
				qa_opt('qa_blog_enabled', (bool)qa_post_text('qa_blog_enabled'));
				qa_opt('qa_blog_title', qa_post_text('qa_blog_title'));
				qa_opt('qa_blog_tagline', qa_post_text('qa_blog_tagline'));	
				qa_opt('qa_site_url', qa_post_text('qa_site_url'));
				qa_opt('qa_blog_cat_1', qa_post_text('qa_blog_cat_1'));
				qa_opt('qa_blog_cat_2', qa_post_text('qa_blog_cat_2'));
				qa_opt('qa_blog_cat_3', qa_post_text('qa_blog_cat_3'));
				qa_opt('qa_blog_cat_4', qa_post_text('qa_blog_cat_4'));
				qa_opt('qa_blog_cat_5', qa_post_text('qa_blog_cat_5'));
				qa_opt('qa_blog_rules', qa_post_text('qa_blog_rules'));				
				qa_opt('qa_blog_content_max', (int)qa_post_text('qa_blog_content_max_field'));
				$ok = qa_lang('qa_blog_lang/blog_save');
			}
			
			qa_set_display_rules($qa_content, array(
				'field_2' => 'field_1',
				'field_3' => 'field_1',
				'field_4' => 'field_1',
				'field_5' => 'field_1',
				'field_6' => 'field_1',
				'field_7' => 'field_1',
				'field_8' => 'field_1',
				'field_9' => 'field_1',
				'field_10' => 'field_1',
				'field_11' => 'field_1',
			));
			
			// form fields to display frontend for admin
			$fields = array();
			
			$fields[] = array(
				'label' => qa_lang('qa_blog_lang/enable_plugin'),
				'type' => 'checkbox',
				'value' => qa_opt('qa_blog_enabled'),
				'tags' => 'name="qa_blog_enabled" id="field_1"',
				);

			$fields[] = array(
				'id' => 'field_2',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/blog_title'),
				'value' => qa_opt('qa_blog_title'),
				'tags' => 'name="qa_blog_title"',				
			);
			
			$fields[] = array(
				'id' => 'field_3',				
				'type' => 'textarea',
				'label' => qa_lang('qa_blog_lang/blog_tagline'),
				'value' => qa_opt('qa_blog_tagline'),
				'rows' => 4,
				'tags' => 'name="qa_blog_tagline"',				
			);
			
			$fields[] = array(
				'id' => 'field_4',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/site_url'),
				'value' => qa_opt('qa_site_url'),
				'tags' => 'name="qa_site_url"',
			);
						
			$fields[] = array(
				'id' => 'field_5',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_1'),
				'value' => qa_opt('qa_blog_cat_1'),
				'tags' => 'name="qa_blog_cat_1"',
			);

			$fields[] = array(
				'id' => 'field_6',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_2'),
				'value' => qa_opt('qa_blog_cat_2'),
				'tags' => 'name="qa_blog_cat_2"',
			);
			
			$fields[] = array(
				'id' => 'field_7',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_3'),
				'value' => qa_opt('qa_blog_cat_3'),
				'tags' => 'name="qa_blog_cat_3"',
			);
			
			$fields[] = array(
				'id' => 'field_8',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_4'),
				'value' => qa_opt('qa_blog_cat_4'),
				'tags' => 'name="qa_blog_cat_4"',
			);
			
			$fields[] = array(
				'id' => 'field_9',
				'type' => 'input',
				'label' => qa_lang('qa_blog_lang/cat_5'),
				'value' => qa_opt('qa_blog_cat_5'),
				'tags' => 'name="qa_blog_cat_5"',
			);
			
			$fields[] = array(
				'id' => 'field_10',
				'type' => 'textarea',
				'label' => qa_lang('qa_blog_lang/blog_rules'),
				'value' => qa_opt('qa_blog_rules'),
				'tags' => 'name="qa_blog_rules"',
				'rows' => 4,
			);
			
			$fields[] = array(
				'id' => 'field_11',
				'label' => qa_lang('qa_blog_lang/content_max'),
				'suffix' => qa_lang('qa_blog_lang/suffix'),
				'type' => 'number',
				'value' => (int)qa_opt('qa_blog_content_max'),
				'tags' => 'name="qa_blog_content_max_field"',
				'error' => 'If this plugin works well on your site, please
				<a href="http://tujuane.net/websmata/qtoa/plugins/blog-post" target="_blank">comment on my website</a> with the link to your site.',
			);
			
			return array(           
				'ok' => ($ok && !isset($error)) ? $ok : null,
				'fields' => $fields,
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="qa_blog_save"',
					),
				),
			);
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/
