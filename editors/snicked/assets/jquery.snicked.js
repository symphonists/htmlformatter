/*-----------------------------------------------------------------------------
	Snicked Editor jQuery Plugin
-----------------------------------------------------------------------------*/
	
	jQuery.fn.snicked = function(language_source) {
		var editors = this.snickedCore();
		var autocomplete = {};
		var indentation = [];
		
		if (language_source) jQuery.getJSON(language_source, function(data) {
			autocomplete = data.autocomplete;
			indentation = data.indentation;
		});
		
	/*-------------------------------------------------------------------------
		Autocomplete
	-------------------------------------------------------------------------*/
		
		(function() {
			var states = {
				rule:		null,
				suggestion:	null
			};
			var methods = {
				next: function(editor, selection) {
					var position = states.rule.points.shift();
					var length = editor.val().length;
					
					states.rule.offset += (length - states.rule.length);
					states.rule.length = length;
					
					selection.start = position + states.rule.offset;
					selection.end = selection.start;
					
					editor.setSelection(selection);
					
					if (states.rule.points.length == 0) {
						states.rule = null;
						
						return true;
					}
					
					return true;
				},
				
				// Start using a rule:
				start: function(editor, selection, before, after, rule) {
					var snippet = rule.snippet, point = null;
					var indent = editor.getIndentation();
					var lines = snippet.split("\n");
					var captures = before.match(rule.before).concat(after.match(rule.after));
					
					// Remove matched parts:
					editor.setBefore(selection, before.replace(rule.before, ''));
					editor.setAfter(selection, after.replace(rule.after, ''));
					
					before = editor.getBefore(selection);
					after = editor.getAfter(selection);
					
					// Reindent rule:
					lines = jQuery(lines).map(function(index, line) {
						if (!index) return line;
						
						return indent + line;
					});
					
					snippet = lines.get().join("\n");
					
					// Set current rule:
					states.rule = {
						length:		0,
						offset:		0,
						selection:	selection,
						points:		[]
					};
					
					// Find all capture points:
					while ((point = snippet.match(/\{#([0-9]+)\}/))) {
						var index = parseInt(point[1]);
						
						if (captures[index]) {
							snippet = snippet.replace(point[0], captures[index]);
						}
						
						else {
							snippet = snippet.replace(point[0], '');
						}
					}
					
					// Find all cursor points:
					while ((point = snippet.match(/\{\$[0-9]+\}/))) {
						states.rule.points.push(
							snippet.indexOf(point[0])
							+ before.length
						);
						snippet = snippet.replace(point[0], '');
					}
					
					// No jump points:
					if (!states.rule.points.length) {
						before += snippet;
						states.rule = null;
						editor.setBefore(selection, before);
						
						return true;
					}
					
					editor.insertBefore(selection, snippet);
					
					states.rule.length = editor.val().length;
					states.rule.position = before.length;
					
					methods.next(editor, selection);
					
					return true;
				},
				
				// Redraw suggestions:
				redraw: function(editor, before) {
					var word = before.match(/\b[a-z0-9:_]+$/i).pop();
					var rules = [];
					
					jQuery(autocomplete.rules).each(function(index, rule) {
						if (rule.label && rule.label.indexOf(word) === 0) {
							rules.push(rule);
						}
					});
					
					if (rules.length) {
						var list = jQuery('<ol class="snicked-suggestions" />');
						
						if (states.suggestion == null) {
							states.suggestion = {};
						}
						
						if (states.suggestion.index < 0) {
							states.suggestion.index = rules.length - 1;
						}
						
						else if (states.suggestion.index >= rules.length) {
							states.suggestion.index = 0;
						}
						
						else if (states.suggestion.index == undefined) {
							states.suggestion.index = 0;
						}
						
						jQuery(rules).each(function(index, rule) {
							var item = jQuery('<li />')
								.text(rule.label)
								.appendTo(list);
							
							if (states.suggestion.index === index) {
								states.suggestion.rule = rule;
								item.addClass('current');
							}
						});
						
						list.insertAfter(editor);
					}
				}
			};
			
			editors.addKeyHandler(function(editor, key, event) {
				var selection = editor.getSelection();
				var before = editor.getBefore(selection);
				
				jQuery('.snicked-suggestions').remove();
				
				// Choose:
				if (states.suggestion != null && event.altKey && (key == 37 || key == 39)) {
					if (key == 37) states.suggestion.index -= 1;
					if (key == 39) states.suggestion.index += 1;
				}
				
				// Apply:
				else if (states.suggestion != null && key == 9) {
					var after = editor.getAfter(selection);
					var before = before.replace(/\b[a-z0-9:_]+$/i, '');
					var rule = states.suggestion.rule;
					
					return methods.start(editor, selection, before, after, rule);
				}
				
				// Redraw:
				if (!states.rule && /\b[a-z0-9:_]+$/i.test(before)) {
					methods.redraw(editor, before);
				}
				
				// Reset:
				else {
					states.suggestion = null;
				}
				
				// Find rule:
				if (!states.rule && autocomplete.keys[key]) {
					var trigger = autocomplete.keys[key];
					var after = editor.getAfter(selection);
					var completed = false;
					
					jQuery(autocomplete.rules).each(function(index, rule) {
						if (rule.key != trigger) return true;
						
						if (!rule.before) rule.before = /$/;
						if (!rule.after) rule.after = /^/;
						
						if (!rule.before.test(before) || !rule.after.test(after)) {
							return true;
						}
						
						completed = methods.start(editor, selection, before, after, rule);
						
						return false;
					});
					
					return completed;
				}
				
				// Work on current rule:
				if (states.rule && key == 9) {
					return methods.next(editor, selection);
				}
				
				return false;
			});
		})();
		
	/*-------------------------------------------------------------------------
		Autoindent
	-------------------------------------------------------------------------*/
		
		(function() {
			editors.addKeyHandler(function(editor, key) {
				var selection = editor.getSelection();
				
				if (key == 9) {
					editor.insertBefore(selection, "\t");
					
					return true;
				}
				
				else if (key == 13) {
					var after = editor.selection.getFollowingText();
					var before = editor.selection.getPrecedingText();
					var indent = editor.getIndentation();
					
					/*
					jQuery(indentationRules).each(function(index, rule) {
						var matched = false;
						
						if (rule.matchBefore && rule.matchAfter) {
							matched = rule.matchBefore.test(before) && rule.matchAfter.test(after);
						}
						
						else if (rule.matchBefore) {
							matched = rule.matchBefore.test(before);
						}
						
						else if (rule.matchAfter) {
							matched = rule.matchAfter.test(after);
						}
						
						if (matched) {
							if (rule.indentLevel == 1) {
								indent += "\t";
							}
							
							else if (rule.indentLevel == -1) {
								indent = indent.replace('\t', '');
							}
							
							return false;
						}
					});
					*/
					
					editor.insertBefore(selection, "\n" + indent);
					
					return true;
				}
				
				return false;
			});
		})();
		
		return editors;
	};
	
/*---------------------------------------------------------------------------*/
