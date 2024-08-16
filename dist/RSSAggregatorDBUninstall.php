<?php

global $smcFunc;

// Add the scheduled task...
$smcFunc['db_query']('', "DELETE FROM {db_prefix}scheduled_tasks WHERE task = 'check_rss_feeds'");
