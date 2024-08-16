<?php
/**
 *	Main logic for the RSS Aggregator mod for SMF..
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
 * browse_feeds - action.
 *
 * Primary action called from the admin menu for managing the RSS feeds.
 * Sets subactions & list columns & figures out if which subaction to call.
 *
 * Action: admin
 * Area: rssagg
 *
 * @return null
 *
 */
function maintain_feeds()
{
	global $txt, $context, $sourcedir;

	// You have to be able to moderate the forum to do this.
	isAllowedTo('admin_forum');

	// Stuff we'll need around...
	require_once($sourcedir . '/RSSAggregatorModel.php');
	loadLanguage('RSSAggregator');
	loadCSSFile('rssaggregator.css', array('force_current' => false, 'validate' => true, 'minimize' => true, 'order_pos' => 9500));

	// Sub actions...
	$subActions = array(
		'browserss' => 'show_feed_status',
		'addrss' => 'add_feed',
		'modrss' => 'mod_feed',
		'showrss' => 'show_feed_details',
	);

	// This uses admin tabs
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['rssagg_title'],
		'description' => $txt['rssagg_desc'],
	);

	// Pick the correct sub-action.
	if (isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]))
		$context['sub_action'] = $_REQUEST['sa'];
	else
		$context['sub_action'] = 'browserss';

	$_REQUEST['sa'] = $context['sub_action'];

	// Set the page title
	$context['page_title'] = $txt['rssagg_title'];

	// Finally fall through to what we are doing.
	call_helper($subActions[$context['sub_action']]);
}

/**
 * show_feed_status.
 *
 * Action: admin
 * Area: rssagg
 * Subaction: browserss
 *
 * @return null
 *
 */
