<?php

use Sharky\Joomla\PluginBuildScript\Script;

require __DIR__ . '/vendor/autoload.php';

(
	new Script(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'menus',
		'search',
		'plg_search_menus',
		'SharkyKZ',
		'Search - Menu Items',
		'Plugin for searching menu items in frontend.',
		'(5\.|4\.|3\.([89]|10))',
		'5.3.10',
	)
)->build();
