<?php

global $smcFunc;

if (!isset($smcFunc['db_create_table']))
	db_extend('packages');

$create_tables = array(
	'rss_channels' => array(
		'columns' => array(
		    array(
			   'name' => 'id_channel',
				'type' => 'int',
				'not_null' => true,
				'auto' => true,
			),
			array(
				'name' => 'smf_link',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'smf_description',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'smf_board',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'smf_topic_prefix',
				'type' => 'varchar',
				'size' => 25,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'smf_id_member',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'smf_last_proc_time',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'smf_status',
				'type' => 'varchar',
				'size' => 10,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'smf_pause',
				'type' => 'tinyint',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'title',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'link',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'description',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'language',
				'type' => 'varchar',
				'size' => 20,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'copyright',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'webmaster',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
		    array(
				'name' => 'last_build_date',
				'type' => 'varchar',
				'size' => 50,
				'default' => '',
				'not_null' => true,
			),
		    array(
				'name' => 'pub_date',
				'type' => 'varchar',
				'size' => 50,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'docs',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'category',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'cloud',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'generator',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'atom_link',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'skip_days',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'skip_hours',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'ttl',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'rating',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'managing_editor',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'managing_editor_uri',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'link_alt',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'yt_id',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'yt_channel_id',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'webfeeds_icon',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'img_title',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'img_url',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'img_link',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'img_description',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'img_width',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'img_height',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_channel'),
			),
		),
	),
	'rss_guids' => array(
		'columns' => array(
		    array(
			   'name' => 'id_channel',
				'type' => 'int',
				'not_null' => true,
				'auto' => true,
			),
			array(
				'name' => 'guid',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
				'not_null' => true,
			),
			array(
				'name' => 'id_msg',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
			array(
				'name' => 'date_added',
				'type' => 'int',
				'default' => 0,
				'not_null' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'unique',
				'columns' => array('id_channel', 'guid'),
			),
			array(
				'type' => 'unique',
				'columns' => array('id_msg'),
			),
		),
	),
);

foreach ($create_tables AS $table_name => $data)
	$smcFunc['db_create_table']('{db_prefix}' . $table_name, $data['columns'], $data['indexes']);

// Add the scheduled task...
$smcFunc['db_insert']('ignore', '{db_prefix}scheduled_tasks',
	array('time_offset' => 'int', 'time_regularity' => 'int', 'time_unit' => 'string-1', 'disabled' => 'int', 'task' => 'string-24', 'callable' => 'string-60'),
	array('1020', '2', 'h', '0', 'check_rss_feeds', '$sourcedir/RSSAggregator.php|check_feeds'),
	array('id_channel'));



