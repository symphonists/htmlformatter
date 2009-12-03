<?php
	
	class FormatterHtmlNormal extends TextFormatter {
		public function about() {
			return array(
				'name'						=> 'HTML Normal',
				'author'					=> array(
					'name'						=> 'Rowan Lewis',
					'website'					=> 'http://pixelcarnage.com/',
					'email'						=> 'rowan@pixelcarnage.com'
				),
				'description'				=> 'Make your HTML clean',
				'html-formatter-editable'	=> true,
				'html-formatter-file'		=> __FILE__,
				'html-formatter-created'	=> '2009-12-01T14:00:00+00:00',
				'html-formatter-updated'	=> '2009-12-03T22:02:05+00:00'
			);
		}
		
		public function options() {
			return array(
				'pretty_acronyms' => false,
				'pretty_ampersands' => false,
				'pretty_dashes' => false,
				'pretty_ellipses' => false,
				'pretty_quotation_marks' => false,
				'pretty_sentence_spacing' => false,
				'pretty_symbols' => false,
				'prevent_widowed_words' => false
			);
		}
		
		public function run($source) {
			$driver = $this->_Parent->ExtensionManager->create('htmlformatter');
			
			return $driver->format($source, $this->options());
		}
	}
	
?>
