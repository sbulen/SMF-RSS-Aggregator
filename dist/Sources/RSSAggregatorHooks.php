<?php
/**
 *	Logic for the RSS Aggregator mod hooks.
 *
 *	Copyright 2024 Shawn Bulen
 *
 *	The RSS Aggregator is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *	
 *	This software is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this software.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// If we are outside SMF throw an error.
if (!defined('SMF')) {
    die('Hacking attempt...');
}

/**
 *
 * Hook function - Add admin menu functions.
 *
 * Hook: integrate_admin_areas
 *
 * @param array $menu
 *
 * @return null
 *
 */
function rss_aggregator_admin_menu(&$menu)
{
	global $txt;

	loadLanguage('RSSAggregator');

	$title = $txt['rssagg_title'];

	// Add to the main menu
	$menu['layout']['areas']['rssagg'] = array(
		'label' => $title,
		'file' => 'RSSAggregator.php',
		'function' => 'maintain_feeds',
		'icon' => 'news',
		'permission' => 'admin_forum',
		'subsections' => array(
			'browserss' => array($txt['rssagg_browse']),
		    'addrss' => array($txt['rssagg_addrss']),
		),
	);
}

/**
 *
 * Hook function - Remove guid for deleted message.
 * Soft delete, no error if not found.
 *
 * Hook: integrate_remove_message
 *
 * @param int $id_msg
 * @param array $message_info
 * @param bool $recycle
 *
 * @return null
 *
 */
function rss_aggregator_remove_message($id_msg, $message_info, $recycle)
{
	global $sourcedir;

	require_once($sourcedir . '/RSSAggregatorModel.php');
	guid_delete_message($id_msg);
}

/**
 *
 * Hook function - Remove guid for deleted topic.
 * Soft delete, no error if not found.
 * Need to use the ***before*** hook, otherwise topic is gone & can no longer find msg...
 *
 * Hook: integrate_remove_topics_before
 *
 * @param array $topics
 *
 * @return null
 *
 */
function rss_aggregator_remove_topics_before($topics)
{
	global $sourcedir;

	require_once($sourcedir . '/RSSAggregatorModel.php');
	guid_delete_topics($topics);
}
