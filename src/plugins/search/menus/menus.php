<?php
/**
 * @copyright   (C) 2020 SharkyKZ
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or exit;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;

/**
 * Plugin for searching menu items in frontend.
 *
 * @since  1.0.0
 */
class PlgSearchMenus extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    \JDatabaseDriver
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * Array of supported search areas.
	 *
	 * @var    string[]
	 * @since  1.0.0
	 */
	private $searchAreas = ['menus' => 'PLG_SEARCH_MENUS_MENU_ITEMS'];

	/**
	 * Determine areas searchable by this plugin.
	 *
	 * @return  array  An array of search areas.
	 *
	 * @since   1.0.0
	 */
	public function onContentSearchAreas()
	{
		$this->loadLanguage();

		return $this->searchAreas;
	}

	/**
	 * Search menu items.
	 *
	 * The SQL must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav.
	 *
	 * @param   string  $text      Target search string.
	 * @param   string  $phrase    Matching option (possible values: exact|any|all).  Default is "any".
	 * @param   string  $ordering  Ordering option (possible values: newest|oldest|popular|alpha|category).  Default is "newest".
	 * @param   string  $areas     An array if the search is to be restricted to areas or null to search all areas.
	 *
	 * @return  stdClass[]  Search results.
	 *
	 * @since   1.0.0
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$text = trim($text);

		if ($text === '')
		{
			return [];
		}

		if (is_array($areas) && !array_intersect($areas, array_keys($this->searchAreas)))
		{
			return [];
		}

		$this->loadLanguage();

		$query = $this->db->getQuery(true);

		$section = $this->app->getLanguage()->_('PLG_SEARCH_MENUS_MENU_ITEMS');
		$limit   = $this->params->get('search_limit', 50);

		$text = $this->db->quote('%' . $text . '%');

		$query->select(
			[
				$this->db->quoteName('a.id'),
				$this->db->quoteName('a.link'),
				$this->db->quoteName('a.title'),
				$this->db->quoteName('a.browserNav'),
			]
		)
			->from($this->db->quoteName('#__menu', 'a'))
			->join('LEFT', $this->db->quoteName('#__extensions', 'e') . ' ON ' . $this->db->quoteName('a.component_id') . ' = ' . $this->db->quoteName('e.extension_id'))
			->where(
				[
					$this->db->quoteName('a.published') . ' = 1',
					$this->db->quoteName('a.client_id') . ' = 0',
					'(' . $this->db->quoteName('a.title') . ' LIKE ' . $text . ' OR ' . $this->db->quoteName('a.alias') . ' LIKE ' . $text . ')',
					'((' . $this->db->quoteName('a.type') . ' = ' . $this->db->quote('component') . ' AND ' . $this->db->quoteName('e.enabled') . ' = 1)'
					. ' OR ' . $this->db->quoteName('a.type') . ' = ' . $this->db->quote('alias')
					. 'OR ' . $this->db->quoteName('a.type') . ' = ' . $this->db->quote('url') . ')',
				]
			)
			->order($this->db->quoteName('a.title') . ' ASC')
			->setLimit($limit);

		// Filter by access level.
		if (!$this->params->get('show_unauthorised', 0))
		{
			$user = Factory::getUser();
			$query->where($this->db->quoteName('a.access') . ' IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
		}

		// Filter by language.
		if ($this->app->isClient('site') && $this->app->getLanguageFilter())
		{
			$query->where(
				'(' . $this->db->quoteName('a.language') . ' = ' . $this->db->quote($this->app->getLanguage()->getTag())
				. ' OR ' . $this->db->quoteName('a.language') . ' = ' . $this->db->quote('*') . ')'
			);
		}

		$this->db->setQuery($query);

		try
		{
			$items = $this->db->loadObjectList();
		}
		catch (RuntimeException $exception)
		{
			return [];
		}

		foreach ($items as $item)
		{
			$item->href = Route::_($item->link);
			$item->browsernav = $item->browserNav;
			$item->section = $section;
			$item->created = '';
			$item->text = '';
		}

		return $items;
	}
}
