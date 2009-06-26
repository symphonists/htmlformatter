<?php
	
	class FormatterHTMLNormal extends TextFormatter {
		function about() {
			return array(
				'name'			=> 'HTML Normal',
				'version'		=> '1.0.1',
				'release-date'	=> '2009-06-26',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description'	=> 'Sanitise HTML input.'
			);
		}
		
		function run($source) {
			return $this->_Parent->ExtensionManager->create('htmlformatter')->format($source, false);
		}
	}
	
?>
