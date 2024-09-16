<?php
/**
 *	Background task to update the feed.
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
class RSSAggregator_Background extends SMF_BackgroundTask
{
	protected $feed_xml = null;

	/**
	 * This executes the task to read the feed & create new posts.
	 *
	 * @return bool Always returns true, so tasks don't clog up queue
	 */
	public function execute()
	{
		global $sourcedir, $txt;

		loadLanguage('RSSAggregator');
		require_once($sourcedir . '/RSSAggregatorModel.php');

		$feed_text = fetch_web_data($this->_details['smf_link']);
		
		if ($feed_text === false)
		{
			$this->report_error('rssagg_err_cannot_access_link');
			return true;
		}

		// Note that SimpleXML really, really, really hates <br> tags, they cause parse errors...
		$feed_text = preg_replace('~<br ?\/?>~', "\n", $feed_text);

		// Suppress the logging of xml parse errors & warnings...
		// My thinking is that the biggest issue will be folks pointing to the wrong file...
		// It doesn't make sense to fill the log with hundreds of xml errors & warnings for this.
		libxml_use_internal_errors(true);

		// The only way to catch really bad parse errors, e.g., not an xml file, is to use a try/catch.
		// Otherwise, nice & simple...
		try
			{$this->feed_xml = new SimpleXMLElement($feed_text, LIBXML_NOBLANKS|LIBXML_NOERROR);}
		catch (Exception $e)
			{$this->feed_xml = false;}

		// One simple error to let them know to research the issue...
		if ($this->feed_xml === false)
		{
			$this->report_error('rssagg_err_cannot_parse');
			return true;
		}

		// Parse that puppy!
		$this->parse_that_puppy();

		// Record the datetime, & set status to OK...
		touch_last_proc_time($this->_details['id_channel']);
		set_channel_status($this->_details['id_channel'], $txt['rssagg_ok']);

		return true;
	}

	/**
	 * Report errors - set status, & log.
	 *
	 * @params string $key - key to error message in $txt[]
	 *
	 * @return void
	 */
	private function report_error($key)
	{
		global $sourcedir, $txt;

		require_once($sourcedir . '/Errors.php');

		// If key doesn't exist, just use passed text, e.g., for debugging...
		if (!empty($txt[$key]))
			$msg_text = $txt[$key] . ' ' . $this->_details['smf_link'];
		else
			$msg_text = $key . ' ' . $this->_details['smf_link'];

		log_error($msg_text);

		set_channel_status($this->_details['id_channel'], $txt['rssagg_failed']);
	}

	/**
	 * Parse that puppy - read the feed & figure out which updates to make.
	 * The approach is to flatten the different standards down to a common set of tags.
	 * Convert existing entity-encoded chars back...  Need to do a lot of processing of html.
	 * We'll re-encode later, before storage...
	 *
	 * Note that publication dates at the channel level tend to reflect *initial* publication
	 * dates & are sometimes many years old for a currently publishing feed.  Cannot filter
	 * based on date at the channel level, only at the line item level.
	 *
	 * @return void
	 */
	private function parse_that_puppy()
	{
		$updates = array();
		$items = array();
		$curr_node = null;

		// rss has one extra layer up top; skip it
		if (!strcasecmp($this->feed_xml->getName(), 'rss') && isset($this->feed_xml?->channel))
			$curr_node = $this->feed_xml->channel;
		else
			$curr_node = $this->feed_xml;

		// Using SimpleXML requires you to check each namespace independently.
		$namespaces = $curr_node->getDocNamespaces();
		// Not all feeds specify the blank namespace, but it's important, it's the default...
		// Without the blank entry, 90% of the items aren't found by foreach()...
		if (!array_key_exists('', $namespaces))
			$namespaces[''] = '';
		// Confirm we're working with an rss channel or yt feed
		if (!strcasecmp($curr_node->getName(), 'feed') || !strcasecmp($curr_node->getName(), 'channel'))
		{
			// Gotta check each namespace when using SimpleXML
			foreach ($namespaces as $prefix => $ns)
			{
				foreach ($curr_node->children($prefix, true) as $key => $ele)
				{
					if (!strcasecmp($key, 'item') || !strcasecmp($key, 'entry'))
						$items[] = $ele;
					else
					{
						// Lowercase keys, because cap usage is all over the map...
						$key_lower = strtolower($key);
						switch ($key_lower)
						{
							case 'title':
							case 'language':
							case 'webmaster':
							case 'docs':
							case 'category':
							case 'cloud':
							case 'generator':
							case 'webmaster':
							case 'skip_days':
							case 'skip_hours':
							case 'rating':
							case 'ttl':
								$updates[$key_lower] = html_entity_decode($ele->__toString());
								break;
							case 'subtitle':
							case 'description':
								$updates['description'] = html_entity_decode($ele->__toString());
								break;
							case 'updated':
							case 'lastbuilddate':
								$updates['last_build_date'] = html_entity_decode($ele->__toString());
								break;
							case 'rights':
							case 'copyright':
								$updates['copyright'] = html_entity_decode($ele->__toString());
								break;
							case 'pubdate':
							case 'published':
								$updates['pub_date'] = html_entity_decode($ele->__toString());
								break;
							case 'icon':
								$updates['webfeeds_icon'] = html_entity_decode($ele->__toString());
								break;
							case 'id':
								$updates['yt_id'] = html_entity_decode($ele->__toString());
								break;
							case 'channelid':
								$updates['yt_channel_id'] = html_entity_decode($ele->__toString());
								break;
							case 'link':
								if (isset($ele->attributes()['rel']) && isset($ele->attributes()['href']))
								{
									if ($prefix === 'atom')
										$updates['atom_link'] = html_entity_decode($ele->attributes()['href']->__toString());
									elseif ($ele->attributes()['rel']->__toString() === 'self')
										$updates['link'] = html_entity_decode($ele->attributes()['href']->__toString());
									else
										$updates['link_alt'] = html_entity_decode($ele->attributes()['href']->__toString());
									break;
								}
								else
								{
									$updates['link'] = html_entity_decode($ele->__toString());
								}
								break;
							// Never seen both editor & author provided...
							// & When editor is provided, most of the time it's actually the author...
							case 'managingeditor':
								$updates['managing_editor'] = html_entity_decode($ele->__toString());
								break;
							case 'author':
								if (isset($ele->name))
									$updates['managing_editor'] = html_entity_decode($ele->name->__toString());
								if (isset($ele->uri))
									$updates['managing_editor_uri'] = html_entity_decode($ele->uri->__toString());
								break;
							case 'image':
								if (isset($ele->title))
									$updates['img_title'] = html_entity_decode($ele->title->__toString());
								if (isset($ele->link))
									$updates['img_link'] = html_entity_decode($ele->link->__toString());
								if (isset($ele->url))
									$updates['img_url'] = html_entity_decode($ele->url->__toString());
								if (isset($ele->description))
									$updates['img_description'] = html_entity_decode($ele->description->__toString());
								if (isset($ele->width))
									$updates['img_width'] = html_entity_decode($ele->width->__toString());
								if (isset($ele->width))
									$updates['img_height'] = html_entity_decode($ele->height->__toString());
								break;
							// Ignore these tags, no action:
							case 'textinput':
							case 'updatefrequency':
							case 'updateperiod':
							case 'site':
								break;
							default:
								if ($this->learn_mode_enabled())
									$this->report_error('Learn Mode unknown element: ' . $key . ':');
						}
					}
				}
			}
		}
		else
		{
			// Something ain't right...
			$this->report_error('rssagg_unknown_format');
			return;
		}

		// Items were saved off above, into an array.  This is because they are
		// almost always in chronological order, but it looks more natural in the SMF forum
		// to show in reverse chronological order.  So reverse the order here, before
		// processing.
		// Also...  Not all feeds include the author, so pass it on down if available.
		$items = array_reverse($items);
		foreach ($items as $item)
			$this->parse_that_item($item, !empty($updates['managing_editor']) ? $updates['managing_editor'] : '');

		// Now that we have all the channel info, let's update the channel.
		// We will make the updates whether or not new items were found, e.g.,
		// in case the feed updated ttl or SkipDays, etc., we want the new info.
		update_channel_feed_info($this->_details['id_channel'], $updates);
	}

	/**
	 * Parse that item - work on a specific item/entry to post.
	 * The approach is to flatten the different standards down to a common set of tags.
	 * Convert existing entity-encoded chars back...  Need to do a lot of processing of html.
	 * We'll re-encode later, before storage...
	 *
	 * @params SimpleXMLElement $curr_node - entry or item to parse
	 * @params string $default_author
	 *
	 * @return void
	 */
	private function parse_that_item($curr_node, $default_author)
	{
		global $sourcedir, $txt;

		$updates = array();
		$msgOptions = array();
		$topicOptions = array();
		$posterOptions = array();

		// Using SimpleXML requires you to check each namespace independently.
		$namespaces = $curr_node->getDocNamespaces();
		// Not all feeds specify the blank namespace, but it's important, it's the default...
		// Without the blank entry, 90% of the items aren't found by foreach()...
		if (!array_key_exists('', $namespaces))
			$namespaces[''] = '';
		foreach ($namespaces as $prefix => $ns)
		{
			foreach ($curr_node->children($prefix, true) as $key => $ele)
			{
				// Lowercase keys, because cap usage is all over the map...
				$key_lower = strtolower($key);
				switch ($key_lower)
				{
					case 'title':
					case 'id':
					case 'guid':
					case 'updated':
						$updates[$key_lower] = html_entity_decode($ele->__toString());
						break;
					case 'summary':
					case 'description':
						$updates['description'] = html_entity_decode($ele->__toString());
						break;
					case 'pubdate':
					case 'published':
						$updates['pub_date'] = html_entity_decode($ele->__toString());
						break;
					case 'videoid':
						$updates['yt_id'] = html_entity_decode($ele->__toString());
						break;
					case 'channelid':
						$updates['yt_channel_id'] = html_entity_decode($ele->__toString());
						break;
					case 'author':
						if (isset($ele->name))
							$updates['managing_editor'] = html_entity_decode($ele->name->__toString());
						if (isset($ele->uri))
							$updates['managing_editor_uri'] = html_entity_decode($ele->uri->__toString());
						break;
					case 'encoded':
						$updates['encoded'] = html_entity_decode($ele->__toString());
						break;
					case 'creator':
						$updates['managing_editor'] = html_entity_decode($ele->__toString());
						break;
					case 'link':
						if (isset($ele->attributes()['rel']) && isset($ele->attributes()['href']))
						{
							if ($ele->attributes()['rel']->__toString() === 'self')
								$updates['link'] = html_entity_decode($ele->attributes()['href']->__toString());
							else
								$updates['link_alt'] = html_entity_decode($ele->attributes()['href']->__toString());
							break;
						}
						else
						{
							$updates['link'] = html_entity_decode($ele->__toString());
						}
						break;
					// media:group...
					// These tend to be used when the focus of the post is a single image, video, etc.
					case 'group':
						if (isset($ele->title))
							$updates['media_title'] = html_entity_decode($ele->title->__toString());
						if (isset($ele->content))
							$updates['media_content'] = html_entity_decode($ele->content->__toString());
						if (isset($ele->thumbnail))
							$updates['media_thumbnail'] = html_entity_decode($ele->thumbnail->__toString());
						if (isset($ele->description))
							$updates['media_description'] = html_entity_decode($ele->description->__toString());
						break;
					// enclosure...
					// These tend to be used when the focus of the post is a single image, video, etc.
					case 'enclosure':
						$updates['media_content'] = html_entity_decode($ele->__toString());
						break;
					// media:* but sometimes not in a group...
					case 'thumbnail':
						$updates['media_thumbnail'] = html_entity_decode($ele->thumbnail->__toString());
						break;
					// media:content group
					// There are often multiple of these, usually images...
					case 'content':
						$temp = array();
						if (isset($ele->attributes()['url']))
							$temp['media_url'] = html_entity_decode($ele->attributes()['url']->__toString());
						if (isset($ele->attributes()['type']))
							$temp['media_type'] = html_entity_decode($ele->attributes()['type']->__toString());
						if (isset($ele->attributes()['medium']))
							$temp['media_medium'] = html_entity_decode($ele->attributes()['medium']->__toString());
						if (!empty($temp))
							$updates['media_array'][] = $temp;
						break;
					// categories are weird, there can be many, & not in a group, but parallel elements...
					// atom categories use a "term" attribute...
					case 'category':
						if (isset($ele->attributes()['term']))
							$updates['categories'][] = html_entity_decode($ele->attributes()['term']->__toString());
						else
							$updates['categories'][] = html_entity_decode($ele->__toString());
						break;
					// Ignore these tags, no action:
					// Comments brings you automatically to post-reply...  Seems funky, user may not even be a member...
					case 'comments':
					// image_link is part of the google standard, & typically reflects an image within description.  Don't duplicate it...
					case 'image_link':
					// post-id is typically a copy of the guid, but in a different format, duplicate...
					case 'post-id':
					// commentRss is typically a copy of the main media link, duplicate...
					case 'commentrss':
						break;
					default:
						// Will display it as info, but log it to review...
						$updates[$key_lower] = $ele->__toString();
						if ($this->learn_mode_enabled())
							$this->report_error('Learn Mode unknown item element: ' . $key . ':');
				}
			}
		}

		// Find an ID... If not guid, then id, if not, then link...
		// Need a unique identifier to prevent recurring posts...
		if (!array_key_exists('guid', $updates))
		{
			if (array_key_exists('id', $updates))
				$updates['guid'] = $updates['id'];
			elseif (array_key_exists('link', $updates))
				$updates['guid'] = $updates['link'];
			else
				if ($this->learn_mode_enabled())
					$this->report_error('Learn Mode no guid in item:');
		}

		// Make sure it hasn't been posted before...
		// If pub date from current source > last proc date, proceed, otherwise bail.
		$publication_time = false;
		if (!empty($updates['pub_date']))
			$publication_time = strtotime($updates['pub_date']);
		if ($publication_time === false)
			$publication_time = time();
		if ((int) $this->_details['smf_last_proc_time'] > $publication_time)
			return;

		// Also check if guid has been posted...
		if (guid_exists($this->_details['id_channel'], htmlspecialchars($updates['guid'])))
			return;

		// Format post subject & body info...
		// Note that some feeds, notably Mastodon, don't really have titles, so just gotta cobble one from the prefix & date...
		$msgOptions['subject'] = $this->_details['smf_topic_prefix'] . ' ' . htmlspecialchars(!empty($updates['title']) ? $updates['title'] : (!empty($updates['pub_date']) ? $this->clean_localize_time($updates['pub_date']) : ''));
		$msgOptions['body'] = $this->format_post_body($updates);

		$topicOptions['board'] = $this->_details['smf_board'];

		// Figure out what to say about the poster...
		// If an ID is provided in the channel configuration, use that; if not, use feed info to build a guest post.
		if (empty($this->_details['smf_id_member']))
		{
			$posterOptions['id'] = 0;
			$posterOptions['name'] = htmlspecialchars(!empty($updates['managing_editor']) ? $updates['managing_editor'] : (!empty($default_author) ? $default_author : ''));
			$posterOptions['email'] = '';
		}
		else
		{
			$member_info = get_poster_info($this->_details['smf_id_member']);
			$posterOptions['id'] = $this->_details['smf_id_member'];
			$posterOptions['name'] = $member_info['real_name'];
			$posterOptions['email'] = $member_info['email_address'];
		}
		// Could plug IP from the server, but that'd not really be associated with the poster...
		// IP of user that triggered cron would be wrong, too, and confusing/deceiving... So...
		$posterOptions['ip'] = '0.0.0.0';

		// Post...
		require_once($sourcedir . '/Subs-Post.php');
		require_once($sourcedir . '/Logging.php');
		if (!createPost($msgOptions, $topicOptions, $posterOptions))
		{
			$this->report_error('rssagg_err_cannot_post');
			return;
		}	

		// Set guid, to avoid posting this again.
		// $msgOptionsp['id'] should include new message id upon successful post.
		guid_add($this->_details['id_channel'], htmlspecialchars($updates['guid']), $msgOptions['id']);
	}

	/**
	 * Format post body.
	 *
	 * Raw text at the top; media in the middle; tagline at the bottom.
	 *
	 * @params array $updates
	 *
	 * @return string $post_body
	 */
	private function format_post_body($updates)
	{
		global $txt, $smcFunc;

		$post_body = '';

		// Raw text content at the top...
		if (!empty($updates['title'])) $post_body .= $updates['title'] . "\n\n";
		if (!empty($updates['summary'])) $post_body .= $updates['summary'] . "\n\n";
		if (!empty($updates['encoded'])) $post_body .= $updates['encoded'] . "\n\n";

		// Sometimes encoded = description (news orgs, e.g., LAT)
		// If so, don't duplicate the info...
		if (!empty($updates['description']) && (empty($updates['encoded']) || (!empty($updates['encoded']) && ($updates['description'] != $updates['encoded']))))
			$post_body .= $updates['description'] . "\n\n";

		// Media in the middle...
		// If yt_id found, treat as a YouTube channel...
		if (!empty($updates['yt_id']))
		{
			if (!empty($updates['media_description'])) $post_body .= $updates['media_description'] . "\n\n";
			$post_body .= '[youtube]' . $updates['yt_id'] . '[/youtube]' . "\n\n";

			// Oddly, the YT channel url, which we want in the tagline, is in the author/managing editor uri field...
			// On YT posts, the alt link is just a variant of the media link, which we don't want in the tagline.
			unset($updates['link_alt']);
		}
		else
		{
			// Media enclosures & groups
			if (!empty($updates['media_thumbnail'])) $post_body .= $updates['media_thumbnail'] . "\n\n";
			if (!empty($updates['media_description'])) $post_body .= $updates['media_description'] . "\n\n";
			if (!empty($updates['media_title'])) $post_body .= $updates['media_title'] . "\n\n";
			if (!empty($updates['media_content'])) $post_body .= $updates['media_content'] . "\n\n";

			// Now for those (potentially multiple) media content tags
			if (!empty($updates['media_array']) && is_array($updates['media_array']))
			{
				foreach ($updates['media_array'] AS $content)
				{
					// If we can put an image out there, do it...
					if (!empty($content['media_type']) && (mb_stripos($content['media_type'], 'image') !== false))
					{
						if (!empty($content['media_url']))
							$post_body .= '[img]' . $content['media_url'] . '[/img]' . "\n\n";
					}
					else
					{
						if (!empty($content['media_url']))
							$post_body .= $content['media_url'] . "\n\n";
					}
				}
			}
		}

		// Put in a line feed for spacing, so its obvious tagline is different... Use br to ensure it won't get stripped later...
		$post_body .= '[br]';

		// At the bottom, we'll put any info found about the channel, link & author...
		if (!empty($updates['categories'])) $post_body .= $txt['rssagg_categories'] . ': ' . implode(', ', $updates['categories']) . "\n";

		// If tagline info is found, spit it out...  This will point to the original post & post time.
		// There's a pecking order here... If the link is available, use that.
		// If not, use the alt link, which often has the source (e.g,. SMF uses that).
		// If alt link not available, use the managing editor uri, which is often the author's page (e.g., for YouTube channels).
		// Note that SMF uses the author/managing-editor-uri for poster's profile, which we do not want...
		// Fortunately alt-link will be found first.
		$tagline = '';
		if (!empty($updates['link']))
			$tagline .= $updates['link'] . ' ';
		elseif (!empty($updates['link_alt']))
			$tagline .= $updates['link_alt'] . ' ';
		elseif (!empty($updates['managing_editor_uri']))
			$tagline .= $updates['managing_editor_uri'] . ' ';

		if (!empty($updates['pub_date']))
			$tagline .= $this->clean_localize_time($updates['pub_date']);
		elseif (!empty($updates['updated']))
			$tagline .= $this->clean_localize_time($updates['updated']);
		
		if (!empty($tagline))
			$post_body .= $txt['rssagg_source'] . ': ' . $tagline . "\n";

		// If in learn mode...  Dump everything in key/value pairs...
		// This helps debug/diagnose layout & completeness.
		if ($this->learn_mode_enabled())
		{
			$post_body .= '[hr][hr]';
			foreach ($updates as $key => $value)
			{
				if (!empty($value))
				{
					switch ($key)
					{
						case 'categories':
							$post_body .= '[b]' . $key . '[/b]: ' . implode(', ', $value) . "\n";
							break;
						case 'media_array':
							foreach ($value AS $info)
							{
								foreach ($info AS $content_key => $content_value)
								{
									$post_body .= '[b]' . $content_key . '[/b]: ' . $content_value . "\n";
								}
							}
							break;
						default:
							$post_body .= '[b]' . $key . '[/b]: ' . $value . "\n";
					}
				}
			}
		}

		// strip_tags() confuses "<-" with comments... 
		// "<-" deletes the remainder of any post... It is treated as a start of a comment...
		$post_body = preg_replace(['~<-~'], ['&lt;-'], $post_body);

		// Nuke html tags, except for link, img, and iframe tags...
		$post_body = strip_tags($post_body, ['a', 'img', 'iframe']);

		// Kill mid-line tabs, leading & trailing spaces
		$patterns = array('~[ \t]+$~m', '~^[ \t]+~m', '~[ \t]+~');
		$replacements = array('', '', ' ');
		$post_body = preg_replace($patterns, $replacements, $post_body);

		// Cleanup gobs & gobs of newlines...  2 max for nice spacing.
		$post_body = preg_replace('~\n{2,}~', "\n\n", trim($post_body));

		// Cleaning up links takes a bit of work, use a callback...
		$post_body = preg_replace_callback('~<a\s[^>]*href=\"([^\"]+)\"[^>]*>([^<]*)<\/a>~', array('RSSAggregator_Background', 'clean_anchor_callback'), $post_body);

		// Anchors without href, who knew? I could have made prior regex more fancy, but this is easier to grok...
		$post_body = preg_replace('~<a\s[^>]*>[^<]*<\/a>~', '', $post_body);

		// Cleaning up img tags takes a bit of work, use a callback...
		$post_body = preg_replace_callback('~<img\s[^>]*src=\"([^\"]+)\"[^>]*>~', array('RSSAggregator_Background','clean_img_callback'), $post_body);

		// Cleaning up iframe tags takes a bit of work, use same callback as used for anchors...
		$post_body = preg_replace_callback('~<iframe\s[^>]*src=\"([^\"]+)\"[^>]*>[^<]*</iframe>~', array('RSSAggregator_Background','clean_anchor_callback'), $post_body);

		// Finally, some of these are huge, truncate anything that won't fit...  No double-encoding (comments, above...)...
		$post_body = mb_substr(htmlspecialchars($post_body, ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401, null, false), 0, 65535);

		return $post_body;
	}

	/**
	 * clean_anchor_callback.
	 * Far too many ways to format an anchor, so do it via logic here...
	 * This way, we can also add bbc tags for youtube, soundcloud, etc.
	 * The little space at the end stops things from being run together...
	 *
	 * For now, I am assuming youtube & soundcloud are supported.
	 * I spent a fair amount of time trying to find out if certain BBC were active,
	 * but gave up.  List isn't published anywhere, it's in code only.  Then you can
	 * have adds by mods.  Then you have a set of disabled bbc - which isn't honored
	 * in tasks run by cron here.  I disabled to test, but bbc was still honored...
	 * Not impossible, but bigger than it should be.
	 *
	 * @params array $matches
	 *
	 * @return string $clean_anchor
	 */
	private function clean_anchor_callback($matches)
	{
		$new_link = '';

		// $matches[0] is whole match...  $matches[1] is the url.  $matches 2 is text.
		$matches[0] = empty($matches[0]) ? '' : trim($matches[0]);
		$matches[1] = empty($matches[1]) ? '' : trim($matches[1]);
		$matches[2] = empty($matches[2]) ? '' : trim($matches[2]);
		$url = empty($matches[1]) ? '' : $matches[1];
		$text = empty($matches[2]) ? $url : $matches[2];

		// Youtube?
		if ($this->yt_id_found($url))
			$new_link = '[youtube]' . $this->yt_id_found($url) . '[/youtube] ';
		// Soundcloud?
		elseif (mb_stripos($url, 'soundcloud.com') !== false)
			$new_link = '[soundcloud]' . $url . '[/soundcloud] ';
		// Generic...
		else
			$new_link = '[url=' . $url . ']' . $text . '[/url] ';

		return $new_link;
	}

	/**
	 * clean_img_callback.
	 * Far too many ways to format an img, so do it via logic here...
	 * This way, we can also check for ridiculous dimensions...
	 * The little space at the end stops things from being run together...
	 *
	 * @params array $matches
	 *
	 * @return string $clean_img
	 */
	private function clean_img_callback($matches)
	{
		$new_link = '';

		// $matches[0] is whole match...  $matches[1] is the src.
		$matches[0] = empty($matches[0]) ? '' : trim($matches[0]);
		$matches[1] = empty($matches[1]) ? '' : trim($matches[1]);
		$src = empty($matches[1]) ? '' : $matches[1];

		//Problem is we don't know what order these are in...
		$width = preg_match('~width=\"(\d+)\"~', $matches[0], $w_matches) ? (int) $w_matches[1] : 0;
		$height = preg_match('~height=\"(\d+)\"~', $matches[0], $h_matches) ? (int) $h_matches[1] : 0;

		// Make sure image doesn't exceed 800x600.  Hard coded for now, may parameterize it later.
		// Lots of pix in tests were HUGE...
		$new_width = (int) $width;
		$new_height = (int) $height;
		if ($width > 800)
		{
			$new_height = (int) ($height * 800 / $width);
			$new_width = (int) 800;
		}

		if ($new_height > 600)
		{
			$new_width = (int) ($new_width * 600 / $new_height);
			$new_height = (int) 600;
		}

		// It doesn't seem to like it when you specify height in responsive/smartphone mode...  SMF bug?
		// Temp fix for now, may keep it because it works great...  Just toss the height...
		$new_height = (int) 0;

		$new_link = '[img' . (empty($new_width) ? '' : ' width=' . $new_width) . (empty($new_height) ? '' : ' height=' . $new_height) . ']' . $src . '[/img] ';

		return $new_link;
	}

	/**
	 * yt_id_found.
	 *
	 * Inspects a url string for a youtube video ID.
	 * Properly finds the IDs in all 185 yt url formats delineated here:
	 * https://gist.github.com/rodrigoborgesdeoliveira/987683cfbfcc8d800192da1e73adc486
	 *
	 * @params string $url
	 *
	 * @return false|string $yt_id if found, false if not
	 */
	private function yt_id_found($url)
	{
		$yt_id = false;
		$matches = array();

		if (preg_match('~(?:youtube\.com|youtu\.be|youtube-nocookie\.com).*(?:/|\?v=|&v=|\?v%3D|%3Fv%3D)([\-_0-9A-Za-z]{11})~i', $url, $matches) == 1)
			$yt_id = $matches[1];

		return $yt_id;
	}

	/**
	 * clean_localize_time.
	 *
	 * If it can, this will read a text string with date & time, e.g., "Wed, 11 Sep 2024 13:25:29 +0000", and produce
	 * a more human-friendly string for users.  The raw text is ugly.  Note also that smf_strftime also applies locale, if possible.
	 *
	 * @params string $datetime
	 *
	 * @return string $datetime
	 */
	private function clean_localize_time($datetime)
	{
		// First, convert to unix time, to normalize everything...
		$epoch = strtotime($datetime);

		// Now use SMF's local-aware string formatting...
		// Since this is embedded in posts, we CANNOT use things like "Today".
		// Since this is in cron, we need to ensure to use the forum time formatting...
		$datetime = timeformat($epoch, false, 'forum');

		return $datetime;
	}

	/**
	 * learn_mode_enabled.
	 *
	 * If learn mode setting not empty, return true.
	 * This helps identify new tags that may be need to be handled.
	 * Placing an entry in the settings table makes it easy to turn 
	 * this behavior off & on without changing code.
	 *
	 * @return bool $enabled
	 */
	private function learn_mode_enabled()
	{
		global $modSettings;

		return !empty($modSettings['rssagg_learn_mode']);
	}
}

?>
