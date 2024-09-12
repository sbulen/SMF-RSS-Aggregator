# Description
The RSS Aggregator lets you add RSS feeds to your SMF forum.  The idea is to provide a very simple, media-focused feed, in order to allow SMF forums to dip their toes into the fediverse.

It supports two types of feeds:
 - Traditional RSS feeds, following the standard RSS & atom formats
 - YouTube channel feeds, which are similar

Mastodon feeds:  Note that each Mastodon account has an RSS feed - just add ".rss" to the account page.  E.g., where you see a user handle of "@ShawnBulen@techhub.social", the server is the "techhub.social" part, so that account's page is "https://techhub.social/@ShawnBulen".  The rss feed for that handle is: "https://techhub.social/@ShawnBulen.rss".

YouTube channels:  Note that you can find the YouTube channel feed url by viewing the page source while on the YouTube channel within your browser.  Just search for "rss", you'll find it.  It's buried in there in some script, not visible on the page, but it's there.  It will look like "https://www.youtube.com/feeds/videos.xml?channel_id=UCVyx4HhDowwspssrwuNeTZg"

Every new item found in a feed results in a new topic in the specified board.

Where YouTube or SoundCloud links are found, they are embedded in corresponding BBC.  This mod assumes you have both enabled and supported in your forum.

Posts can be associated with a member, if desired, to be able to associate an avatar to the channel, and to provide a handle on the posts.  Otherwise, posts are treated as guest posts.

A tagline is provided at the bottom of each post with a link to the source and the initial publication date, if available.

## Limitations
 - Only the admin can setup subscriptions.
 - No feedback data is included, e.g., comments, ratings, statistics.  It would conflict with forum feedback, just sticking to one set.
 - This will not update existing posts.  Updates may conflict with forum discussions.
 - If id/guid are not unique, duplicate posts may result.  Not all feeds provide guids or even publication dates on their items.  There is A LOT of effort here to avoid dupes by providing proxy guids, but some may still occur.
 - Mastodon cards are not supported, which is a shame.  Mastodon & other fediverse feeds use cards as a generic mechanism for associating media with a formatted background image & some predefined action/url.  The problem here is that the card details are not in the rss feeds, only the core media url.
 - xml parse errors and warnings are suppressed, and a generic single "cannot parse" error is logged in SMF.  If you encounter this, odds are you aren't pointing to an actual rss feed...
