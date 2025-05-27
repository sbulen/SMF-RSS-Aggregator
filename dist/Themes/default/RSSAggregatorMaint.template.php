<?php
/**
 *	Template for admin functions for the RSS Aggregator mod for SMF.
 *
 *	Copyright 2024-2025 Shawn Bulen
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

/**
 * A page to add a feed.
 */

function template_add_feed()
{
	global $context, $scripturl, $txt;

	echo '
	<div>
		<form action="', $scripturl, '?action=admin;area=rssagg;sa=addrss" method="post" accept-charset="', $context['character_set'], '">
			<div class="cat_bar">
				<h3 class="catbg">', $txt['rssagg_addrss'], '
				</h3>
			</div>
			<div class="windowbg">
				<dl class="settings">';

	// Link.
	echo '
					<dt>
						<strong>', $txt['rssagg_link_short'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_link_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_link" value="', $context['rssagg_feed_info']['smf_link'], '" size="255">
					</dd>';

	// Board.
	echo '
					<dt>
						<strong>', $txt['rssagg_board'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_board_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_board" value="', $context['rssagg_feed_info']['smf_board'], '" size="10"><br>
						<span class="lefttext">', $context['rssagg_feed_info']['smf_board_name'], '</span>
					</dd>';

	// Description.
	echo '
					<dt>
						<strong>', $txt['rssagg_description'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_description_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_description" value="', $context['rssagg_feed_info']['smf_description'], '" size="255">
					</dd>';

	// Prefix.
	echo '
					<dt>
						<strong>', $txt['rssagg_prefix'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_prefix_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_topic_prefix" value="', $context['rssagg_feed_info']['smf_topic_prefix'], '" size="25">
					</dd>';

	// Member.
	echo '
					<dt>
						<strong>', $txt['rssagg_member'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_member_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_id_member" value="', $context['rssagg_feed_info']['smf_id_member'], '" size="10"><br>
						<span class="lefttext">', $context['rssagg_feed_info']['smf_member_name'], '</span>
					</dd>';

	// Table footer & button.
	echo '
				</dl>
				<input type="submit" name="add" value="', $txt['rssagg_addrss'] ,'" class="button">
				<input type="hidden" name="', $context['rssagg_add_token_var'], '" value="', $context['rssagg_add_token'], '">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">';

	echo '
			</div>
		</form>
	</div>';
}

/**
 * A page to modify an existing feed.
 */

function template_mod_feed()
{
	global $context, $scripturl, $txt;

	echo '
	<div>
		<form action="', $scripturl, '?action=admin;area=rssagg;sa=modrss" method="post" accept-charset="', $context['character_set'], '">
			<div class="cat_bar">
				<h3 class="catbg">', $txt['rssagg_modrss'], '
				</h3>
			</div>
			<div class="windowbg">
				<dl class="settings">';

	// channel ID.
	echo '
					<dt>
						<strong>', $txt['rssagg_id'], ':</strong><br>			
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['id_channel'], '</span>
					</dd>';

	// Status.
	echo '
					<dt>
						<strong>', $txt['rssagg_status'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_status'], '</span>
					</dd>';

	// Link.
	echo '
					<dt>
						<strong>', $txt['rssagg_link_short'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_link_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_link" value="', $context['rssagg_feed_info']['smf_link'], '" size="255">
					</dd>';

	// Board.
	echo '
					<dt>
						<strong>', $txt['rssagg_board'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_board_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_board" value="', $context['rssagg_feed_info']['smf_board'], '" size="10"><br>
						<span class="lefttext">', $context['rssagg_feed_info']['smf_board_name'], '</span>
					</dd>';

	// Description.
	echo '
					<dt>
						<strong>', $txt['rssagg_description'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_description_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_description" value="', $context['rssagg_feed_info']['smf_description'], '" size="255">
					</dd>';

	// Prefix.
	echo '
					<dt>
						<strong>', $txt['rssagg_prefix'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_prefix_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_topic_prefix" value="', $context['rssagg_feed_info']['smf_topic_prefix'], '" size="25">
					</dd>';

	// Member.
	echo '
					<dt>
						<strong>', $txt['rssagg_member'], ':</strong><br>
						<span class="smalltext">', $txt['rssagg_member_desc'], '</span>
					</dt>
					<dd>
						<input type="text" name="smf_id_member" value="', $context['rssagg_feed_info']['smf_id_member'], '" size="10"><br>
						<span class="lefttext">', $context['rssagg_feed_info']['smf_member_name'], '</span>
					</dd>';

	// Table footer & button.
	echo '
				</dl>
				<input type="submit" name="mod" value="', $txt['rssagg_modrss'] ,'" class="button">
				<input type="hidden" name="chan" value="', $context['rssagg_feed_info']['id_channel'], '">
				<input type="hidden" name="', $context['rssagg_mod_token_var'], '" value="', $context['rssagg_mod_token'], '">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">';

	echo '
			</div>
		</form>
	</div>';
}

