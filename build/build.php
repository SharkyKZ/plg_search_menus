<?php

require dirname(__DIR__) . '/build-script/script.php';

(
	new PluginBuildScript(
		'menus',
		'search',
		'plg_search_menus',
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'Search - Menu Items',
		'Plugin for searching menu items in frontend.',
		'(4\.|3\.([89]|10))',
		'5.3.10',
		'SharkyKZ',
	)
)->build();
