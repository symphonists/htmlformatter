<?php
	
	class Formatter%s extends TextFormatter {
		public function about() {
			return array(
				'name'						=> %s,
				'author'					=> array(
					'name'						=> %s,
					'website'					=> %s,
					'email'						=> %s
				),
				'description'				=> %s,
				'html-formatter-editable'	=> true,
				'html-formatter-file'		=> __FILE__,
				'html-formatter-created'	=> %s,
				'html-formatter-updated'	=> %s
			);
		}
		
		public function options() {
			return array(
				'pretty_acronyms' => %s,
				'pretty_ampersands' => %s,
				'pretty_dashes' => %s,
				'pretty_ellipses' => %s,
				'pretty_quotation_marks' => %s,
				'pretty_sentence_spacing' => %s,
				'pretty_symbols' => %s,
				'prevent_widowed_words' => %s
			);
		}
		
		public function run($source) {
			$driver = $this->_Parent->ExtensionManager->create('htmlformatter');
			
			return $driver->format($source, $this->options());
		}
	}
	
?>