/**
 * Shows detailed channel info for one feed
 */

function template_show_feed_details()
{
	global $context, $scripturl, $txt;

	global $context, $scripturl, $txt;

	// Show the SMF-maintained info...
	echo '
	<div>
		<form action="', $scripturl, '?action=admin;area=rssagg;sa=browserss" method="post" accept-charset="', $context['character_set'], '">
			<div class="cat_bar">
				<h3 class="catbg">', $txt['rssagg_feed_config'], '
				</h3>
			</div>
			<div class="windowbg">
				<dl class="settings">';

	// channel ID.
	echo '
					<dt>
						<strong>', $txt['rssagg_id'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['id_channel'], '</span>
					</dd>';

	// Link.
	echo '
					<dt>
						<strong>', $txt['rssagg_link_short'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_link'], '</span>
					</dd>';

	// Board.
	echo '
					<dt>
						<strong>', $txt['rssagg_board'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_board'], '</span>
					</dd>';

	// Description.
	echo '
					<dt>
						<strong>', $txt['rssagg_description'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_description'], '</span>
					</dd>';

	// Prefix.
	echo '
					<dt>
						<strong>', $txt['rssagg_prefix'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_topic_prefix'], '</span>
					</dd>';

	// Member.
	echo '
					<dt>
						<strong>', $txt['rssagg_member'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_id_member'], '</span>
					</dd>';

	// Last processed.
	echo '
					<dt>
						<strong>', $txt['rssagg_last_proc'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_last_proc_time_txt'], '</span>
					</dd>';

	// Status.
	echo '
					<dt>
						<strong>', $txt['rssagg_status'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_status'], '</span>
					</dd>';

	// Paused.
	echo '
					<dt>
						<strong>', $txt['rssagg_pause'], ':</strong><br>
					</dt>
					<dd>
						<span class="leftext">', $context['rssagg_feed_info']['smf_pause_txt'], '</span>
					</dd>';

	// Table footer & button.
	echo '
				</dl>
				<input type="submit" value="', $txt['rssagg_return'] ,'" class="button">
			</div>
		</form>
	</div>';

	// Show the info the channel itself reports out...
	echo '
	<div>
		<div class="cat_bar">
			<h3 class="catbg">', $txt['rssagg_details'], '
			</h3>
		</div>
		<div class="windowbg">
			<dl class="settings">';

	// Title.
	echo '
				<dt>
					<strong>', $txt['rssagg_feed_title'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['title'], '</span>
				</dd>';

	// Reported link.
	echo '
				<dt>
					<strong>', $txt['rssagg_rep_link'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['link'], '</span>
				</dd>';

	// Reported description.
	echo '
				<dt>
					<strong>', $txt['rssagg_rep_desc'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['description'], '</span>
				</dd>';

	// Language.
	echo '
				<dt>
					<strong>', $txt['rssagg_language'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['language'], '</span>
				</dd>';

	// Copyright.
	echo '
				<dt>
					<strong>', $txt['rssagg_copyright'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['copyright'], '</span>
				</dd>';

	// Webmaster.
	echo '
				<dt>
					<strong>', $txt['rssagg_webmaster'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['webmaster'], '</span>
				</dd>';

	// Last Build Date.
	echo '
				<dt>
					<strong>', $txt['rssagg_last_build'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['last_build_date'], '</span>
				</dd>';

	// Pub Date.
	echo '
				<dt>
					<strong>', $txt['rssagg_pub_date'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['pub_date'], '</span>
				</dd>';

	// Docs.
	echo '
				<dt>
					<strong>', $txt['rssagg_docs'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['docs'], '</span>
				</dd>';

	// Category.
	echo '
				<dt>
					<strong>', $txt['rssagg_category'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['category'], '</span>
				</dd>';

	// Cloud.
	echo '
				<dt>
					<strong>', $txt['rssagg_cloud'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['cloud'], '</span>
				</dd>';

	// Generator.
	echo '
				<dt>
					<strong>', $txt['rssagg_generator'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['generator'], '</span>
				</dd>';

	// Atom link.
	echo '
				<dt>
					<strong>', $txt['rssagg_atom_link'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['atom_link'], '</span>
				</dd>';

	// Skip Days.
	echo '
				<dt>
					<strong>', $txt['rssagg_skip_days'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['skip_days'], '</span>
				</dd>';

	// Skip Hours.
	echo '
				<dt>
					<strong>', $txt['rssagg_skip_hours'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['skip_hours'], '</span>
				</dd>';

	// ttl
	echo '
				<dt>
					<strong>', $txt['rssagg_ttl'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['ttl'], '</span>
				</dd>';

	// Rating
	echo '
				<dt>
					<strong>', $txt['rssagg_rating'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['rating'], '</span>
				</dd>';

	// Managing Editor
	echo '
				<dt>
					<strong>', $txt['rssagg_managing_editor'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['managing_editor'], '</span>
				</dd>';

	// Managing Editor URI
	echo '
				<dt>
					<strong>', $txt['rssagg_managing_editor_uri'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['managing_editor_uri'], '</span>
				</dd>';

	// Alt link
	echo '
				<dt>
					<strong>', $txt['rssagg_link_alt'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['link_alt'], '</span>
				</dd>';

	// Youtube ID
	echo '
				<dt>
					<strong>', $txt['rssagg_yt_id'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['yt_id'], '</span>
				</dd>';

	// Youtube Channel ID
	echo '
				<dt>
					<strong>', $txt['rssagg_yt_channel_id'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['yt_channel_id'], '</span>
				</dd>';

	// Webfeeds icon
	echo '
				<dt>
					<strong>', $txt['rssagg_webfeeds_icon'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['webfeeds_icon'], '</span>
				</dd>';

	// Image Title
	echo '
				<dt>
					<strong>', $txt['rssagg_img_title'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['img_title'], '</span>
				</dd>';

	// Image Url
	echo '
				<dt>
					<strong>', $txt['rssagg_img_url'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['img_url'], '</span>
				</dd>';

	// Image Link
	echo '
				<dt>
					<strong>', $txt['rssagg_img_link'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['img_link'], '</span>
				</dd>';

	// Image Description
	echo '
				<dt>
					<strong>', $txt['rssagg_img_description'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['img_description'], '</span>
				</dd>';

	// Image Width
	echo '
				<dt>
					<strong>', $txt['rssagg_img_width'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['img_width'], '</span>
				</dd>';

	// Image Height
	echo '
				<dt>
					<strong>', $txt['rssagg_img_height'], ':</strong><br>
				</dt>
				<dd>
					<span class="leftext">', $context['rssagg_feed_info']['img_height'], '</span>
				</dd>';

	// Table footer & button.
	echo '
			</dl>
		</div>
	</div>';
}
