<?php
	
	class FormatterHtmlPretty extends TextFormatter {
		public function about() {
			return array(
				'name'						=> 'HTML Pretty',
				'author'					=> array(
					'name'						=> 'Rowan Lewis',
					'website'					=> 'http://pixelcarnage.com/',
					'email'						=> 'rowan@pixelcarnage.com'
				),
				'description'				=> 'Make your HTML pretty and clean',
				'html-formatter-editable'	=> true,
				'html-formatter-file'		=> __FILE__,
				'html-formatter-created'	=> '2009-12-01T14:00:00+00:00',
				'html-formatter-updated'	=> '2009-12-13T01:23:14+00:00'
			);
		}
		
		public function options() {
			return array(
				'pretty_acronyms'			=> true,
				'pretty_ampersands'			=> true,
				'pretty_dashes'				=> true,
				'pretty_ellipses'			=> true,
				'pretty_quotation_marks'	=> true,
				'pretty_sentence_spacing'	=> true,
				'pretty_symbols'			=> true,
				'prevent_widowed_words'		=> true,
				'editor_name'				=> 'none'
			);
		}
		
		public function run($source) {
			$driver = $this->_Parent->ExtensionManager->create('htmlformatter');
			
			return $driver->format($source, $this->options());
		}
	}
	
?>
