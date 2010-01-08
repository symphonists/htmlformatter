{
	autocomplete:	{
		keys: {
			9:		'tab',
			13:		'enter',
			62:		'tagit'
		},
		rules:	[
			// HTML List:
			{
				key:			'tab',
				before:			/<(ol|ul)[^<>]*?>$/,
				snippet:		'{#0}\n\t<li>{$0}</li>\n</{#1}>'
			},
			{
				key:			'tagit',
				before:			/<(ol|ul)[^<>]*?$/,
				snippet:		'{#0}>\n\t<li>{$0}</li>\n</{#1}>'
			},
			{
				key:			'enter',
				before:			/\s*<li>\s*$/,
				after:			/^<\/li>/,
				snippet:		'{$0}'
			},
			{
				key:			'enter',
				before:			/<li>.*?$/,
				after:			/^<\/li>/,
				snippet:		'{#0}{#1}\n<li>{$0}</li>'
			},
			{
				key:			'enter',
				before:			/\s*<li>\s*<\/li>\s*$/,
				snippet:		'{$0}'
			},
			{
				key:			'enter',
				before:			/<\/li>\s*$/,
				snippet:		'{#0}\n<li>{$0}</li>'
			},
			
			// HTML Definition List:
			{
				key:			'tab',
				before:			/<dl[^>]*?>$/,
				snippet:		'{#0}\n\t<dt>{$0}</dt>\n</dl>'
			},
			{
				key:			'enter',
				before:			/\s*<dt>\s*$/,
				after:			/^<\/dt>/,
				snippet:		'{$0}'
			},
			{
				key:			'enter',
				before:			/<dt>.*?$/,
				after:			/^<\/dt>/,
				snippet:		'{#0}{#1}\n<dd>{$0}</dd>'
			},
			{
				key:			'enter',
				before:			/\s*<dt>\s*<\/dt>\s*$/,
				snippet:		'{$0}'
			},
			{
				key:			'enter',
				before:			/<\/dt>\s*$/,
				snippet:		'{#0}\n<dd>{$0}</dd>'
			},
			{
				key:			'enter',
				before:			/\s*<dd>\s*$/,
				after:			/^<\/dd>/,
				snippet:		'{$0}'
			},
			{
				key:			'enter',
				before:			/<dd>.*?$/,
				after:			/^<\/dd>/,
				snippet:		'{#0}{#1}\n<dt>{$0}</dt>'
			},
			{
				key:			'enter',
				before:			/\s*<dd>\s*<\/dd>\s*$/,
				snippet:		'{$0}'
			},
			{
				key:			'enter',
				before:			/<\/dd>\s*$/,
				snippet:		'{#0}\n<dt>{$0}</dt>'
			},
			
			// Any HTML end tag:
			{
				key:			'tab',
				before:			/<([a-z][a-z0-9]*)[^<>]*?>$/,
				snippet:		'{#0}{$0}</{#1}>'
			},
			{
				key:			'tab',
				before:			/<(a)$/,
				snippet:		'{#0} href="{$0}">{$1}</{#1}>'
			},
			{
				key:			'tagit',
				before:			/<(a)$/,
				snippet:		'{#0} href="{$0}">{$1}</{#1}>'
			},
			{
				key:			'tab',
				before:			/<([a-z][a-z0-9]*)$/,
				snippet:		'{#0}>{$0}</{#1}>'
			},
			{
				key:			'tagit',
				before:			/<([a-z][a-z0-9]*)$/,
				snippet:		'{#0}>{$0}</{#1}>'
			}
		]
	}
}