<?php
	
	class Extension_HTMLFormatter extends Extension {
		public function about() {
			return array(
				'name'			=> 'HTML Formatter',
				'version'		=> '2.0.1',
				'release-date'	=> '2009-06-26',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description'	=> 'Allows HTML to be used as input, safely.'
			);
		}
		
		public function format($source, $pretty = false) {
			header('content-type: text/plain');
			
			// Switch tabs for space:
			$this->reindent($source);
			
			// Tidy up input:
			$this->prepare($source);
			
			// Wrap stray text with paragraphs:
			$this->wrap($source);
			
			// Tidy again:
			$this->cleanup($source);
			
			// Make it pretty?
			if ($pretty) $this->pretty($source);
			
			$source = preg_replace(
				'/^<body>|<\/body>$/i', '', $source
			);
			
			$source = trim($source);
			
			//var_dump($source); exit;
			
			return $source;
		}
		
		protected function pretty(&$source) {
			$document = new DOMDocument('1.0', 'UTF-8');
			$document->loadXML($source);
			$xpath = new DOMXPath($document);
			$nodes = array();
			$results = $xpath->query('//dd | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //li | //p');
			
			// Find nodes that may contain prettyable bits:
			foreach ($results as $node) {
				array_unshift($nodes, $node);
			}
			
			// Loop through the nodes, now in reverse order:
			foreach ($nodes as $node) {
				$content = '';
				
				// Find content:
				while ($node->hasChildNodes()) {
					$content .= $document->saveXML($node->firstChild);
					$node->removeChild($node->firstChild);
				}
				
				// Replace characters with special meaning:
				$search = array(
					'/(\w)\'(\w)|(\s)\'(\d+\w?)\b(?!\')/',				// apostrophe's
					'/(\S)\'(?=\s|[[:punct:]]|<|$)/',					// single closing
					'/\'/',												// single opening
					'/(\S)\"(?=\s|[[:punct:]]|<|$)/',					// double closing
					'/"/',												// double opening
					'/\b([A-Z][A-Z0-9]{2,})\b(?:[(]([^)]*)[)])/',		// 3+ acronym
					'/\.{3}/',											// ellipsis
					'/--/',												// em dash
					'/-/',												// en dash
					'/(\d+)( ?)x( ?)(?=\d+)/',							// dimension sign
					'/\b ?[([]TM[])]/i',								// trademark
					'/\b ?[([]R[])]/i',									// registered
					'/\b ?[([]C[])]/i'									// copyright
				);
				
				$replace = array(
					'\1&#8217;\2',										// apostrophe's
					'\1&#8217;',										// single closing
					'&#8216;',											// single opening
					'\1&#8221;',										// double closing
					'&#8220;',											// double opening
					'<acronym title="\2">\1</acronym>',					// 3+ uppercase acronym
					'\1&#8230;',										// ellipsis
					'&#8212;',											// em dash
					'&#8211;',											// en dash
					'\1\2&#215;\3',										// dimension sign
					'&#174;',											// trademark
					'&#174;',											// registered
					'&#169;'											// copyright
				);
				
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
				
				// Prevent widows:
				$content = preg_replace(
					'/((^|\s)\S{0,20})\s(\S{0,20})$/',
					'\1&#160;\3', $content
				);
				
				// Wrap dashes:
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
				
				// Wrap amptersands:
				$content = preg_replace(
					'/&#38;|&amp;/i',
					'<span class="ampersand">&#38;</span>', $content
				);
				
			    // Wrap quotation marks:
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
				
				// Wrap ellipsis:
				$content = str_replace(
					'&#8230;', '<span class="ellipsis">&#8230;</span>', $content
				);
				
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
					'wrap'							=> 0
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
					'wrap'							=> 0
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
			foreach ($xpath->query('//body | //blockquote | //div') as $node) {
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
						$value = preg_replace('/[\r\n\t]/', '<br />', $value);
						$value = preg_replace('/\s{2,}/', ' ', $value);
						
						$content .= "<p>$value</p>";
					}
				}
				
				// Remove children:
				while ($node->hasChildNodes()) {
					$node->removeChild($node->firstChild);
				}
				
				// Replace content:
				$fragment = $document->createDocumentFragment();
				$fragment->appendXML($content);
				$node->appendChild($fragment);
			}
			
			$source = $document->saveXML($document->documentElement);
		}
	}
	
?>
