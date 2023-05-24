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
		$errors = array();
		$securityexpired = false;

		if ($adminsection == 'bp_categories') {
			$this->content = qa_content_prepare();
			$this->template = 'admin';
			$this->content['suggest_next'] = "";
			$this->content['error'] = $securityexpired ? qa_lang_html('admin/form_security_expired') : qa_admin_page_error();
			$this->content['title'] = qa_lang_html('admin/admin_title') . ' - ' . qa_lang_html('bp_lang/' . $adminsection);
			$this->content = $this->bp_blog_categories();
		}
		qa_html_theme_base::doctype();
	}

	function nav_list($bp_navigation, $class, $level = null)
	{
		if ($this->template == 'admin') {
			if ($class == 'nav-sub') {
				$bp_navigation['bp_categories'] = array(
					'label' => qa_lang_html('bp_lang/nav_categories'),
					'url' => qa_path_html('admin/bp_categories'),
					'selected' => (strtolower(qa_request_part(1)) == 'bp_categories') ? 'selected' : '',
				);
			}
		}
		if (count($bp_navigation) > 1)
			qa_html_theme_base::nav_list($bp_navigation, $class, $level = null);
	}

	function bp_blog_categories()
	{
		require_once QA_PLUGIN_DIR . 'q2a-blog-post/core/blog-db.php';
		$editcatid = qa_post_text('edit');
		if (!isset($editcatid))
			$editcatid = qa_get('edit');
		if (!isset($editcatid))
			$editcatid = qa_get('addsub');

		$categories = qa_db_select_with_pending(bp_db_cat_nav_selectspec($editcatid, true, false, true));

		$editcategory = @$categories[$editcatid];

		if (isset($editcategory)) {
			$parentid = qa_get('addsub');
			if (isset($parentid))
				$editcategory = array('parentid' => $parentid);

		} else {
			if (qa_clicked('doaddcategory'))
				$editcategory = array();
			elseif (qa_clicked('dosavecategory')) {
				$parentid = qa_post_text('parent');
				$editcategory = array('parentid' => strlen($parentid) ? $parentid : null);
			}
		}

		$setmissing = qa_post_text('missing') || qa_get('missing');

		$setparent = !$setmissing && (qa_post_text('setparent') || qa_get('setparent')) && isset($editcategory['catid']);

		$hassubcategory = false;
		foreach ($categories as $category) {
			if (!strcmp($category['parentid'], $editcatid))
				$hassubcategory = true;
		}

		// Process saving options
		$savedoptions = false;
		$securityexpired = false;

		if (qa_clicked('dosaveoptions')) {
			if (!qa_check_form_security_code('admin/bp_categories', qa_post_text('code')))
				$securityexpired = true;
			else {
				qa_set_option('bp_allow_no_blogcat', (int) qa_post_text('option_bp_allow_no_blogcat'));
				qa_set_option('bp_allow_no_sub_blogcat', (int) qa_post_text('option_bp_allow_no_sub_blogcat'));
				$savedoptions = true;
			}
		}

		// Process saving an old or new category
		if (qa_clicked('docancel')) {
			if ($setmissing || $setparent)
				qa_redirect(qa_request(), array('edit' => $editcategory['catid']));
			elseif (isset($editcategory['catid']))
				qa_redirect(qa_request());
			else
				qa_redirect(qa_request(), array('edit' => @$editcategory['parentid']));

		} elseif (qa_clicked('dosetmissing')) {
			if (!qa_check_form_security_code('admin/bp_categories', qa_post_text('code')))
				$securityexpired = true;
			else {
				$inreassign = qa_get_category_field_value('reassign');
				bp_db_cat_reassign($editcategory['catid'], $inreassign);
				qa_redirect(qa_request(), array('recalc' => 1, 'edit' => $editcategory['catid']));
			}

		} elseif (qa_clicked('dosavecategory')) {
			if (!qa_check_form_security_code('admin/bp_categories', qa_post_text('code')))
				$securityexpired = true;
			elseif (qa_post_text('dodelete')) {
				if (!$hassubcategory) {
					$inreassign = qa_get_category_field_value('reassign');
					bp_db_cat_reassign($editcategory['catid'], $inreassign);
					bp_db_cat_delete($editcategory['catid']);
					qa_redirect(qa_request(), array('recalc' => 1, 'edit' => $editcategory['parentid']));
				}

			} else {
				require_once QA_INCLUDE_DIR . 'util/string.php';

				$inname = qa_post_text('name');
				$incontent = qa_post_text('content');
				$inparentid = $setparent ? qa_get_category_field_value('parent') : $editcategory['parentid'];
				$inposition = qa_post_text('position');
				$errors = array();

				// Check the parent ID

				$incategories = qa_db_select_with_pending(bp_db_cat_nav_selectspec($inparentid, true));

				// Verify the name is legitimate for that parent ID

				if (empty($inname))
					$errors['name'] = qa_lang('main/field_required');
				elseif (qa_strlen($inname) > QA_DB_MAX_CAT_PAGE_TITLE_LENGTH)
					$errors['name'] = qa_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TITLE_LENGTH);
				else {
					foreach ($incategories as $category) {
						if (
							!strcmp($category['parentid'], $inparentid) &&
							strcmp($category['catid'], @$editcategory['catid']) &&
							qa_strtolower($category['title']) == qa_strtolower($inname)
						) {
							$errors['name'] = qa_lang('admin/category_already_used');
						}
					}
				}

				// Verify the slug is legitimate for that parent ID
				for ($attempt = 0; $attempt < 100; $attempt++) {
					switch ($attempt) {
						case 0:
							$inslug = qa_post_text('slug');
							if (!isset($inslug))
								$inslug = implode('-', qa_string_to_words($inname));
							break;

						case 1:
							$inslug = qa_lang_sub('admin/category_default_slug', $inslug);
							break;

						default:
							$inslug = qa_lang_sub('admin/category_default_slug', $attempt - 1);
							break;
					}

					$matchcatid = bp_db_cat_slug_to_id($inparentid, $inslug); // query against DB since MySQL ignores accents, etc...

					if (!isset($inparentid))
						$matchpage = qa_db_single_select(qa_db_page_full_selectspec($inslug, false));
					else
						$matchpage = null;

					if (empty($inslug))
						$errors['slug'] = qa_lang('main/field_required');
					elseif (qa_strlen($inslug) > QA_DB_MAX_CAT_PAGE_TAGS_LENGTH)
						$errors['slug'] = qa_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TAGS_LENGTH);
					elseif (preg_match('/[\\+\\/]/', $inslug))
						$errors['slug'] = qa_lang_sub('admin/slug_bad_chars', '+ /');
					elseif (!isset($inparentid) && qa_admin_is_slug_reserved($inslug)) // only top level is a problem
						$errors['slug'] = qa_lang('admin/slug_reserved');
					elseif (isset($matchcatid) && strcmp($matchcatid, @$editcategory['catid']))
						$errors['slug'] = qa_lang('admin/category_already_used');
					elseif (isset($matchpage))
						$errors['slug'] = qa_lang('admin/page_already_used');
					else
						unset($errors['slug']);

					if (isset($editcategory['catid']) || !isset($errors['slug'])) // don't try other options if editing existing category
						break;
				}

				// Perform appropriate database action

				if (empty($errors)) {
					if (isset($editcategory['catid'])) { // changing existing category
						bp_db_cat_rename($editcategory['catid'], $inname, $inslug);

						$recalc = false;

						if ($setparent) {
							bp_db_cat_set_parent($editcategory['catid'], $inparentid);
							$recalc = true;
						} else {
							bp_db_cat_set_content($editcategory['catid'], $incontent);
							bp_db_cat_set_position($editcategory['catid'], $inposition);
							$recalc = $hassubcategory && $inslug !== $editcategory['tags'];
						}

						qa_redirect(qa_request(), array('edit' => $editcategory['catid'], 'saved' => true, 'recalc' => (int) $recalc));

					} else { // creating a new one
						$catid = bp_db_cat_create($inparentid, $inname, $inslug);

						bp_db_cat_set_content($catid, $incontent);

						if (isset($inposition))
							bp_db_cat_set_position($catid, $inposition);

						qa_redirect(qa_request(), array('edit' => $inparentid, 'added' => true));
					}
				}
			}
		}

		if ($setmissing) {
			$this->content['form'] = array(
				'tags' => 'method="post" action="' . qa_path_html(qa_request()) . '"',

				'style' => 'tall',

				'fields' => array(
					'reassign' => array(
						'label' => isset($editcategory)
						? qa_lang_html_sub('admin/category_no_sub_to', qa_html($editcategory['title']))
						: qa_lang_html('admin/category_none_to'),
						'loose' => true,
					),
				),

				'buttons' => array(
					'save' => array(
						'tags' => 'id="dosaveoptions"',
						// just used for qa_recalc_click()
						'label' => qa_lang_html('main/save_button'),
					),

					'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => qa_lang_html('main/cancel_button'),
					),
				),

				'hidden' => array(
					'dosetmissing' => '1',
					// for IE
					'edit' => @$editcategory['catid'],
					'missing' => '1',
					'code' => qa_get_form_security_code('admin/bp_categories'),
				),
			);

			bp_set_up_cat_field(
				$qa_content, $this->content['form']['fields']['reassign'],
				'reassign',
				$categories,
				@$editcategory['catid'],
				qa_opt('bp_allow_no_blogcat'),
				qa_opt('bp_allow_no_sub_blogcat')
			);


		} elseif (isset($editcategory)) {
			$this->content['form'] = array(
				'tags' => 'method="post" action="' . qa_path_html(qa_request()) . '"',

				'style' => 'tall',

				'ok' => qa_get('saved') ? qa_lang_html('admin/category_saved') : (qa_get('added') ? qa_lang_html('admin/category_added') : null),

				'fields' => array(
					'name' => array(
						'id' => 'name_display',
						'tags' => 'name="name" id="name"',
						'label' => qa_lang_html(count($categories) ? 'admin/category_name' : 'admin/category_name_first'),
						'value' => qa_html(isset($inname) ? $inname : @$editcategory['title']),
						'error' => qa_html(@$errors['name']),
					),

					'blogposts' => array(),

					'delete' => array(),

					'reassign' => array(),

					'slug' => array(
						'id' => 'slug_display',
						'tags' => 'name="slug"',
						'label' => qa_lang_html('admin/category_slug'),
						'value' => qa_html(isset($inslug) ? $inslug : @$editcategory['tags']),
						'error' => qa_html(@$errors['slug']),
					),

					'content' => array(
						'id' => 'content_display',
						'tags' => 'name="content"',
						'label' => qa_lang_html('admin/category_description'),
						'value' => qa_html(isset($incontent) ? $incontent : @$editcategory['content']),
						'error' => qa_html(@$errors['content']),
						'rows' => 2,
					),
				),

				'buttons' => array(
					'save' => array(
						'tags' => 'id="dosaveoptions"',
						// just used for qa_recalc_click
						'label' => qa_lang_html(isset($editcategory['catid']) ? 'main/save_button' : 'bp_lang/add_cat_button'),
					),

					'cancel' => array(
						'tags' => 'name="docancel"',
						'label' => qa_lang_html('main/cancel_button'),
					),
				),

				'hidden' => array(
					'dosavecategory' => '1',
					// for IE
					'edit' => @$editcategory['catid'],
					'parent' => @$editcategory['parentid'],
					'setparent' => (int) $setparent,
					'code' => qa_get_form_security_code('admin/bp_categories'),
				),
			);


			if ($setparent) {
				unset($this->content['form']['fields']['delete']);
				unset($this->content['form']['fields']['reassign']);
				unset($this->content['form']['fields']['blogposts']);
				unset($this->content['form']['fields']['content']);

				$this->content['form']['fields']['parent'] = array(
					'label' => qa_lang_html('admin/category_parent'),
				);

				$childdepth = bp_db_cat_child_depth($editcategory['catid']);

				bp_set_up_cat_field(
					$qa_content, $this->content['form']['fields']['parent'],
					'parent',
					isset($incategories) ? $incategories : $categories, isset($inparentid) ? $inparentid : @$editcategory['parentid'],
					true,
					true, QA_CATEGORY_DEPTH - 1 - $childdepth,
					@$editcategory['catid']
				);

				$this->content['form']['fields']['parent']['options'][''] = qa_lang_html('admin/category_top_level');

				@$this->content['form']['fields']['parent']['note'] .= qa_lang_html_sub('admin/category_max_depth_x', QA_CATEGORY_DEPTH);

			} elseif (isset($editcategory['catid'])) { // existing category
				if ($hassubcategory) {
					$this->content['form']['fields']['name']['note'] = qa_lang_html('admin/category_no_delete_subs');
					unset($this->content['form']['fields']['delete']);
					unset($this->content['form']['fields']['reassign']);

				} else {
					$this->content['form']['fields']['delete'] = array(
						'tags' => 'name="dodelete" id="dodelete"',
						'label' =>
						'<span id="reassign_shown">' . qa_lang_html('bp_lang/delete_cat_reassign') . '</span>' .
						'<span id="reassign_hidden" style="display:none;">' . qa_lang_html('admin/delete_category') . '</span>',
						'value' => 0,
						'type' => 'checkbox',
					);

					$this->content['form']['fields']['reassign'] = array(
						'id' => 'reassign_display',
						'tags' => 'name="reassign"',
					);

					bp_set_up_cat_field(
						$this->content, $this->content['form']['fields']['reassign'],
						'reassign',
						$categories, $editcategory['parentid'],
						true,
						true,
						null, $editcategory['catid']
					);
				}

				$this->content['form']['fields']['blogposts'] = array(
					'label' => qa_lang_html('bp_lang/total_ps'),
					'type' => 'static',
					'value' => '<a href="' . qa_path_html('blog/' . bp_cat_path_request($categories, $editcategory['catid'])) . '">' .
					($editcategory['pcount'] == 1
						? qa_lang_html_sub('bp_lang/1_blogpost', '1', '1')
						: qa_lang_html_sub('bp_lang/x_blogposts', qa_format_number($editcategory['pcount']))
					) . '</a>',
				);

				if ($hassubcategory && !qa_opt('bp_allow_no_sub_blogcat')) {
					$nosubcount = bp_db_count_catid_ps($editcategory['catid']);

					if ($nosubcount) {
						$this->content['form']['fields']['blogposts']['error'] =
							strtr(
								qa_lang_html('admin/category_no_sub_error'),
								array(
									'^q' => qa_format_number($nosubcount),
									'^1' => '<a href="' . qa_path_html(qa_request(), array('edit' => $editcategory['catid'], 'missing' => 1)) . '">',
									'^2' => '</a>',
								)
							);
					}
				}

				qa_set_display_rules(
					$qa_content,
					array(
						'position_display' => '!dodelete',
						'slug_display' => '!dodelete',
						'content_display' => '!dodelete',
						'parent_display' => '!dodelete',
						'children_display' => '!dodelete',
						'reassign_display' => 'dodelete',
						'reassign_shown' => 'dodelete',
						'reassign_hidden' => '!dodelete',
					)
				);

			} else { // new category
				unset($this->content['form']['fields']['delete']);
				unset($this->content['form']['fields']['reassign']);
				unset($this->content['form']['fields']['slug']);
				unset($this->content['form']['fields']['blogposts']);

				$this->content['focusid'] = 'name';
			}

			if (!$setparent) {
				$pathhtml = bp_cat_path_html($categories, @$editcategory['parentid']);

				if (count($categories)) {
					$this->content['form']['fields']['parent'] = array(
						'id' => 'parent_display',
						'label' => qa_lang_html('admin/category_parent'),
						'type' => 'static',
						'value' => (strlen($pathhtml) ? $pathhtml : qa_lang_html('admin/category_top_level')),
					);

					$this->content['form']['fields']['parent']['value'] =
						'<a href="' . qa_path_html(qa_request(), array('edit' => @$editcategory['parentid'])) . '">' .
						$this->content['form']['fields']['parent']['value'] . '</a>';

					if (isset($editcategory['catid'])) {
						$this->content['form']['fields']['parent']['value'] .= ' - ' .
							'<a href="' . qa_path_html(qa_request(), array('edit' => $editcategory['catid'], 'setparent' => 1)) .
							'" style="white-space: nowrap;">' . qa_lang_html('admin/category_move_parent') . '</a>';
					}
				}

				$positionoptions = array();

				$previous = null;
				$passedself = false;

				foreach ($categories as $key => $category) {
					if (!strcmp($category['parentid'], @$editcategory['parentid'])) {
						if (isset($previous))
							$positionhtml = qa_lang_html_sub('admin/after_x', qa_html($passedself ? $category['title'] : $previous['title']));
						else
							$positionhtml = qa_lang_html('admin/first');

						$positionoptions[$category['position']] = $positionhtml;

						if (!strcmp($category['catid'], @$editcategory['catid']))
							$passedself = true;

						$previous = $category;
					}
				}

				if (isset($editcategory['position']))
					$positionvalue = $positionoptions[$editcategory['position']];
				else {
					$positionvalue = isset($previous) ? qa_lang_html_sub('admin/after_x', qa_html($previous['title'])) : qa_lang_html('admin/first');
					$positionoptions[1 + @max(array_keys($positionoptions))] = $positionvalue;
				}

				$this->content['form']['fields']['position'] = array(
					'id' => 'position_display',
					'tags' => 'name="position"',
					'label' => qa_lang_html('admin/position'),
					'type' => 'select',
					'options' => $positionoptions,
					'value' => $positionvalue,
				);

				if (isset($editcategory['catid'])) {
					$catdepth = count(bp_cat_path($categories, $editcategory['catid']));

					if ($catdepth < QA_CATEGORY_DEPTH) {
						$childrenhtml = '';

						foreach ($categories as $category) {
							if (!strcmp($category['parentid'], $editcategory['catid'])) {
								$childrenhtml .= (strlen($childrenhtml) ? ', ' : '') .
									'<a href="' . qa_path_html(qa_request(), array('edit' => $category['catid'])) . '">' . qa_html($category['title']) . '</a>' .
									' (' . $category['pcount'] . ')';
							}
						}

						if (!strlen($childrenhtml))
							$childrenhtml = qa_lang_html('admin/category_no_subs');

						$childrenhtml .= ' - <a href="' . qa_path_html(qa_request(), array('addsub' => $editcategory['catid'])) .
							'" style="white-space: nowrap;"><b>' . qa_lang_html('admin/category_add_sub') . '</b></a>';

						$this->content['form']['fields']['children'] = array(
							'id' => 'children_display',
							'label' => qa_lang_html('admin/category_subs'),
							'type' => 'static',
							'value' => $childrenhtml,
						);
					} else {
						$this->content['form']['fields']['name']['note'] = qa_lang_html_sub('admin/category_no_add_subs_x', QA_CATEGORY_DEPTH);
					}

				}
			}

		} else {
			$this->content['form'] = array(
				'tags' => 'method="post" action="' . qa_path_html(qa_request()) . '"',

				'ok' => $savedoptions ? qa_lang_html('admin/options_saved') : null,

				'style' => 'tall',

				'fields' => array(
					'intro' => array(
						'label' => qa_lang_html('bp_lang/categories_introduction'),
						'type' => 'static',
					),
				),

				'buttons' => array(
					'save' => array(
						'tags' => 'name="dosaveoptions" id="dosaveoptions"',
						'label' => qa_lang_html('main/save_button'),
					),

					'add' => array(
						'tags' => 'name="doaddcategory"',
						'label' => qa_lang_html('bp_lang/add_cat_button'),
					),
				),

				'hidden' => array(
					'code' => qa_get_form_security_code('admin/bp_categories'),
				),
			);

			if (count($categories)) {
				unset($this->content['form']['fields']['intro']);

				$navcategoryhtml = '';

				foreach ($categories as $category) {
					if (!isset($category['parentid'])) {
						$navcategoryhtml .=
							'<a href="' . qa_path_html('admin/bp_categories', array('edit' => $category['catid'])) . '">' .
							qa_html($category['title']) .
							'</a> - ' .
							($category['pcount'] == 1
								? qa_lang_html_sub('bp_lang/1_blogpost', '1', '1')
								: qa_lang_html_sub('bp_lang/x_blogposts', qa_format_number($category['pcount']))
							) . '<br/>';
					}
				}

				$this->content['form']['fields']['nav'] = array(
					'label' => qa_lang_html('bp_lang/top_level_categories'),
					'type' => 'static',
					'value' => $navcategoryhtml,
				);

				$this->content['form']['fields']['bp_allow_no_blogcat'] = array(
					'label' => qa_lang_html('bp_lang/bp_allow_no_blogcat'),
					'tags' => 'name="option_bp_allow_no_blogcat"',
					'type' => 'checkbox',
					'value' => qa_opt('bp_allow_no_blogcat'),
				);

				if (!qa_opt('bp_allow_no_blogcat')) {
					$nocatcount = bp_db_count_catid_ps(null);

					if ($nocatcount) {
						$this->content['form']['fields']['bp_allow_no_blogcat']['error'] =
							strtr(
								qa_lang_html('bp_lang/category_none_error'),
								array(
									'^q' => qa_format_number($nocatcount),
									'^1' => '<a href="' . qa_path_html(qa_request(), array('missing' => 1)) . '">',
									'^2' => '</a>',
								)
							);
					}
				}

				$this->content['form']['fields']['bp_allow_no_sub_blogcat'] = array(
					'label' => qa_lang_html('bp_lang/bp_allow_no_sub_blogcat'),
					'tags' => 'name="option_bp_allow_no_sub_blogcat"',
					'type' => 'checkbox',
					'value' => qa_opt('bp_allow_no_sub_blogcat'),
				);

			} else
				unset($this->content['form']['buttons']['save']);
		}

		if (qa_get('recalc')) {
			$this->content['form']['ok'] = '<span id="recalc_ok">' . qa_lang_html('admin/recalc_categories') . '</span>';
			$this->content['form']['hidden']['code_recalc'] = qa_get_form_security_code('admin/recalc');
		
			$this->content['script_rel'][] = 'qa-content/qa-admin.js?' . QA_VERSION;
			$this->content['script_var']['qa_warning_recalc'] = qa_lang('admin/stop_recalc_warning');
		
			$this->content['script_onloads'][] = array(
				"qa_recalc_click('dorecalccategories', document.getElementById('dosaveoptions'), null, 'recalc_ok');"
			);
		}
		
		$this->content['navigation']['sub'] = qa_admin_sub_navigation();

		return $this->content;
	}

}

/*
 Omit PHP closing tag to help avoid accidental output */