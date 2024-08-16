<?php
/**
 *	DB interaction for the RSS Aggregator mod for SMF..
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
 * get_feed_sched_info - returns an array of scheduling info about feeds.
 *
 * @params int $start - for multi-page queries
 * @params int $limit - for multi-page queries
 * @params int $sort
 *
 * @return array
 *
 */
function get_feed_sched_info($start = 0, $limit = 0, $sort = 'id_channel')
{
	global $smcFunc, $txt;

	$sched_info = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_channel, smf_link, smf_board, smf_topic_prefix, smf_id_member, smf_last_proc_time, smf_status, smf_pause, skip_days, skip_hours, ttl, COALESCE(b.name, \'\') AS name, COALESCE(m.real_name, \'\') AS real_name
		FROM {db_prefix}rss_channels rc
		LEFT JOIN {db_prefix}members m ON rc.smf_id_member = m.id_member
		LEFT JOIN {db_prefix}boards b ON rc.smf_board = b.id_board
		ORDER BY {raw:sort}' .
		(empty($limit) ? '' : ' LIMIT {int:limit}' . (empty($start) ? '' : ' OFFSET {int:start}')),
		array(
			'start' => $start,
			'limit' => $limit,
			'sort' => $sort,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$row['smf_last_proc_time_txt'] = !empty($row['smf_last_proc_time']) ? timeformat($row['smf_last_proc_time']) : '';
		$row['smf_pause_txt'] = !empty($row['smf_pause']) ? $txt['rssagg_pause'] : '';
		$sched_info[] = $row;
	}
	$smcFunc['db_free_result']($request);

	return $sched_info;
}

/**
 * get_feed_sched_count - returns the total count of feeds.
 *
 * @return int
 *
 */
function get_feed_sched_count()
{
	global $smcFunc;

	$count = 0;

	$request = $smcFunc['db_query']('', '
		SELECT count(*) AS count
		FROM {db_prefix}rss_channels',
		array()
	);

	$count = (int) $smcFunc['db_fetch_assoc']($request)['count'];
	$smcFunc['db_free_result']($request);

	return $count;
}

/**
 * get_feed_info - returns all info about one requested feed.
 *
 * @params int $id_channel - the requested channel
 *
 * @return array
 *
 */
function get_feed_info($id_channel = 'id_channel')
{
	global $smcFunc, $txt;

	$sched_info = array();

	if (empty($id_channel) || !is_int($id_channel))
		return $sched_info;

	$request = $smcFunc['db_query']('', '
		SELECT * 
		FROM {db_prefix}rss_channels
		WHERE id_channel = {int:channel}',
		array(
			'channel' => $id_channel,
		)
	);

	$sched_info = $smcFunc['db_fetch_assoc']($request);

	$sched_info['smf_last_proc_time_txt'] = !empty($sched_info['smf_last_proc_time']) ? timeformat($sched_info['smf_last_proc_time']) : '';
	$sched_info['smf_pause_txt'] = !empty($sched_info['smf_pause']) ? $txt['rssagg_pause'] : '';

	$smcFunc['db_free_result']($request);

	return $sched_info;
}

/**
 * add_rss_channel - adds a new feed.
 *
 * @param string $url
 * @param int $board
 * @param string $desc
 * @param string $prefix
 * @param int $member
 *
 * @return void
 *
 */
function add_rss_channel($url, $board, $desc='', $prefix = '', $member = 0)
{
	global $smcFunc;

	if (!empty($url) && is_string($url) && !empty($board) && is_numeric($board) && is_string($desc) && is_string($prefix) && is_int($member))
	{
		$smcFunc['db_insert']('',
			'{db_prefix}rss_channels',
			array('smf_link' => 'string-255', 'smf_board' => 'int', 'smf_description' => 'string-255', 'smf_topic_prefix' => 'string-25', 'smf_id_member' => 'int'),
			array($url, $board, $desc, $prefix, $member),
			array('id_channel')
		);
	}
}

/**
 * mod_rss_channel - modifies an existing feed.
 *
 * @param int $id_channel
 * @param string $url
 * @param int $board
 * @param string $desc
 * @param string $prefix
 * @param int $member
 *
 * @return void
 *
 */
function mod_rss_channel($id_channel, $url, $board, $desc='', $prefix = '', $member = 0)
{
	global $smcFunc;

	if (!empty($id_channel) && !empty($url) && is_string($url) && !empty($board) && is_numeric($board) && is_string($desc) && is_string($prefix) && is_int($member))
	{
		$smcFunc['db_query']('',
			'UPDATE {db_prefix}rss_channels
				SET smf_link = {string:smf_link},
				smf_board = {int:smf_board},
				smf_description = {string:smf_description},
				smf_topic_prefix = {string:smf_topic_prefix},
				smf_id_member = {int:smf_id_member}
			WHERE id_channel = {int:id_channel}',
			array(
				'id_channel' => $id_channel,
				'smf_link' => $url,
				'smf_board' => $board,
				'smf_description' => $desc,
				'smf_topic_prefix' => $prefix,
				'smf_id_member' => $member,
			),
		);
	}
}

/**
 * add_rssagg_background_task.
 *
 * Note that the $info assoc array passed ***must*** have the following info from the feed definition:
 *  'id_channel', 'smf_link', 'smf_topic_prefix', 'smf_id_member', 'smf_board'
 *
 * @param array $info
 *
 * @return null
 *
 */
function add_rssagg_background_task($info)
{
	global $smcFunc;

	if (!empty($info['id_channel']) && is_numeric($info['id_channel']))
	{
		$smcFunc['db_insert']('',
			'{db_prefix}background_tasks',
			array('task_file' => 'string', 'task_class' => 'string', 'task_data' => 'string', 'claimed_time' => 'int'),
			array('$sourcedir/tasks/RSSAggregator-UpdateFeed.php', 'RSSAggregator_Background', $smcFunc['json_encode']($info), 0),
			array('id_task')
		);
	}
}

/**
 * toggle_pause_state.
 *
 * @param array $channels
 *
 * @return null
 *
 */
function toggle_pause_state($channels)
{
	global $smcFunc;

	if (!empty($channels) && is_array($channels))
	{
		$smcFunc['db_query']('', 'UPDATE {db_prefix}rss_channels
			SET smf_pause =
				CASE
					WHEN smf_pause = 1 THEN 0
					WHEN smf_pause = 0 THEN 1
				END
			WHERE id_channel IN ({array_int:channels})',
		array(
			'channels' => $channels,
			)
		);
	}
}

/**
 * zero_last_proc_time.
 *
 * This makes it so the process can be rerun sooner.
 *
 * @param array $id_channels
 *
 * @return null
 *
 */
function zero_last_proc_time($id_channels)
{
	global $smcFunc;

	if (!empty($id_channels) && is_array($id_channels))
	{
		$smcFunc['db_query']('', "UPDATE {db_prefix}rss_channels
			SET smf_last_proc_time = 0
			WHERE id_channel IN ({array_int:channels})",
		array(
			'channels' => $id_channels,
		));
	}
}

/**
 * touch_last_proc_time.
 *
 * Used to record the execution time.
 *
 * @param int $id_channel
 *
 * @return null
 *
 */
function touch_last_proc_time($id_channel)
{
	global $smcFunc;

	if (!empty($id_channel) && is_numeric($id_channel))
	{
		$smcFunc['db_query']('', "UPDATE {db_prefix}rss_channels
			SET smf_last_proc_time = {int:curr_time}
			WHERE id_channel = {int:channel}",
		array(
			'curr_time' => time(),
			'channel' => $id_channel,
		));
	}
}

/**
 * set_channel_status.
 *
 * @param int $id_channel
 * @param string $status
 *
 * @return null
 *
 */
function set_channel_status($id_channel, $status)
{
	global $smcFunc;

	if (!empty($id_channel) && is_numeric($id_channel) && !empty($status) && is_string($status))
	{
		$smcFunc['db_query']('', "UPDATE {db_prefix}rss_channels
			SET smf_status = {string:status}
			WHERE id_channel = {int:channel}",
		array(
			'status' => $status,
			'channel' => $id_channel,
		));
	}
}

/**
 * guid_add.
 *
 * @param int $id_channel
 * @param string $guid
 * @param int $id_msg
 *
 * @return null
 *
 */
function guid_add($id_channel, $guid, $id_msg)
{
	global $smcFunc;

	if (!empty($id_channel) && is_numeric($id_channel) && !empty($guid) && is_string($guid) && !empty($id_msg) && is_numeric($id_msg))
	{
		$smcFunc['db_insert']('REPLACE',
			'{db_prefix}rss_guids',
			array('id_channel' => 'int', 'guid' => 'string-255', 'id_msg' => 'int', 'date_added' => 'int'),
			array($id_channel, $guid, $id_msg, time()),
			array('id_channel', 'guid'),
		);
	}
}

/**
 * guid_delete_channels.  List processing allows bulk deletion, so accepts array.
 *
 * @param array $id_channels
 *
 * @return null
 *
 */
function guid_delete_channels($id_channels)
{
	global $smcFunc;

	if (!empty($id_channels) && is_array($id_channels))
	{
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}rss_guids
			WHERE id_channel IN ({array_int:id_channels})',
			array(
				'id_channels' => $id_channels,
			),
		);
	}
}