function show_feed_status()
{
	global $txt, $context, $sourcedir, $scripturl, $modSettings;

	// You have to be able to moderate the forum to do this.
	isAllowedTo('admin_forum');

	// Set up some basics....
	$context['url_start'] = '?action=admin;area=rssagg;sa=browserss';
	$context['page_title'] = $txt['rssagg_browse'];

	// The number of entries to show per page.
	$context['displaypage'] = $modSettings['defaultMaxMembers'];

	// Handle deletion...
	if (!empty($_POST['remove']) && isset($_POST['selection']))
	{
		checkSession();
		validateToken('rssagg_maint', 'post');

		rss_feed_deletes(array_unique($_POST['selection']));
		guid_delete_channels(array_unique($_POST['selection']));
	}

	// Handle pauses...
	if (!empty($_POST['pause']) && isset($_POST['selection']))
	{
		checkSession();
		validateToken('rssagg_maint', 'post');

		toggle_pause_state(array_unique($_POST['selection']));
	}

	// Handle runs, submit a background task for each & request, & zero out last_proc_time...
	if (!empty($_POST['runnow']) && isset($_POST['selection']))
	{
		checkSession();
		validateToken('rssagg_maint', 'post');

		zero_last_proc_time(array_unique($_POST['selection']));

		$sched_info = array();
		$keyed_sched_info = array();

		// Need to be able to find it by channel...
		$sched_info = get_feed_sched_info();
		foreach ($sched_info as $info)
			$keyed_sched_info[$info['id_channel']] = $info;

		// This is where $this->_details are passed to background task.
		foreach ($_POST['selection'] as $chan)
			add_rssagg_background_task($keyed_sched_info[$chan]);
	}

	// This is all the information required for an rss channel listing.
	require_once($sourcedir . '/Subs-List.php');
	$listOptions = array(
		'id' => 'rss_feed_list',
		'title' => $txt['rssagg_browse'],
		'width' => '100%',
		'items_per_page' => $context['displaypage'],
		'no_items_label' => $txt['rssagg_no_entries_found'],
		'base_href' => $scripturl . $context['url_start'],
		'default_sort_col' => 'id_channel',

		'get_items' => array(
			'function' => 'get_feed_sched_info',
			'params' => array(),
		),
		'get_count' => array(
			'function' => 'get_feed_sched_count',
			'params' => array(),
		),

		'columns' => array(
			'id_channel' => array(
				'header' => array(
					'value' => $txt['rssagg_id'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'id_channel',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'id_channel',
					'reverse' => 'id_channel DESC',
				),
			),
			'smf_topic_prefix' => array(
				'header' => array(
					'value' => $txt['rssagg_prefix'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'smf_topic_prefix',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'smf_topic_prefix',
					'reverse' => 'smf_topic_prefix DESC',
				),
			),
			'smf_board' => array(
				'header' => array(
					'value' => $txt['rssagg_board'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'name',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'name',
					'reverse' => 'name DESC',
				),
			),
			'smf_id_member' => array(
				'header' => array(
					'value' => $txt['rssagg_member'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'real_name',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'real_name',
					'reverse' => 'real_name DESC',
				),
			),
			'smf_link' => array(
				'header' => array(
					'value' => $txt['rssagg_link'],
					'class' => 'lefttext',
				),
				'data' => array(
					'function' => function($rowData) use ($scripturl)
					{
						$link_link = sprintf('<a href="%1$s?action=admin;area=rssagg;sa=showrss;chan=%2$d" class="smalltext">%3$s</a>', $scripturl, $rowData['id_channel'], $rowData['smf_link']);

						return $link_link;
					},
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'smf_link',
					'reverse' => 'smf_link DESC',
				),
			),
			'smf_last_proc_time' => array(
				'header' => array(
					'value' => $txt['rssagg_last_proc'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'smf_last_proc_time_txt',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'smf_last_proc_time',
					'reverse' => 'smf_last_proc_time DESC',
				),
			),
			'smf_status' => array(
				'header' => array(
					'value' => $txt['rssagg_status'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'smf_status',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'smf_status',
					'reverse' => 'smf_status DESC',
				),
			),
			'smf_pause' => array(
				'header' => array(
					'value' => $txt['rssagg_pause'],
					'class' => 'lefttext',
				),
				'data' => array(
					'db' => 'smf_pause_txt',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'smf_pause',
					'reverse' => 'smf_pause DESC',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['modify'],
					'class' => 'lefttext',
				),
				'data' => array(
					'function' => function($rowData) use ($scripturl, $txt)
					{
						$link_link = sprintf('<a href="%1$s?action=admin;area=rssagg;sa=modrss;chan=%2$d" class="smalltext">%3$s</a>', $scripturl, $rowData['id_channel'], $txt['modify']);

						return $link_link;
					},
				),
			),
			'selection' => array(
				'header' => array(
					'value' => '<input type="checkbox" name="all" onclick="invertAll(this, this.form);">',
					'class' => 'centercol',
				),
				'data' => array(
					'function' => function($entry)
					{
						return '<input type="checkbox" name="selection[]" value="' . $entry['id_channel'] . '"' . '>';
					},
					'class' => 'centercol',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . $context['url_start'],
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				$context['session_var'] => $context['session_id'],
			),
			'token' => 'rssagg_maint',	
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '
					<input type="submit" name="remove" value="' . $txt['rssagg_remove'] . '" data-confirm="' . $txt['rssagg_remove_confirm'] . '" class="button you_sure">
					<input type="submit" name="pause" value="' . $txt['rssagg_pause_btn'] . '" data-confirm="' . $txt['rssagg_pause_btn_confirm'] . '" class="button you_sure">
					<input type="submit" name="runnow" value="' . $txt['rssagg_runnow'] . '" data-confirm="' . $txt['rssagg_runnow_confirm'] . '" class="button you_sure">',
				'class' => 'floatright',
			),
		),
	);

	createToken('rssagg_maint');

	// Create the feed list.
	createList($listOptions);

	// The sub_template is defined in GenericList.template.php, which was invoked
	// When createList() was called above.
	$context['sub_template'] = 'show_list';
	$context['default_list'] = 'rss_feed_list';
}

/**
 * add_feed.
 *
 * Action: admin
 * Area: rssagg
 * Subaction: addrss
 *
 * @return null
 *
 */
function add_feed()
{
	global $context, $smcFunc;

	// You have to be able to moderate the forum to do this.
	isAllowedTo('admin_forum');

	// Make sure the right person is putzing...
	if (!empty($_POST))
		checkSession('post');

	// Setup the template stuff we'll need.
	loadTemplate('RSSAggregatorMaint');

	// Are we adding/modifying one?
	if (!empty($_POST['add']))
	{
		validateToken('rssagg_add', 'post');

		// In case you need to come back after errors....
		$_SESSION['rssagg_feed_info']['smf_link'] = !empty($_POST['smf_link']) ? htmlspecialchars($_POST['smf_link']) : '';
		$_SESSION['rssagg_feed_info']['smf_board'] = !empty($_POST['smf_board']) ? htmlspecialchars($_POST['smf_board']) : '';
		$_SESSION['rssagg_feed_info']['smf_description'] = !empty($_POST['smf_description']) ? htmlspecialchars($_POST['smf_description']) : '';
		$_SESSION['rssagg_feed_info']['smf_topic_prefix'] = !empty($_POST['smf_topic_prefix']) ? htmlspecialchars($_POST['smf_topic_prefix']) : '';
		$_SESSION['rssagg_feed_info']['smf_id_member'] = !empty($_POST['smf_id_member']) ? htmlspecialchars($_POST['smf_id_member']) : '';

		// Validate the url
		$context['rssagg_feed_info']['smf_link'] = htmlspecialchars($_POST['smf_link']);
		$smf_link = validate_iri($_POST['smf_link']);
		if (!is_string($smf_link) || (strlen($smf_link) > 255))
			fatal_lang_error('rssagg_bad_link', false);

		// Validate the board
		$context['rssagg_feed_info']['smf_board'] = htmlspecialchars($_POST['smf_board']);
		$smf_board = htmlspecialchars($_POST['smf_board']);
		$smf_board_name = get_board_name((int) $smf_board);
		if (!is_numeric($smf_board) || $smf_board == 0 || empty($smf_board_name))
			fatal_lang_error('rssagg_bad_board', false);
		$context['rssagg_feed_info']['smf_board_name'] = $smf_board_name;

		// Validate the description
		$context['rssagg_feed_info']['smf_description'] = htmlspecialchars($_POST['smf_description']);
		$smf_description = htmlspecialchars($_POST['smf_description']);
		if (!is_string($smf_description) || (strlen($smf_description) > 255))
			fatal_lang_error('rssagg_bad_desc', false);

		// Validate the prefix
		$context['rssagg_feed_info']['smf_topic_prefix'] = htmlspecialchars($_POST['smf_topic_prefix']);
		$smf_topic_prefix = htmlspecialchars($_POST['smf_topic_prefix']);
		if (!is_string($smf_topic_prefix) || (mb_strlen($smf_topic_prefix) > 25))
			fatal_lang_error('rssagg_bad_prefix', false);

		// Validate the member
		$context['rssagg_feed_info']['smf_id_member'] = htmlspecialchars($_POST['smf_id_member']);
		$smf_id_member = htmlspecialchars($_POST['smf_id_member']);
		$smf_member_name = get_member_name((int) $smf_id_member);
		if (((int) $smf_id_member != 0) && empty($smf_member_name))
			fatal_lang_error('rssagg_bad_member', false);
		$context['rssagg_feed_info']['smf_member_name'] = $smf_member_name;

		add_rss_channel($smf_link, (int) $smf_board, $smf_description, $smf_topic_prefix, (int) $smf_id_member);

		unset($_SESSION['rssagg_feed_info']);

		// Show them what they've done, by going back to the browse form
		redirectexit('action=admin;area=rssagg;sa=browserss');
	}

	// In case we're resuming an edit...
	$context['rssagg_feed_info']['smf_link'] = !empty($_SESSION['rssagg_feed_info']['smf_link']) ? $_SESSION['rssagg_feed_info']['smf_link'] : '';
	$context['rssagg_feed_info']['smf_board'] = !empty($_SESSION['rssagg_feed_info']['smf_board']) ? $_SESSION['rssagg_feed_info']['smf_board'] : '';
	$context['rssagg_feed_info']['smf_description'] = !empty($_SESSION['rssagg_feed_info']['smf_description']) ? $_SESSION['rssagg_feed_info']['smf_description'] : '';
	$context['rssagg_feed_info']['smf_topic_prefix'] = !empty($_SESSION['rssagg_feed_info']['smf_topic_prefix']) ? $_SESSION['rssagg_feed_info']['smf_topic_prefix'] : '';
	$context['rssagg_feed_info']['smf_id_member'] = !empty($_SESSION['rssagg_feed_info']['smf_id_member']) ? $_SESSION['rssagg_feed_info']['smf_id_member'] : '';

	$context['rssagg_feed_info']['smf_board_name'] = !empty($_SESSION['rssagg_feed_info']['smf_board']) ? get_board_name($_SESSION['rssagg_feed_info']['smf_board']) : '';
	$context['rssagg_feed_info']['smf_member_name'] = !empty($_SESSION['rssagg_feed_info']['smf_id_member']) ? get_member_name($_SESSION['rssagg_feed_info']['smf_id_member']) : '';

	unset($_SESSION['rssagg_feed_info']);

	createToken('rssagg_add');

	$context['sub_template'] = 'add_feed';
}

/**
 * mod_feed.
 *
 * Action: admin
 * Area: rssagg
 * Subaction: modrss
 *
 * @return null
 *
 */
function mod_feed()
{
	global $context;

	// You have to be able to moderate the forum to do this.
	isAllowedTo('admin_forum');

	// Make sure the right person is putzing...
	if (!empty($_POST))
		checkSession('post');

	// Setup the template stuff we'll need.
	loadTemplate('RSSAggregatorMaint');

	if (!empty($_POST['chan']))
		$chan = (int) $_POST['chan'];
	else
		$chan = (int) $_GET['chan'];

	$context['rssagg_feed_info'] = get_feed_info($chan);

	// Are we adding/modifying one?
	if (!empty($_POST['mod']))
	{
		validateToken('rssagg_mod', 'post');

		// In case you need to come back after errors....
		$_SESSION['rssagg_feed_info']['smf_link'] = !empty($_POST['smf_link']) ? htmlspecialchars($_POST['smf_link']) : '';
		$_SESSION['rssagg_feed_info']['smf_board'] = !empty($_POST['smf_board']) ? htmlspecialchars($_POST['smf_board']) : '';
		$_SESSION['rssagg_feed_info']['smf_description'] = !empty($_POST['smf_description']) ? htmlspecialchars($_POST['smf_description']) : '';
		$_SESSION['rssagg_feed_info']['smf_topic_prefix'] = !empty($_POST['smf_topic_prefix']) ? htmlspecialchars($_POST['smf_topic_prefix']) : '';
		$_SESSION['rssagg_feed_info']['smf_id_member'] = !empty($_POST['smf_id_member']) ? htmlspecialchars($_POST['smf_id_member']) : '';

		// Validate the url
		$context['rssagg_feed_info']['smf_link'] = htmlspecialchars($_POST['smf_link']);
		$smf_link = validate_iri($_POST['smf_link']);
		if (!is_string($smf_link) || (strlen($smf_link) > 255))
			fatal_lang_error('rssagg_bad_link', false);

		// Validate the board
		$context['rssagg_feed_info']['smf_board'] = htmlspecialchars($_POST['smf_board']);
		$smf_board = htmlspecialchars($_POST['smf_board']);
		$smf_board_name = get_board_name((int) $smf_board);
		if (!is_numeric($smf_board) || $smf_board == 0 || empty($smf_board_name))
			fatal_lang_error('rssagg_bad_board', false);
		$context['rssagg_feed_info']['smf_board_name'] = $smf_board_name;

		// Validate the description
		$context['rssagg_feed_info']['smf_description'] = htmlspecialchars($_POST['smf_description']);
		$smf_description = htmlspecialchars($_POST['smf_description']);
		if (!is_string($smf_description) || (strlen($smf_description) > 255))
			fatal_lang_error('rssagg_bad_desc', false);

		// Validate the prefix
		$context['rssagg_feed_info']['smf_topic_prefix'] = htmlspecialchars($_POST['smf_topic_prefix']);
		$smf_topic_prefix = htmlspecialchars($_POST['smf_topic_prefix']);
		if (!is_string($smf_topic_prefix) || (mb_strlen($smf_topic_prefix) > 25))
			fatal_lang_error('rssagg_bad_prefix', false);

		// Validate the member
		$context['rssagg_feed_info']['smf_id_member'] = htmlspecialchars($_POST['smf_id_member']);
		$smf_id_member = htmlspecialchars($_POST['smf_id_member']);
		$smf_member_name = get_member_name((int) $smf_id_member);
		if (((int) $smf_id_member != 0) && empty($smf_member_name))
			fatal_lang_error('rssagg_bad_member', false);
		$context['rssagg_feed_info']['smf_member_name'] = $smf_member_name;

		mod_rss_channel($chan, $smf_link, (int) $smf_board, $smf_description, $smf_topic_prefix, (int) $smf_id_member);

		unset($_SESSION['rssagg_feed_info']);

		// Show them what they've done, by going back to the browse form
		redirectexit('action=admin;area=rssagg;sa=browserss');
	}

	// In case we're resuming an edit...
	
	if (!empty($_SESSION['rssagg_feed_info']['smf_link']))
		$context['rssagg_feed_info']['smf_link'] = $_SESSION['rssagg_feed_info']['smf_link'];
	if (!empty($_SESSION['rssagg_feed_info']['smf_board']))
		$context['rssagg_feed_info']['smf_board'] = $_SESSION['rssagg_feed_info']['smf_board'];
	if (!empty($_SESSION['rssagg_feed_info']['smf_description']))
		$context['rssagg_feed_info']['smf_description'] = $_SESSION['rssagg_feed_info']['smf_description'];
	if (!empty($_SESSION['rssagg_feed_info']['smf_topic_prefix']))
		$context['rssagg_feed_info']['smf_topic_prefix'] = $_SESSION['rssagg_feed_info']['smf_topic_prefix'];
	if (!empty($_SESSION['rssagg_feed_info']['smf_id_member']))
		$context['rssagg_feed_info']['smf_id_member'] = $_SESSION['rssagg_feed_info']['smf_id_member'];

	unset($_SESSION['rssagg_feed_info']);

	$context['rssagg_feed_info']['smf_board_name'] = get_board_name($context['rssagg_feed_info']['smf_board']);
	$context['rssagg_feed_info']['smf_member_name'] = get_member_name($context['rssagg_feed_info']['smf_id_member']);

	createToken('rssagg_mod');

	$context['sub_template'] = 'mod_feed';
}

/**
 * show_feed_details.
 *
 * Action: admin
 * Area: rssagg
 * Subaction: showrss
 *
 * @return null
 *
 */
function show_feed_details()
{
	global $context;

	// You have to be able to moderate the forum to do this.
	isAllowedTo('admin_forum');

	// Setup the template stuff we'll need.
	loadTemplate('RSSAggregatorMaint');

	$chan = (int) $_GET['chan'];

	$context['rssagg_feed_info'] = get_feed_info($chan);

	$context['sub_template'] = 'show_feed_details';
}

/**
 * check_feeds - Scheduled Task to take a quick look at the feeds.
 * If the timing looks right, submit a background task to actually do the feed.
 * Background tasks are used because some take a while...
 *
 * Action: NA - helper function called from Scheduled Tasks.
 *
 * @param void
 *
 * @return bool $completed
 *
 */
function check_feeds()
{
	global $sourcedir;

	// May need language, esp if invoked by rando activity
	loadLanguage('RSSAggregator');

	// Get the data model info.
	require_once($sourcedir . '/RSSAggregatorModel.php');

	$weekday = date('l');
	$hour = date('G');
	$sched_info = get_feed_sched_info();

	foreach ($sched_info AS $info)
	{
		// Check skip days
		$skip_days = explode(',', $info['skip_days']);
		if (in_array($weekday, $skip_days))
			continue;

		// Check skip hours
		$skip_hours = explode(',', $info['skip_hours']);
		if (in_array($hour, $skip_hours))
			continue;

		// Check ttl
		if (time() < ((int) $info['smf_last_proc_time']) + (((int) $info['ttl']) * 60))
			continue;

		// Paused?
		if (!empty($info['smf_pause']))
			continue;

		add_rssagg_background_task($info);
	}

	// It won't log it unless we say it's true...
	return true;
}
