jQuery(document).ready(function () {
	var root = Symphony.WEBSITE + '/extensions/htmlformatter';
	
	jQuery.each(HTMLFormatterEditors, function(formatter, editor) {
		if (editor == 'ckeditor') {
			jQuery('textarea.' + formatter).each(function(index) {
		        CKEDITOR.replace(
		        	jQuery(this).attr('name'),
		        	{
			            height:					this.offsetHeight,
			            extraPlugins:			'uicolor,xmlentities',
			            removePlugins:			'font,entities,resize',
			            startupOutlineBlocks:	true,
			            replaceByClassEnabled:	false,
			            xmlentities:			false,
			            toolbar:				[
							['Format'],
							['Bold','Italic','Strike','-','Subscript','Superscript'],
							['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
							['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
							['Link','Unlink','Anchor'],
							'/',
							['Image','Table','HorizontalRule','SpecialChar'],
							['PasteText','PasteFromWord','RemoveFormat'],
							['Source','Maximize', 'ShowBlocks','-','About']
			            ]
			        }
		        );
		    });
		}
		
		else if (editor == 'jwysiwyg') {
			jQuery('textarea.' + formatter).wysiwyg();
		}
		
		else if (editor == 'snicked') {
			jQuery('textarea.' + formatter).snicked(root + '/editors/snicked/demos/html.js');
		}
	});
});