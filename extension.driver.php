<?php
/*---------------------------------------------------------------------------*/
	
	require_once(TOOLKIT . '/class.textformattermanager.php');
	
/*---------------------------------------------------------------------------*/
	
	class Extension_HTMLFormatter extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'HTML Formatter',
				'version'		=> '2.1.1',
				'release-date'	=> '2010-01-07',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description'	=> 'Allows HTML to be used as input, safely.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'initaliseAdminPageHead'
				)
			);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> 100,
					'name'	=> 'Formatters',
					'link'	=> '/formatters/'
				)
			);
		}
		
	/*-------------------------------------------------------------------------
		Delegates:
	-------------------------------------------------------------------------*/
		
		public function initaliseAdminPageHead($context) {
			$page = Administration::instance()->Page;
			$includes = $editors = array();
			$position = 29751000;
			
			foreach ($this->getFormatters('name', 'asc', 1, 10000, true) as $formatter) {
				$class = $formatter['about']['handle'];
				$editor = $formatter['options']['editor_name'];
				
				$editors[$class] = $editor;
				
				if (is_null($editor) or $editor == 'none') continue;
				
				$includes[] = $editor;
			}
			
			$includes = array_unique($includes);
			
			$script = new XMLElement('script');
			$script->setAttribute('type', 'text/javascript');
			$script->setValue(sprintf(
				'var HTMLFormatterEditors = %s;',
				json_encode($editors)
			));
			
			foreach ($includes as $index => $include) {
				switch ($include) {
					case 'ckeditor':
						$page->addScriptToHead(URL . '/extensions/htmlformatter/editors/ckeditor/ckeditor.js', $position++);
						break;
					case 'jwysiwyg':
						$page->addScriptToHead(URL . '/extensions/htmlformatter/editors/jwysiwyg/jquery.wysiwyg.js', $position++);
						$page->addStylesheetToHead(URL . '/extensions/htmlformatter/editors/jwysiwyg/jquery.wysiwyg.css', 'screen', $position++);
						break;
					case 'snicked':
						$page->addScriptToHead(URL . '/extensions/htmlformatter/editors/snicked/assets/jquery.snickedCore.js', $position++);
						$page->addScriptToHead(URL . '/extensions/htmlformatter/editors/snicked/assets/jquery.snicked.js', $position++);
						break;
				}
			}
			
			$page->addElementToHead($script, $position++);
			$page->addScriptToHead(URL . '/extensions/htmlformatter/assets/editors.js', $position++);
			$page->addStylesheetToHead(URL . '/extensions/htmlformatter/assets/editors.css', 'screen', 40);
		}
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		
		public function countFormatters() {
			$tfm = new TextformatterManager($this->_Parent);
			$results = 0;
			
			foreach ($tfm->listAll() as $handle => $about) {
				if (!isset($about['html-formatter-editable'])) continue;
				
				$results++;
			}
			
			return $results;
		}
		
		public function getFormatters($column = 'name', $direction = 'asc', $page = 1, $length = 10000, $full = false) {
			$tfm = new TextformatterManager($this->_Parent);
			$results = array();
			
			foreach ($tfm->listAll() as $handle => $about) {
				if (!isset($about['html-formatter-editable'])) continue;
				
				if (!$full) {
					$about['handle'] = $handle;
					$results[] = $about;
				}
				
				else {
					$formatter = $tfm->create($handle);
					$about = $formatter->about();
					$about['handle'] = $handle;
					
					$results[] = array(
						'about'		=> $about,
						'options'	=> $formatter->options()
					);
				}
			}
			
			// Sorting:
			if ($column == 'name') {
				usort($results, array($this, 'getFormattersSortByName'));
			}
			
			else if ($column == 'modified') {
				usort($results, array($this, 'getFormattersSortByModified'));
			}
			
			else if ($column == 'author') {
				usort($results, array($this, 'getFormattersSortByAuthor'));
			}
			
			if ($direction != 'asc') {
				$results = array_reverse($results);
			}
			
			// Pagination:
			$results = array_slice($results, ($page - 1) * $length, $length);
			
			return $results;
		}
		
		protected function getFormattersSortByName($a, $b) {
			return strcmp($a['name'], $b['name']);
		}
		
		protected function getFormattersSortByModified($a, $b) {
			return strtotime($a['html-formatter-updated']) > strtotime($b['html-formatter-updated']);
		}
		
		protected function getFormattersSortByAuthor($a, $b) {
			return strcmp($a['author']['name'], $b['author']['name']);
		}
		
		public function getFormatter($name) {
			$tfm = new TextformatterManager($this->_Parent);
			$formatter = $tfm->create($name);
			
			return array(
				'about'		=> $formatter->about(),
				'options'	=> $formatter->options()
			);
		}
		
		public function setFormatter(&$name, &$error, $new) {
			$template = file_get_contents(EXTENSIONS . '/htmlformatter/templates/formatter.php');
			$old = (!empty($name) ? $this->getFormatter($name) : array());
			
			// Update author:
			if (!isset($new['about']['author'])) {
				$new['about']['author'] = array(
					'name'		=> $this->_Parent->Author->getFullName(),
					'website'	=> URL,
					'email'		=> $this->_Parent->Author->get('email')
				);
			}
			
			// Update dates:
			$new['about']['html-formatter-created'] = DateTimeObj::getGMT('c', @strtotime($new['about']['html-formatter-created']));
			$new['about']['html-formatter-updated'] = DateTimeObj::getGMT('c');
			
			// New name:
			$name = str_replace('-', '', Lang::createHandle($new['about']['name']));
			
			// Create new file:
			if (strpos(@$new['about']['html-formatter-file'], dirname(__FILE__)) === 0) {
				$rootdir = dirname(__FILE__);
			}
			
			else {
				$rootdir = WORKSPACE;
			}
			
			$filemode = $this->_Parent->Configuration->get('write_mode', 'file');
			$filename = sprintf(
				'%s/text-formatters/formatter.%s.php',
				$rootdir, $name
			);
			$dirmode = $this->_Parent->Configuration->get('write_mode', 'directory');
			$dirname = dirname($filename);
			
			// Make sure the directory exists:
			if (!is_dir($dirname)) {
				General::realiseDirectory($dirname, $dirmode);
			}
			
			// Make sure new file can be written:
			if (!is_writable($dirname) or (file_exists($filename) and !is_writable($filename))) {
				$error = __('Cannot save formatter, path is not writable.');
				return false;
			}
			
			$filedata = sprintf(
				$template,
				
				// Class name:
				str_replace(
					' ', '',
					ucwords(
						str_replace('-', ' ', Lang::createHandle($new['about']['name']))
					)
				),
				
				// Name:
				var_export($new['about']['name'], true),
				
				// Author:
				var_export($new['about']['author']['name'], true),
				
				// Website:
				var_export($new['about']['author']['website'], true),
				
				// Email:
				var_export($new['about']['author']['email'], true),
				
				// Description:
				var_export($new['about']['description'], true),
				
				// Dates:
				var_export($new['about']['html-formatter-created'], true),
				var_export($new['about']['html-formatter-updated'], true),
				
				// Options:
				var_export($new['options']['pretty_acronyms'], true),
				var_export($new['options']['pretty_ampersands'], true),
				var_export($new['options']['pretty_dashes'], true),
				var_export($new['options']['pretty_ellipses'], true),
				var_export($new['options']['pretty_quotation_marks'], true),
				var_export($new['options']['pretty_sentence_spacing'], true),
				var_export($new['options']['pretty_symbols'], true),
				var_export($new['options']['prevent_widowed_words'], true),
				var_export($new['options']['editor_name'], true)
			);
			
			// Write file to disk:
			General::writeFile($filename, $filedata, $filemode);
			
			// Cleanup old file:
			if (
				$filename != @$old['about']['html-formatter-file']
				and file_exists($filename) and @file_exists($old['about']['html-formatter-file'])
			) {
				General::deleteFile($old['about']['html-formatter-file']);
			}
			
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Formatting:
	-------------------------------------------------------------------------*/
		
		public function format($source, $options) {
			$options = (object)array_merge(
				array(
					'pretty-acronyms'			=> false,
					'pretty_ampersands'			=> false,
					'pretty_quotation_marks'	=> false,
					'pretty_dashes'				=> false,
					'pretty_ellipses'			=> false,
					'pretty_sentence_spacing'	=> false,
					'pretty_symbols'			=> false,
					'prevent_widowed_words'		=> false
				),
				$options
			);
			
			// Switch tabs for space:
			$this->reindent($source);
			
			// Tidy up input:
			$this->prepare($source);
			
			// Wrap stray text with paragraphs:
			$this->wrap($source);
			
			// Tidy again:
			$this->cleanup($source);
			
			// Make it pretty?
			$this->pretty($source, $options);
			
			$source = trim(preg_replace(
				'/^<body>|<\/body>$/i', '', $source
			));
			
			//header('content-type: text/plain'); var_dump($source); exit;
			
			return $source;
		}
		
		protected function pretty(&$source, $options) {
			$document = new DOMDocument('1.0', 'UTF-8');
			$document->loadXML($source);
			$xpath = new DOMXPath($document);
			$nodes = array();
			$results = $xpath->query('//address | //caption | //td | //th | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //li | //dt | //dd | //p');
			
			// Find nodes that may contain prettyable bits:
			foreach ($results as $node) {
				array_unshift($nodes, $node);
			}
			
			// Loop through the nodes, now in reverse order:
			foreach ($nodes as $node) {
				$search = $replace = array();
				$content = '';
				
				// Find content:
				while ($node->hasChildNodes()) {
					$content .= $document->saveXML($node->firstChild);
					$node->removeChild($node->firstChild);
				}
				
				// Make quotation marks pretty:
				if ($options->pretty_quotation_marks) {
					$search = array_merge(
						$search,
						array(
							'/(\w)\'(\w)|(\s)\'(\d+\w?)\b(?!\')/',				// apostrophe's
							'/(\S)\'(?=\s|[[:punct:]]|<|$)/',					// single closing
							'/\'/',												// single opening
							'/(\S)\"(?=\s|[[:punct:]]|<|$)/',					// double closing
							'/"/',												// double opening
						)
					);
					$replace = array_merge(
						$replace,
						array(
							'\1&#8217;\2',										// apostrophe's
							'\1&#8217;',										// single closing
							'&#8216;',											// single opening
							'\1&#8221;',										// double closing
							'&#8220;',											// double opening
						)
					);
				}
				
				// Make sentences pretty:
				if ($options->pretty_sentence_spacing) {
					$search = array_merge(
						$search,
						array(
							'/([!?.])(?:[ ])/',
						)
					);
					$replace = array_merge(
						$replace,
						array(
							'\1&#160; ',
						)
					);
				}
				
				// Make acronyms pretty:
				if ($options->pretty_acronyms) {
					$search = array_merge(
						$search,
						array(
							'/\b([A-Z][A-Z0-9]{2,})\b(?:[(]([^)]*)[)])/',
						)
					);
					$replace = array_merge(
						$replace,
						array(
							'<acronym title="\2">\1</acronym>',
						)
					);
				}
				
				// Make ellipses pretty:
				if ($options->pretty_ellipses) {
					$search = array_merge(
						$search,
						array(
							'/\.{3}/',
						)
					);
					$replace = array_merge(
						$replace,
						array(
							'\1&#8230;',
						)
					);
				}
				
				// Make dashes pretty:
				if ($options->pretty_dashes) {
					$search = array_merge(
						$search,
						array(
							'/--/',												// em dash
							'/-/',												// en dash
						)
					);
					$replace = array_merge(
						$replace,
						array(
							'&#8212;',											// em dash
							'&#8211;',											// en dash
						)
					);
				}
				
				// Make symbols pretty:
				if ($options->pretty_symbols) {
					$search = array_merge(
						$search,
						array(
							'/(\d+)( ?)x( ?)(?=\d+)/',							// dimension sign
							'%(^|\s)\(tm\)($|\s)%i',							// trademark
							'%(^|\s)\(r\)($|\s)%i',								// registered
							'%(^|\s)\(c\)($|\s)%i'								// copyright
						)
					);
					$replace = array_merge(
						$replace,
						array(
							'\1\2&#215;\3',										// dimension sign
							'\1&#8482;\2',										// trademark
							'\1&#174;\2',										// registered
							'\1&#169;\2'										// copyright
						)
					);
				}
				
				if (!empty($search)) {
					$lines = preg_split("/(<.*>)/U", $content, -1, PREG_SPLIT_DELIM_CAPTURE);
					$content = ''; $apply = true;
					
					foreach ($lines as $line) {
						// Skip over code samples:
						if (preg_match('/^<(pre|code)/i', $line)) $apply = false;
						else if (preg_match('/$<\/(pre|code)>/i', $line)) $apply = true;
						
						if ($apply and !preg_match("/<.*>/", $line)) {
							$line = preg_replace($search, $replace, $line);
						}
						
						$content .= $line;
					}
				}
				
				// Prevent widows:
				if ($options->prevent_widowed_words) {
					$content = preg_replace(
						'/((^|\s)\S{0,20})\s(\S{0,20})$/',
						'\1&#160;\3', $content
					);
				}
				
				// Wrap dashes:
				if ($options->pretty_dashes) {
					$content = str_replace(
						array(
							'&#8212;',
							'&#8211;'
						),
						array(
							'<span class="dash em">&#8212;</span>',
							'<span class="dash en">&#8211;</span>'
						),
						$content
					);
				}
				
				// Wrap ampersands:
				if ($options->pretty_ampersands) {
					$content = preg_replace(
						'/&#38;|&amp;/i',
						'<span class="ampersand">&#38;</span>', $content
					);
				}
				
			    // Wrap quotation marks:
				if ($options->pretty_quotation_marks) {
					$content = str_replace(
						array(
					    	'&#8216;',
					    	'&#8217;',
					    	'&#8220;',
					    	'&#8221;',
					    	'&#171;',
					    	'&#187;'
						),
						array(
					    	'<span class="quote left single">&#8216;</span>',
					    	'<span class="quote right single">&#8217;</span>',
					    	'<span class="quote left double">&#8220;</span>',
					    	'<span class="quote right double">&#8221;</span>',
					    	'<span class="quote left angle">&#8221;</span>',
					    	'<span class="quote right angle">&#8221;</span>'
						),
						$content
					);
				}
				
				// Wrap ellipsis:
				if ($options->pretty_ellipses) {
					$content = str_replace(
						'&#8230;', '<span class="ellipsis">&#8230;</span>', $content
					);
				}
				
				// Replace content:
				$fragment = $document->createDocumentFragment();
				$fragment->appendXML($content);
				$node->appendChild($fragment);
			}
			
			$source = $document->saveXML($document->documentElement);
		}
		
		protected function reindent(&$source, $tabsize = 4) {
			if (!function_exists('__expander')) eval("
				function __expander(\$matches) {
					return \$matches[1] . str_repeat(
						' ', strlen(\$matches[2]) * {$tabsize} - (strlen(\$matches[1]) % {$tabsize})
					);
				}
			");
			
			while (strstr($source, "\t")) {
				$source = preg_replace_callback('%^([^\t\n]*)(\t+)%m', '__expander', $source);
			}
		}
		
		protected function prepare(&$source) {
			$substitute = "\x1b";
			
			// Remove or replace returns:
			$source = preg_replace("/[\r](?=\n)/", '', $source);
			$source = str_replace("\r", "\n", $source);
			
			// Replace newlines with a substitute character
			// so that we can reinstate them later:
			$source = str_replace("\n", $substitute, $source);
			
			$tidy = new Tidy();
			$tidy->parseString(
				$source, array(
					'drop-font-tags'				=> true,
					'drop-proprietary-attributes'	=> true,
					'hide-comments'					=> true,
					'numeric-entities'				=> true,
					'output-xhtml'					=> true,
					'wrap'							=> 0,
					
					// Stuff to get rid of awful word this:
					'bare'							=> true,
					'word-2000'						=> true,
					
					// HTML5 Elements:
					'new-blocklevel-tags'			=> 'section nav article aside hgroup header footer figure figcaption ruby video audio canvas details datagrid summary menu',
					'new-inline-tags'				=> 'time mark rt rp output progress meter',
					'new-empty-tags'				=> 'wbr source keygen command'
				), 'utf8'
			);
			
			$source = $tidy->body()->value;
			
			// Reinstate newlines:
			$source = str_replace($substitute, "\n", $source);
		}
		
		protected function cleanup(&$source) {
			$tidy = new Tidy();
			$tidy->parseString(
				$source, array(
					'drop-font-tags'				=> true,
					'drop-proprietary-attributes'	=> true,
					'hide-comments'					=> true,
					'numeric-entities'				=> true,
					'output-xhtml'					=> true,
					'wrap'							=> 0,
					
					// Stuff to get rid of awful word this:
					'bare'							=> true,
					'word-2000'						=> true,
					
					// HTML5 Elements:
					'new-blocklevel-tags'			=> 'section nav article aside hgroup header footer figure figcaption ruby video audio canvas details datagrid summary menu',
					'new-inline-tags'				=> 'time mark rt rp output progress meter',
					'new-empty-tags'				=> 'wbr source keygen command'
				), 'utf8'
			);
			
			$source = $tidy->body()->value;
		}
		
		protected function wrap(&$source) {
			$document = new DOMDocument('1.0', 'UTF-8');
			$document->loadXML($source);
			$xpath = new DOMXPath($document);
			$nodes = array(); $breaks = array(
				'section', 'article', 'aside', 'header', 'footer', 'nav',
				'dialog', 'figure', 'address', 'p', 'hr', 'br', 'pre',
				'blockquote', 'ol', 'ul', 'li', 'dl', 'dt', 'dd', 'img',
				'iframe', 'embed', 'object', 'param', 'video', 'audio',
				'source', 'canvas', 'map', 'area', 'table', 'caption',
				'colgroup', 'col', 'tbody', 'thead', 'tfoot', 'tr', 'td',
				'th', 'form', 'fieldset', 'label', 'input', 'button',
				'select', 'datalist', 'optgroup', 'option', 'textarea',
				'keygen', 'output', 'details', 'datagrid', 'command',
				'bb', 'menu', 'legend', 'div'
			);
			
			// Find nodes that may contain paragraphs:
			foreach ($xpath->query('//body | //blockquote | //div | //header | //footer | //aside || //article | //section') as $node) {
				array_unshift($nodes, $node);
			}
			
			// Loop through the nodes, now in reverse order:
			foreach ($nodes as $node) {
				$default = array(
					'type'	=> 'inline',
					'value'	=> ''
				);
				$groups = array($default);
				$content = '';
				
				// Group text between paragraph breaks:
				foreach ($node->childNodes as $child) {
					if (in_array($child->nodeName, $breaks)) {
						array_push($groups, 
							array(
								'type'	=> 'break',
								'value'	=> $document->saveXML($child)
							)
						);
						
						array_push($groups, $default);
					}
					
					else {
						$current = array_pop($groups);
						$current['value'] .= $document->saveXML($child);
						array_push($groups, $current);
					}
				}
				
				// Join together again:
				foreach ($groups as $current) {
					if ($current['type'] == 'break') {
						$content .= $current['value'];
					}
					
					else if (trim($current['value'])) {
						$value = preg_replace('/((\r\n|\n)\s*){2,}/', "</p><p>", trim($current['value']));
						$value = preg_replace('/[\r\n\t](?<=\S)/', '<br />', $value);
						$value = preg_replace('/\s{2,}/', ' ', $value);
						
						$content .= "<p>$value</p>";
					}
				}
				
				// Remove children:
				while ($node->hasChildNodes()) {
					$node->removeChild($node->firstChild);
				}
				
				// Replace content:
				if ($content) {
					try {
						$fragment = $document->createDocumentFragment();
						$fragment->appendXML($content);
						$node->appendChild($fragment);
					}
					
					catch (Exception $e) {
						// Ignore...
					}
				}
			}
			
			$source = $document->saveXML($document->documentElement);
		}
	}
	
/*---------------------------------------------------------------------------*/
?>