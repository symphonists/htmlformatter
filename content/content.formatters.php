<?php
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	
	class ContentExtensionHTMLFormatterFormatters extends AdministrationPage {
		protected $_handle = '';
		protected $_action = '';
		protected $_driver = null;
		protected $_editing = false;
		protected $_errors = array(
			'about'		=> array(),
			'options'	=> array(),
			'other'		=> ''
		);
		protected $_fields = array();
		protected $_status = '';
		protected $_formatters = array();
		protected $_uri = null;
		protected $_valid = true;
		protected $_pagination = null;
		protected $_table_column = 'name';
		protected $_table_columns = array();
		protected $_table_direction = 'asc';
		
		public function __construct(&$parent){
			parent::__construct($parent);
			
			$this->_uri = URL . '/symphony/extension/htmlformatter';
			$this->_driver = $this->_Parent->ExtensionManager->create('htmlformatter');
		}
		
		public function build($context) {
			if (@$context[0] == 'edit' or @$context[0] == 'new') {
				if ($this->_editing = $context[0] == 'edit') {
					$this->_fields = $this->_driver->getFormatter($context[1]);
				}
				
				$this->_handle = $context[1];
				$this->_status = $context[2];
			}
			
			else {
				$this->__prepareIndex();
			}
			
			parent::build($context);
		}
		
		public function __actionNew() {
			$this->__actionEdit();
		}
		
		public function __actionEdit() {
			if (@array_key_exists('delete', $_POST['action'])) {
				$this->__actionEditDelete();
			}
			
			else {
				$this->__actionEditNormal();
			}
		}
		
		public function __actionEditDelete() {
			General::deleteFile($this->_fields['about']['html-formatter-file']);
			
			redirect("{$this->_uri}/formatters/");
		}
		
		public function __actionEditNormal() {
			header('content-type: text/plain');
			
		// Validate -----------------------------------------------------------
			
			// About info:
			if (!empty($_POST['fields']['about']['name'])) {
				$this->_fields['about']['name'] = $_POST['fields']['about']['name'];
			}
			
			else {
				$this->_errors['about']['name'] = 'Name must not be empty.';
			}
			
			if (!empty($_POST['fields']['about']['description'])) {
				$this->_fields['about']['description'] = $_POST['fields']['about']['description'];
			}
			
			// Options toggle:
			$this->_fields['options']['pretty_acronyms'] = (@$_POST['fields']['options']['pretty_acronyms'] == 'yes');
			$this->_fields['options']['pretty_ampersands'] = (@$_POST['fields']['options']['pretty_ampersands'] == 'yes');
			$this->_fields['options']['pretty_dashes'] = (@$_POST['fields']['options']['pretty_dashes'] == 'yes');
			$this->_fields['options']['pretty_ellipses'] = (@$_POST['fields']['options']['pretty_ellipses'] == 'yes');
			$this->_fields['options']['pretty_quotation_marks'] = (@$_POST['fields']['options']['pretty_quotation_marks'] == 'yes');
			$this->_fields['options']['pretty_sentence_spacing'] = (@$_POST['fields']['options']['pretty_sentence_spacing'] == 'yes');
			$this->_fields['options']['pretty_symbols'] = (@$_POST['fields']['options']['pretty_symbols'] == 'yes');
			$this->_fields['options']['prevent_widowed_words'] = (@$_POST['fields']['options']['prevent_widowed_words'] == 'yes');
			$this->_fields['options']['editor_name'] = @$_POST['fields']['options']['editor_name'];
			
			if (!empty($this->_errors['about'])) {
				$this->_valid = false;
				return;
			}
			
		// Save ---------------------------------------------------------------
			
			$name = $this->_handle;
			
			if (!$this->_driver->setFormatter($name, $error, $this->_fields)) {
				$this->_valid = false;
				$this->_errors['other'] = $error;
				return;
			}
			
			if ($this->_editing) {
				redirect("{$this->_uri}/formatters/edit/{$name}/saved/");
			}
			
			else {
				redirect("{$this->_uri}/formatters/edit/{$name}/created/");
			}
		}
		
		public function __viewNew() {
			$this->__viewEdit();
		}
		
		public function __viewEdit() {
			$this->addStylesheetToHead(URL . '/extensions/htmlformatter/assets/formatters.css', 'screen', 1000);
			
		// Status: -----------------------------------------------------------
			
			if (!$this->_valid) {
				$message = __('An error occurred while processing this form <a href="#error">See below for details.</a>');
				
				if ($this->_errors['other']) {
					$message = $this->_errors['other'];
				}
				
				$this->pageAlert($message, Alert::ERROR);
			}
			
			// Status message:
			if ($this->_status) {
				$action = null;
				
				switch ($this->_status) {
					case 'saved': $action = '%1$s updated at %2$s. <a href="%3$s">Create another?</a> <a href="%4$s">View all %5$s</a>'; break;
					case 'created': $action = '%1$s created at %2$s. <a href="%3$s">Create another?</a> <a href="%4$s">View all %5$s</a>'; break;
				}
				
				if ($action) $this->pageAlert(
					__(
						$action, array(
							__('Formatter'), 
							DateTimeObj::get(__SYM_TIME_FORMAT__), 
							URL . '/symphony/extension/htmlformatter/formatters/new/', 
							URL . '/symphony/extension/htmlformatter/formatters/',
							__('Formatters')
						)
					),
					Alert::SUCCESS
				);
			}
			
		// Header: ------------------------------------------------------------
			
			$this->setPageType('form');
			$this->setTitle('Symphony &ndash; Formatters &ndash; ' . (
				@$this->_fields['about']['name'] ? $this->_fields['about']['name'] : 'Untitled'
			));
			$this->appendSubheading("<a href=\"{$this->_uri}/formatters/\">Formatters</a> &raquo; " . (
				@$this->_fields['about']['name'] ? $this->_fields['about']['name'] : 'Untitled'
			));
			
		// About --------------------------------------------------------------
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input(
				'fields[about][name]',
				General::sanitize(@$this->_fields['about']['name'])
			));
			
			if (isset($this->_errors['about']['name'])) {
				$label = Widget::wrapFormElementWithError($label, $this->_errors['about']['name']);
			}
			
			$group->appendChild($label);
			
			$label = Widget::Label(__('Description <i>Optional</i>'));
			$label->appendChild(Widget::Input(
				'fields[about][description]',
				General::sanitize(@$this->_fields['about']['description'])
			));
			
			if (isset($this->_errors['about']['description'])) {
				$label = Widget::wrapFormElementWithError($label, $this->_errors['about']['description']);
			}
			
			$group->appendChild($label);
			$fieldset->appendChild($group);
			
		// Options ------------------------------------------------------------
			
			$this->buildOptions(
				__('Options'), 'checkbox', $fieldset,
				array(
					array(
						'label'		=> '%s Prettify&nbsp;acronyms?',
						'help'		=> 'Convert <code>ABBR(Abbreviation or acronym description)</code> into its HTML&nbsp;counterpart.',
						'name'		=> 'pretty_acronyms',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prettify&nbsp;ampersands?',
						'help'		=> 'For example &amp; would become <code>&lt;span class="ampersand"&gt;&amp;&lt;/span&gt;</code>.',
						'name'		=> 'pretty_ampersands',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prettify quotation&nbsp;marks?',
						'help'		=> 'Convert any single and double quotation marks into their fancy&nbsp;counterparts.',
						'name'		=> 'pretty_quotation_marks',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prettify&nbsp;dashes?',
						'help'		=> 'Convert any single dashes into en-dashes and double dashes into&nbsp;em-dashes.',
						'name'		=> 'pretty_dashes',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prettify&nbsp;ellipses?',
						'help'		=> 'For example <code>foo...</code> would become &lt;span class="ellipsis"&gt;foo&hellip;&lt;/span&gt;.',
						'name'		=> 'pretty_ellipses',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prettify sentence&nbsp;spacing?',
						'help'		=> 'Convert single spaces between sentences to double&nbsp;spaces.',
						'name'		=> 'pretty_sentence_spacing',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prettify&nbsp;symbols?',
						'help'		=> 'Convert <code>(C)</code>, <code>(R)</code> or <code>TM</code> into their respective&nbsp;symbols.',
						'name'		=> 'pretty_symbols',
						'value'		=> 'yes'
					),
					array(
						'label'		=> '%s Prevent widowed&nbsp;words?',
						'help'		=> 'Prevent words at the end of a paragraph or header from being wrapped onto the following&nbsp;line.',
						'name'		=> 'prevent_widowed_words',
						'value'		=> 'yes'
					)
				)
			);
			
		// Editor -------------------------------------------------------------
			
			$this->buildOptions(
				__('Editors'), 'radio', $fieldset,
				array(
					array(
						'label'		=> '%s Disable all&nbsp;editors?',
						'help'		=> 'Don\'t use any of the available editor&nbsp;components.',
						'name'		=> 'editor_name',
						'value'		=> 'none',
						'default'	=> true
					),
					array(
						'label'		=> '%s Use the CKEditor&nbsp;editor?',
						'help'		=> '<a href="http://ckeditor.com/">CKEditor</a> is a comprehensive WYSIWYG editor&nbsp;component.',
						'name'		=> 'editor_name',
						'value'		=> 'ckeditor'
					),
					array(
						'label'		=> '%s Use the JWYSIWYG&nbsp;editor?',
						'help'		=> '<a href="http://code.google.com/p/jwysiwyg/">JWYSIWYG</a> is a light-weight WYSIWYG editor&nbsp;component.',
						'name'		=> 'editor_name',
						'value'		=> 'jwysiwyg'
					)
				)
			);
			
			$this->Form->appendChild($fieldset);
			
		// Footer -------------------------------------------------------------
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(
				Widget::Input('action[save]',
					($this->_editing ? 'Save Changes' : 'Create Template'),
					'submit', array(
						'accesskey'		=> 's'
					)
				)
			);
			
			if ($this->_editing) {
				$button = new XMLElement('button', 'Delete');
				$button->setAttributeArray(array(
					'name'		=> 'action[delete]',
					'class'		=> 'confirm delete',
					'title'		=> 'Delete this formatter'
				));
				$div->appendChild($button);
			}
			
			$this->Form->appendChild($div);
		}
		
		public function buildOptions($title, $type, $fieldset, $data) {
			$label = new XMLElement('h3', $title);
			$label->setAttribute('class', 'html-formatter-label');
			$fieldset->appendChild($label);
			
			$options = new XMLElement('div');
			$options->setAttribute('class', 'html-formatter-options');
			
			$row = null;
			
			foreach ($data as $index => $item) {
				if ($index % 3 == 0) {
					if ($row) $options->appendChild($row);
					
					$row = new XMLElement('div');
				}
				
				// Prevent widowed words:
				$option = new XMLElement('div');
				$option->setAttribute('class', 'html-formatter-option');
				
				$input = Widget::Input('fields[options][' . $item['name'] . ']', $item['value']);
				$input->setAttribute('type', $type);
				
				if ($this->_fields['options'][$item['name']] == $item['value']) {
					$input->setAttribute('checked', 'checked');
				}
				
				else if (@!$this->_fields['options'][$item['name']] and @$item['default']) {
					$input->setAttribute('checked', 'checked');
				}
				
				$label = Widget::Label(__(
					$item['label'], array(
						$input->generate()
					)
				));
				$option->appendChild($label);
				
				$help = new XMLElement('p', __($item['help']));
				$help->setAttribute('class', 'help');
				
				$option->appendChild($help);
				$row->appendChild($option);
			}
			
			$options->appendChild($row);
			$fieldset->appendChild($options);
		}
		
	/*-------------------------------------------------------------------------
		Index
	-------------------------------------------------------------------------*/
		
		public function generateLink($values) {
			$values = array_merge(array(
				'pg'	=> $this->_pagination->page,
				'sort'	=> $this->_table_column,
				'order'	=> $this->_table_direction
			), $values);
			
			$count = 0;
			$link = $this->_Parent->getCurrentPageURL();
			
			foreach ($values as $key => $value) {
				if ($count++ == 0) {
					$link .= '?';
				}
				
				else {
					$link .= '&amp;';
				}
				
				$link .= "{$key}={$value}";
			}
			
			return $link;
		}
		
		public function __prepareIndex() {
			$this->_table_columns = array(
				'name'			=> array(__('Name'), true),
				'description'	=> array(__('Description'), false),
				'modified'		=> array(__('Modified'), true),
				'author'		=> array(__('Author'), true)
			);
			
			if (@$_GET['sort'] and $this->_table_columns[$_GET['sort']][1]) {
				$this->_table_column = $_GET['sort'];
			}
			
			if (@$_GET['order'] == 'desc') {
				$this->_table_direction = 'desc';
			}
			
			$this->_pagination = (object)array(
				'page'		=> (@(integer)$_GET['pg'] > 1 ? (integer)$_GET['pg'] : 1),
				'length'	=> $this->_Parent->Configuration->get('pagination_maximum_rows', 'symphony')
			);
			
			$this->_formatters = $this->_driver->getFormatters(
				$this->_table_column,
				$this->_table_direction,
				$this->_pagination->page,
				$this->_pagination->length
			);
			
			// Calculate pagination:
			$this->_pagination->start = max(1, (($page - 1) * 17));
			$this->_pagination->end = (
				$this->_pagination->start == 1
				? $this->_pagination->length
				: $start + count($this->_formatters)
			);
			$this->_pagination->total = $this->_driver->countFormatters();
			$this->_pagination->pages = ceil(
				$this->_pagination->total / $this->_pagination->length
			);
		}
		
		public function __actionIndex() {
			$checked = @array_keys($_POST['items']);
			
			if (is_array($checked) and !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						foreach ($checked as $name) {
							$data = $this->_driver->getFormatter($name);
							
							General::deleteFile($data['about']['html-formatter-file']);
						}
						
						redirect("{$this->_uri}/formatters/");
						break;
				}
			}
		}
		
		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle('Symphony &ndash; Formatters');
			
			$this->appendSubheading('Formatters', Widget::Anchor(
				'Create New', "{$this->_uri}/formatters/new/",
				'Create a new formatter', 'create button'
			));
			
			$tableHead = array();
			$tableBody = array();
			
			// Columns, with sorting:
			foreach ($this->_table_columns as $column => $values) {
				if ($values[1]) {
					if ($column == $this->_table_column) {
						if ($this->_table_direction == 'desc') {
							$direction = 'asc';
							$label = 'ascending';
						}
						
						else {
							$direction = 'desc';
							$label = 'descending';
						}
					}
					
					else {
						$direction = 'asc';
						$label = 'ascending';
					}
					
					$link = $this->generateLink(array(
						'sort'	=> $column,
						'order'	=> $direction
					));
					
					$anchor = Widget::Anchor($values[0], $link, __("Sort by {$label} " . strtolower($values[0])));
					
					if ($column == $this->_table_column) {
						$anchor->setAttribute('class', 'active');
					}
					
					$tableHead[] = array($anchor, 'col');
				}
				
				else {
					$tableHead[] = array($values[0], 'col');
				}
			}
			
			if (!is_array($this->_formatters) or empty($this->_formatters)) {
				$tableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', null, count($tableHead))))
				);
			}
			
			else {
				foreach ($this->_formatters as $formatter) {
					$formatter = (object)$formatter;
					
					$col_name = Widget::TableData(
						Widget::Anchor(
							$formatter->name,
							"{$this->_uri}/formatters/edit/{$formatter->handle}/"
						)
					);
					$col_name->appendChild(Widget::Input("items[{$formatter->handle}]", null, 'checkbox'));
					
					$col_date = Widget::TableData(
						DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($formatter->{'html-formatter-updated'}))
					);
					
					if (!empty($formatter->description)) {
						$col_description = Widget::TableData(
							General::sanitize($formatter->description)
						);
					}
					
					else {
						$col_description = Widget::TableData('None', 'inactive');
					}
					
					if (isset($formatter->author['website'])) {
						$col_author = Widget::TableData(Widget::Anchor(
							$formatter->author['name'],
							General::validateURL($formatter->author['website'])
						));
					}
					
					else if (isset($formatter->author['email'])) {
						$col_author = Widget::TableData(Widget::Anchor(
							$formatter->author['name'],
							'mailto:' . $formatter->author['email']
						));	
					}
					
					else {
						$col_author = Widget::TableData($formatter->author['name']);
					}
					
					$tableBody[] = Widget::TableRow(array($col_name, $col_description, $col_date, $col_author), null);
				}
			}
			
			$table = Widget::Table(
				Widget::TableHead($tableHead), null, 
				Widget::TableBody($tableBody)
			);
			
			$this->Form->appendChild($table);
			
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			
			$options = array(
				array(null, false, 'With Selected...'),
				array('delete', false, 'Delete')									
			);

			$actions->appendChild(Widget::Select('with-selected', $options));
			$actions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));
			
			$this->Form->appendChild($actions);
			
			// Pagination:
			if ($this->_pagination->pages > 1) {
				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'page');
				
				// First:
				$li = new XMLElement('li');
				$li->setValue(__('First'));
				
				if ($this->_pagination->page > 1) {
					$li->setValue(
						Widget::Anchor(__('First'), $this->generateLink(array(
							'pg' => 1
						)))->generate()
					);
				}
				
				$ul->appendChild($li);
				
				// Previous:
				$li = new XMLElement('li');
				$li->setValue(__('&larr; Previous'));
				
				if ($this->_pagination->page > 1) {
					$li->setValue(
						Widget::Anchor(__('&larr; Previous'), $this->generateLink(array(
							'pg' => $this->_pagination->page - 1
						)))->generate()
					);
				}
				
				$ul->appendChild($li);
				
				// Summary:
				$li = new XMLElement('li', __('Page %s of %s', array(
					$this->_pagination->page,
					max($this->_pagination->page, $this->_pagination->pages)
				)));
				$li->setAttribute('title', __('Viewing %s - %s of %s entries', array(
					$this->_pagination->start,
					$this->_pagination->end,
					$this->_pagination->total
				)));
				$ul->appendChild($li);
				
				// Next:
				$li = new XMLElement('li');
				$li->setValue(__('Next &rarr;'));
				
				if ($this->_pagination->page < $this->_pagination->pages) {
					$li->setValue(
						Widget::Anchor(__('Next &rarr;'), $this->generateLink(array(
							'pg' => $this->_pagination->page + 1
						)))->generate()
					);
				}
				
				$ul->appendChild($li);
				
				// Last:
				$li = new XMLElement('li');
				$li->setValue(__('Last'));
				
				if ($this->_pagination->page < $this->_pagination->pages) {
					$li->setValue(
						Widget::Anchor(__('Last'), $this->generateLink(array(
							'pg' => $this->_pagination->pages
						)))->generate()
					);
				}
				
				$ul->appendChild($li);
				$this->Form->appendChild($ul);
			}
		}
	}
	
?>