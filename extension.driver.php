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
			// Tidy up input:
			$this->prepare($source);
			
			// Wrap stray text with paragraphs:
			$this->wrap($source);
			
			// Tidy again:
			$this->cleanup($source);
			
			// Make it pretty?
			if ($pretty) $this->pretty($source);
			
			return $source;
		}
		
		protected function pretty(&$source) {
			header('content-type: text/plain');
			
			// Replace characters with special meaning:
			$search = array(
				'/(\w)\'(\w)|(\s)\'(\d+\w?)\b(?!\')/',				// apostrophe's
				'/(\S)\'(?=\s|[[:punct:]]|<|$)/',					// single closing
				'/\'/',												// single opening
				'/(\S)\"(?=\s|[[:punct:]]|<|$)/',					// double closing
				'/"/',												// double opening
				'/\b([A-Z][A-Z0-9]{2,})\b(?:[(]([^)]*)[)])/',		// 3+ acronym
				'/\b( )?\.{3}/',									// ellipsis
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
			
			$lines = preg_split("/(<.*>)/U", $source, -1, PREG_SPLIT_DELIM_CAPTURE);
			$source = '';
			
			foreach ($lines as $line) {
				if (!preg_match("/<.*>/", $line)) {
					$line = preg_replace($search, $replace, $line);
				}
				
				$source .= $line;
			}
			
			// Prevent widows by inserting non breaking spaces:
			$source = preg_replace(
				'/([^\s])\s+(((<(a|span|i|b|em|strong|acronym|caps|sub|sup|abbr|big|small|code|cite|tt)[^>]*>)*\s*[^\s<>]+)(<\/(a|span|i|b|em|strong|acronym|caps|sub|sup|abbr|big|small|code|cite|tt)>)*[^\s<>]*\s*(<\/(p|h[1-6]|li)>|$))/i',
				'\\1&#160;\\2', $source
			);
			
			// Wrap dashes:
			$source = str_replace(
				array(
					'&#8212;',
					'&#8211;'
				),
				array(
					'<span class="dash em">&#8212;</span>',
					'<span class="dash en">&#8211;</span>'
				),
				$source
			);
			
			// Wrap amptersands:
			$source = preg_replace(
				'/&#38;|&amp;/i',
				'<span class="ampersand">&#38;</span>', $source
			);
			
		    // Wrap quotation marks:
			$source = str_replace(
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
				$source
			);
			
			// Wrap ellipsis:
			$source = str_replace(
				'&#8230;', '<span class="ellipsis">&#8230;</span>', $source
			);
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
			$breaks = array(
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
			foreach ($xpath->query('//body') as $node) {
				$default = array(
					'type'	=> 'inline',
					'value'	=> ''
				);
				$groups = array($default);
				$output = '';
				
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
						$output .= $current['value'];
					}
					
					else if (trim($current['value'])) {
						$value = preg_replace('/((\r\n|\n)\s*){2,}/', "</p><p>", trim($current['value']));
						$value = preg_replace('/[\r\n\t]/', '<br />', $value);
						$value = preg_replace('/\s{2,}/', ' ', $value);
						
						$output .= "<p>$value</p>";
					}
				}
				
				// Replace $node with $output:
				$parent = $node->parentNode;
				$fragment = $document->createDocumentFragment();
				$fragment->appendXML($output);
				$parent->replaceChild($fragment, $node);
			}
			
			$source = $document->saveXML();
		}
	}
	
?>