/**
 * guid_delete_message.  For cleaning up upon message deletion.
 *
 * @param int $id_msg
 *
 * @return null
 *
 */
function guid_delete_message($id_msg)
{
	global $smcFunc;

	if (!empty($id_msg) && is_numeric($id_msg))
	{
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}rss_guids
			WHERE id_msg = {int:id_msg}',
			array(
				'id_msg' => $id_msg,
			),
		);
	}
}

/**
 * guid_delete_topics.  For cleaning up upon message/topic deletion.
 *
 * @param array $topics
 *
 * @return null
 *
 */
function guid_delete_topics($topics)
{
	global $smcFunc;

	if (!is_array($topics))
		return;

	$request = $smcFunc['db_query']('', 'SELECT id_first_msg FROM {db_prefix}topics
		WHERE id_topic IN ({array_int:topics})',
		array(
			'topics' => $topics,
		),
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		guid_delete_message($row['id_first_msg']);
	$smcFunc['db_free_result']($request);
}

/**
 * guid_exists.  Check if that guid already exists or not.
 *
 * @param int $id_channel
 * @param string $guid
 *
 * @return bool $exists
 *
 */
function guid_exists($id_channel, $guid)
{
	global $smcFunc;

	$result = false;

	if (empty($id_channel) || !is_numeric($id_channel) || empty($guid) || !is_string($guid))
		return $result;

	$request = $smcFunc['db_query']('', 'SELECT guid FROM {db_prefix}rss_guids
		WHERE id_channel = {int:id_channel}
		AND guid = {string:guid}',
		array(
			'id_channel' => $id_channel,
			'guid' => $guid,
		),
	);
	while ($smcFunc['db_fetch_assoc']($request))
		$result = true;
	$smcFunc['db_free_result']($request);

	return $result;
}

/**
 * update_channel_feed_info.  Update the channel - specifically, the fields that come from the feed.
 *
 * @param int $id_channel
 * @param array $updates - Only updates need be passed.
 *
 * @return void
 *
 */
function update_channel_feed_info($id_channel, $updates)
{
	global $smcFunc;

	if (empty($updates) || !is_array($updates) || empty($id_channel) || !is_numeric($id_channel))
	    return;

	// Let's setup all the feed-owned fields with defaults.  That way, 
	// we only need to be fed info about what's been passed in the feed.
	// *** As passed in the feed... I.e., with few exceptions, as strings... ***
	// Feed-owned fields are all fields that don't start with "smf_".

	// This looks like overkill, but, I wanted to blank out anything not passed
	// in the last update - it's now outdated...
	$fields = array(
		'title' => '',
		'link' => '',
		'description' => '',
		'language' => '',
		'copyright' => '',
		'webmaster' => '',
		'last_build_date' => '',
		'pub_date' => '',
		'docs' => '',
		'category' => '',
		'cloud' => '',
		'generator' => '',
		'atom_link' => '',
		'skip_days' => '',
		'skip_hours' => '',
		'ttl' => 0,
		'rating' => '',
		'managing_editor' => '',
		'managing_editor_uri' => '',
		'link_alt' => '',
		'yt_id' => '',
		'yt_channel_id' => '',
		'webfeeds_icon' => '',
		'img_title' => '',
		'img_url' => '',
		'img_link' => '',
		'img_description' => '',
		'img_width' => 0,
		'img_height' => 0,
	);

	// Merge with passed updates...
	foreach ($fields as $key => $value)
		if (array_key_exists($key, $updates))
			$fields[$key] = htmlspecialchars($updates[$key]);

	// Gotta add the key, too...
	$fields['id_channel'] = $id_channel;

	$request = $smcFunc['db_query']('', 'UPDATE {db_prefix}rss_channels
		SET
			title = {string:title},
			link = {string:link},
			description = {string:description},
			language = {string:language},
			copyright = {string:copyright},
			webmaster = {string:webmaster},
			last_build_date = {string:last_build_date},
			pub_date = {string:pub_date},
			docs = {string:docs},
			category = {string:category},
			cloud = {string:cloud},
			generator = {string:generator},
			atom_link = {string:atom_link},
			skip_days = {string:skip_days},
			skip_hours = {string:skip_hours},
			ttl = {int:ttl},
			rating = {string:rating},
			managing_editor = {string:managing_editor},
			managing_editor_uri = {string:managing_editor_uri},
			link_alt = {string:link_alt},
			yt_id = {string:yt_id},
			yt_channel_id = {string:yt_channel_id},
			webfeeds_icon = {string:webfeeds_icon},
			img_title = {string:img_title},
			img_url = {string:img_url},
			img_link = {string:img_link},
			img_description = {string:img_description},
			img_width = {int:img_width},
			img_height = {int:img_height}
		WHERE id_channel = {int:id_channel}',
	$fields,
	);
}

/**
 * rss_feed_delete.  List processing allows bulk deletion, so accepts array.
 *
 * @param array $id_channels
 *
 * @return null
 *
 */
function rss_feed_deletes($id_channels)
{
	global $smcFunc;

	if (!empty($id_channels) && is_array($id_channels))
	{
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}rss_channels
			WHERE id_channel IN({array_int:id_channels})',
			array(
				'id_channels' => $id_channels,
			),
		);
	}
}

