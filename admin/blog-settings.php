<?php
/*
 Blog Post by Jack Siro
 https://github.com/JaxiroKe/q2a-blog-post
 Description: Blog Post Plugin Admin pages manager
 
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/admin.php';
require_once QA_INCLUDE_DIR . 'db/maxima.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/options.php';
require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-base.php';

class qa_html_theme_layer extends qa_html_theme_base
{
	var $plugin_directory;
	var $plugin_url;

	function doctype()
	{
		global $qa_request;
		$adminsection = strtolower(qa_request_part(1));
		$securityexpired = false;

		if ($adminsection == 'bp_settings') {
			$this->content = qa_content_prepare();
			$this->template = 'admin';
			$this->content['suggest_next'] = "";
			$this->content['error'] = $securityexpired ? qa_lang_html('admin/form_security_expired') : qa_admin_page_error();
			$this->content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('bp_lang/' . $adminsection);
			$this->content = $this->bp_blog_settings();
		}
		qa_html_theme_base::doctype();
	}

	function nav_list($navigation, $class, $level = null)
	{
		if ($this->template == 'admin') {
			if ($class == 'nav-sub') {
				$navigation['bp_settings'] = array(
					'label' => qa_lang_html('bp_lang/nav_settings'),
					'url' => qa_path_html('admin/bp_settings'),
					'selected' => (strtolower(qa_request_part(1)) == 'bp_settings') ? 'selected' : '',
				);
			}
		}
		qa_html_theme_base::nav_list($navigation, $class, $level = null);
	}

	function bp_blog_settings()
	{
		$formokhtml = null;
		$form_fields = array();
		$getoptions = array();
		$permit_options = array();
		$permissions = bp_permit_options();
		$showoptions = array('bp_blog_title', 'bp_blog_tagline', 'bp_blog_editor', 'bp_blog_rules', 'bp_content_max', 'bp_avatar_size_home', 'bp_avatar_size_comment', 'bp_nof_home_articles', 'bp_nof_comments');
		$optiontype = array('bp_content_max' => 'number', 'bp_avatar_size_home' => 'number', 'bp_avatar_size_comment' => 'number', 'bp_nof_home_articles' => 'number', 'bp_nof_comments' => 'number');
		$getoptions = array_merge($showoptions, $permissions);
		$options = bp_get_options($getoptions);

		if (qa_clicked('doresetoptions')) {
			if (!qa_check_form_security_code('admin/bp_settings', qa_post_text('code')))
				$securityexpired = true;
			else {
				qa_reset_options($getoptions);
				$formokhtml = qa_lang_html('admin/options_reset');
			}
		} elseif (qa_clicked('dosaveoptions')) {
			if (!qa_check_form_security_code('admin/bp_settings', qa_post_text('code')))
				$securityexpired = true;
			else {
				foreach ($getoptions as $optionname) {
					$optionvalue = qa_post_text('option_' . $optionname);

					if (
						@$optiontype[$optionname] == 'number' || @$optiontype[$optionname] == 'checkbox' ||
						(@$optiontype[$optionname] == 'number-blank' && strlen($optionvalue))
					)
						$optionvalue = (int) $optionvalue;
					qa_set_option($optionname, $optionvalue);
				}
				$formokhtml = qa_lang_html('bp_lang/blog_save');
			}
		}

		$this->content['custom'] = '<p><center><b>Support development of this Plugin</b> with anything from </br><b>$ 30</b> on <b>Paypal</b>: <b><a href="https://paypal.com/jacksiro">https://paypal.com/jacksiro</a></b></center></p>';

		$this->content['form'] = array(
			'ok' => $formokhtml,
			'tags' => 'method="post" action="' . qa_path_html(qa_request()) . '"',
			'style' => 'wide',
			'fields' => array(),

			'buttons' => array(
				'save' => array(
					'tags' => 'id="dosaveoptions"',
					'label' => qa_lang_html('admin/save_options_button'),
				),

				'reset' => array(
					'tags' => 'name="doresetoptions" onclick="return confirm(' . qa_js(qa_lang_html('admin/reset_options_confirm')) . ');"',
					'label' => qa_lang_html('admin/reset_options_button'),
				),
			),

			'hidden' => array(
				'dosaveoptions' => '1',
				'has_js' => '0',
				'code' => qa_get_form_security_code('admin/bp_settings'),
			),
		);

		foreach ($showoptions as $optionname) {
			$value = $options[$optionname];
			$type = @$optiontype[$optionname];
			if ($type == 'number-blank')
				$type = 'number';

			$optionfield = array(
				'id' => $optionname,
				'label' => qa_lang_html('bp_lang/' . $optionname),
				'tags' => 'name="option_' . $optionname . '" id="option_' . $optionname . '"',
				'value' => qa_html($value),
				'type' => $type,
				//'error' => qa_html(@$errors[$optionname]),
			);

			switch ($optionname) {
				case 'bp_blog_title':
					$optionfield['type'] = 'text';
					$optionfield['style'] = 'tall';
					break;
				case 'bp_blog_tagline':
				case 'bp_blog_rules':
					$optionfield['type'] = 'textarea';
					$optionfield['style'] = 'tall';
					$optionfield['rows'] = 2;
					break;
				case 'bp_blog_editor':
					$editors = qa_list_modules('editor');
					$selectoptions = array();
					foreach ($editors as $editor) {
						$selectoptions[qa_html($editor)] = strlen($editor) ? qa_html($editor) : qa_lang_html('admin/basic_editor');
						if ($editor == $value) {
							$module = qa_load_module('editor', $editor);
							if (method_exists($module, 'admin_form')) {
								$optionfield['note'] = '<a href="' . qa_admin_module_options_path('editor', $editor) . '">' . qa_lang_html('admin/options') . '</a>';
							}
						}
					}
					qa_optionfield_make_select($optionfield, $selectoptions, $value, '');
					break;
				default:
					$optionfield['type'] = 'number';
					$optionfield['value'] = (int) $value;
					$optionfield['suffix'] = qa_lang('bp_lang/suffix_' . $optionname);
					break;
			}
			//$getoptions[] = $optionname;
			//$form_fields[$optionname] = $optionfield;
			$this->content['form']['fields'][$optionname] = $optionfield;
		}

		$this->content['form']['fields']['separator1'] = array(
			'type' => 'custom',
			'style' => 'tall',
			'html' => '<h2>' . qa_lang('bp_lang/blog_permissions') . '</h2><hr>',
		);

		foreach ($permissions as $permit_option) {
			$permit_value = qa_opt($permit_option);
			$permit_field['label'] = qa_lang_html('bp_lang/' . $permit_option) . ':';
			$permit_field['tags'] = 'name="option_' . $permit_option . '" id="option_' . $permit_option . '"';
			if (in_array($permit_option, array('bp_permit_view_p_page', 'bp_permit_post', 'bp_permit_post_c')))
				$widest = QA_PERMIT_ALL;
			elseif ($permit_option == 'bp_permit_moderate' || $permit_option == 'bp_permit_hide_show')
				$widest = QA_PERMIT_POINTS;
			elseif ($permit_option == 'bp_permit_delete_hidden')
				$widest = QA_PERMIT_EDITORS;
			else
				$widest = QA_PERMIT_USERS;

			if ($permit_option == 'bp_permit_view_p_page') {
				$narrowest = QA_PERMIT_APPROVED;
				$dopoints = false;
			} elseif ($permit_option == 'bp_permit_moderate' || $permit_option == 'bp_permit_hide_show')
				$narrowest = QA_PERMIT_MODERATORS;
			elseif ($permit_option == 'bp_permit_post_c' || $permit_option == 'bp_permit_flag')
				$narrowest = QA_PERMIT_EDITORS;
			elseif ($permit_option == 'bp_permit_delete_hidden')
				$narrowest = QA_PERMIT_ADMINS;
			else
				$narrowest = QA_PERMIT_EXPERTS;

			$permitoptions = qa_admin_permit_options($widest, $narrowest, (!QA_FINAL_EXTERNAL_USERS) && qa_opt('confirm_user_emails'), $dopoints);

			if (count($permitoptions) > 1) {
				qa_optionfield_make_select(
					$permit_field,
					$permitoptions,
					$permit_value,
					($permit_value == QA_PERMIT_CONFIRMED) ? QA_PERMIT_USERS : min(array_keys($permitoptions))
				);
			} else {
				$permit_field['type'] = 'static';
				$permit_field['value'] = reset($permitoptions);
			}
			//$permit_options[$permit_option] = $permit_field;
			$this->content['form']['fields'][$permit_option] = $permit_field;
		}
		$this->content['navigation']['sub'] = qa_admin_sub_navigation();

		return $this->content;
	}

}

/*
 Omit PHP closing tag to help avoid accidental output */