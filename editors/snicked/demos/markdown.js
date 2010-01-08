{
	// Autocompletion:
	autocomplete:	{
		keys: {
			9:		'tab',
			13:		'enter'
		},
		rules:	[
			// Headers:
			{
				key:			'enter',
				before:			/^([ \t>]*)(#+)\s*(.*)$/,
				snippet:		'{#0}\n{#1}\n{#1}'
			},
			
			// Blockquote:
			{
				key:			'enter',
				before:			/\s*>\s*$/,
				snippet:		'\n\n{$0}'
			},
			{
				key:			'enter',
				before:			/\s*(>)\s*(.*?)$/,
				snippet:		'\n{#1} {#2}\n{#1} {$0}'
			},
			
			// Unordered list:
			{
				key:			'enter',
				before:			/\s*[*-]\s*$/,
				snippet:		'\n\n{$0}'
			},
			{
				key:			'enter',
				before:			/\s*([*-])\s*(.*?)$/,
				snippet:		'\n{#1} {#2}\n{#1} {$0}'
			}
		]
	}
}