/**
 * get_poster_info.  Helps with posting....
 *
 * @param int $id_member
 *
 * @return array $member_info
 *
 */
function get_poster_info($id_member)
{
	global $smcFunc;

	$member_info = array();

	if (empty($id_member) || !is_numeric($id_member))
		return $member_info;

	$request = $smcFunc['db_query']('', 'SELECT id_member, real_name, email_address FROM {db_prefix}members
		WHERE id_member = {int:id_member}',
		array(
			'id_member' => $id_member,
		),
	);
	$member_info = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	return $member_info;
}

/**
 * get_board_name.  Empty string if board not found.
 *
 * @param int $id_board
 *
 * @return string $board_name
 *
 */
function get_board_name($id_board)
{
	global $smcFunc;

	$board_name = '';
	$id_board = (int) $id_board;

	if (empty($id_board))
		return $board_name;

	$request = $smcFunc['db_query']('', 'SELECT name FROM {db_prefix}boards
		WHERE id_board = {int:id_board}',
		array(
			'id_board' => $id_board,
		),
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$board_name = $row['name'];
	$smcFunc['db_free_result']($request);

	return $board_name;
}

/**
 * get_member_name.  Empty string if member not found.
 *
 * @param int $id_member
 *
 * @return string $member_name
 *
 */
function get_member_name($id_member)
{
	global $smcFunc;

	$member_name = '';
	$id_member = (int) $id_member;

	if (empty($id_member))
		return $member_name;

	$request = $smcFunc['db_query']('', 'SELECT real_name FROM {db_prefix}members
		WHERE id_member = {int:id_member}',
		array(
			'id_member' => $id_member,
		),
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$member_name = $row['real_name'];
	$smcFunc['db_free_result']($request);

	return $member_name;
}
