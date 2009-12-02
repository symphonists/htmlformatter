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
			'options'	=> array()
		);
		protected $_fields = array();
		protected $_prepared = false;
		protected $_status = '';
		protected $_formatters = array();
		protected $_uri = null;
		protected $_valid = true;
		
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
				$this->_formatters = $this->_driver->getFormatters();
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
			
			if (!empty($this->_errors['about'])) {
				$this->_valid = false;
				return;
			}
			
		// Save ---------------------------------------------------------------
			
			$name = $this->_handle;
			
			if (!$this->_driver->setFormatter($name, $error, $this->_fields)) {
				$this->_valid = false;
				$this->_errors['about']['name'] = $error;
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
			
			if (!$this->_valid) $this->pageAlert('
				An error occurred while processing this form.
				<a href="#error">See below for details.</a>',
				Alert::ERROR
			);
			
			// Status message:
			if ($this->_status) {
				$action = null;
				
				switch($this->_status) {
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
			
			$label = new XMLElement('h3', __('Options'));
			$label->setAttribute('class', 'html-formatter-label');
			$fieldset->appendChild($label);
			
			$options = new XMLElement('div');
			$options->setAttribute('class', 'html-formatter-options');
			
			$row = new XMLElement('div');
			
			// Pretty acronyms:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_acronyms]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_acronyms']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify&nbsp;acronyms?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				Convert <code>ABBR(Abbreviation or acronym description)</code> into its HTML&nbsp;counterpart.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			// Pretty ampersands:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_ampersands]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_ampersands']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify&nbsp;ampersands?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				For example <code>&amp;amp;</code> would become <code>&lt;span class="ampersand"&gt;&amp;amp;&lt;/span&gt;</code>.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			// Pretty quotation marks:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_quotation_marks]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_quotation_marks']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify quotation&nbsp;marks?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				Convert any single and double quotation marks into their fancy&nbsp;counterparts.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			$options->appendChild($row);
			$row = new XMLElement('div');
			
			// Pretty dashes:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_dashes]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_dashes']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify dashes?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				Convert any single dashes into en-dashes and double dashes into&nbsp;em-dashes.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			// Pretty ellipses:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_ellipses]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_ellipses']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify&nbsp;ellipses?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				For example <code>foo...</code> would become <code>&lt;span class="ellipsis"&gt;foo&amp;hellip;&lt;/span&gt;</code>.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			// Pretty sentence spacing:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_sentence_spacing]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_sentence_spacing']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify sentence&nbsp;spacing?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				Convert single spaces between sentences to double spaces.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			$options->appendChild($row);
			$row = new XMLElement('div');
			
			// Pretty symbols:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][pretty_symbols]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['pretty_symbols']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prettify symbols?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				Convert <code>(C)</code>, <code>(R)</code> or <code>TM</code> into their respective&nbsp;symbols.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			// Prevent widowed words:
			$option = new XMLElement('div');
			$option->setAttribute('class', 'html-formatter-option');
			
			$input = Widget::Input('fields[options][prevent_widowed_words]', 'yes');
			$input->setAttribute('type', 'checkbox');
			
			if ($this->_fields['options']['prevent_widowed_words']) {
				$input->setAttribute('checked', 'checked');
			}
			
			$label = Widget::Label(__(
				'%s Prevent widowed&nbsp;words?', array(
					$input->generate()
				)
			));
			$option->appendChild($label);
			
			$help = new XMLElement('p', __('
				Prevent words at the end of a paragraph or header from being wrapped onto the following&nbsp;line.
			'));
			$help->setAttribute('class', 'help');
			
			$option->appendChild($help);
			$row->appendChild($option);
			
			$options->appendChild($row);
			$fieldset->appendChild($options);
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
		
	/*-------------------------------------------------------------------------
		Index
	-------------------------------------------------------------------------*/
		
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
			
			$tableHead = array(
				array('Name', 'col'),
				array('Description', 'col'),
				array('Modified', 'col'),
				array('Author', 'col')
			);	
			
			$tableBody = array();
			
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
		}
	}
	
?>