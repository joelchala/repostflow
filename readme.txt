=== RePostFlow ===
Contributors: chalatico07
Tags: social media, auto share, repost, evergreen, scheduler
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically reshare your evergreen WordPress content to 10 social networks on a smart schedule.

== Description ==

**RePostFlow** automatically picks your existing WordPress content and reshares it to several social networks on a customizable schedule. Give new life to your older posts and keep a steady flow of traffic from social media, on autopilot.

It is ideal for sites with evergreen content (games, tutorials, recipes, timeless articles) that you want to keep visible on social media without manual effort.

= Main features =

* **Smart post selection** - Picks published posts at random with a cooldown period to avoid duplicates
* **10 social networks** - X/Twitter, Facebook, Instagram, LinkedIn, Telegram, Pinterest, Threads, Bluesky, Discord, Mastodon
* **Customizable schedules** - From every 2 hours to once a week
* **Template engine** - Per-network messages with variables such as {title}, {url}, {hashtags}
* **Image support** - Automatically attaches the post's featured image
* **UTM tracking** - Optional UTM parameters for analytics
* **Queue system** - View and manage upcoming shares
* **Share history** - Full log with the status of every share
* **Dashboard widget** - Quick overview from the WordPress dashboard
* **Extensible** - Hooks and filters for developers
* **Secure** - API credentials are encrypted, nonce checks on all actions

= Supported networks =

* **X (Twitter)** - API v2 with OAuth 1.0a, text and image
* **Facebook** - Graph API, posts to Pages with a link or photo
* **Instagram** - Business accounts through the Graph API (image required)
* **LinkedIn** - Personal profiles and company pages
* **Telegram** - Bot API for channels and groups, HTML formatting
* **Pinterest** - Pins images to boards (image required)
* **Threads** - Meta's Threads through the Graph API
* **Bluesky** - AT Protocol network with text and image support
* **Discord** - Channel integration through webhooks (simplest setup)
* **Mastodon** - Federated network, works with any instance

= How it works =

1. Connect your social accounts under Settings → Networks
2. Set your posting schedule and post selection rules
3. Customize the message template for each network
4. Enable auto-sharing and RePostFlow does the rest

= Extensible =

Developers can add custom networks with the `rpf_register_networks` action and change behavior with filters such as `rpf_post_query_args`, `rpf_template_variables` and `rpf_should_share_post`.

= Use cases =

* **Gaming sites** - Reshare games automatically every day
* **Tutorial blogs** - Keep technical content visible
* **Recipe sites** - Periodically share older recipes
* **News portals** - Drive traffic to important archives
* **E-commerce** - Promote evergreen products

= External services =

RePostFlow connects to the social networks you enable, and only those. Nothing is sent until you add your credentials for a network and enable it. Each request is made when a scheduled share runs, or when you press "Test connection" in the settings. In every case the plugin sends the credentials you entered (to authenticate) and the content of the post being shared: its text built from your template (title, excerpt, URL, hashtags, author), the permalink (with optional UTM parameters) and the featured image when the network accepts one. No visitor data is sent.

* **X (Twitter)** - api.twitter.com and upload.twitter.com. [Terms](https://x.com/en/tos), [Privacy](https://x.com/en/privacy)
* **Facebook and Instagram** - graph.facebook.com. [Terms](https://www.facebook.com/terms.php), [Privacy](https://www.facebook.com/privacy/policy/)
* **Threads** - graph.threads.net. [Terms](https://help.instagram.com/769983657850450), [Privacy](https://help.instagram.com/515230437301944)
* **LinkedIn** - api.linkedin.com. [Terms](https://www.linkedin.com/legal/user-agreement), [Privacy](https://www.linkedin.com/legal/privacy-policy)
* **Telegram** - api.telegram.org. [Terms](https://telegram.org/tos), [Privacy](https://telegram.org/privacy)
* **Pinterest** - api.pinterest.com. [Terms](https://policy.pinterest.com/en/terms-of-service), [Privacy](https://policy.pinterest.com/en/privacy-policy)
* **Bluesky** - bsky.social, or the PDS server you configure. [Terms](https://bsky.social/about/support/tos), [Privacy](https://bsky.social/about/support/privacy-policy)
* **Discord** - discord.com (webhook URL you provide). [Terms](https://discord.com/terms), [Privacy](https://discord.com/privacy)
* **Mastodon** - the instance URL you configure (for example mastodon.social). Terms and privacy are set by each instance.

== Installation ==

1. Upload the `repostflow` folder to `/wp-content/plugins/`
2. Activate the plugin from the "Plugins" menu in WordPress
3. Go to **RePostFlow → Settings** to configure your networks
4. Enable auto-sharing and enjoy an automatic social media presence

== Frequently Asked Questions ==

= Does it work with custom post types? =

Yes. You can select any public post type on the General settings tab.

= How does smart selection work? =

RePostFlow picks published posts at random while respecting a cooldown period. It will not share the same post again within the configured period (default: 7 days). You can also set a minimum post age and include or exclude specific categories.

= Do I need API keys for each network? =

Yes. Each social network requires API credentials. RePostFlow gives instructions in the settings panel for each network. Credentials are encrypted before they are stored.

= Will it share the same post twice? =

Not within the cooldown period. The plugin tracks every share in a log and excludes recently shared posts from selection.

= Does it work with WordPress cron? =

Yes, it uses WP-Cron. For best reliability we recommend setting up a real server cron job that runs `wp-cron.php` every 15 minutes.

= What happens if a share fails? =

The error is recorded in the history with its message. You can see the details under RePostFlow → History.

= Can I use my own message template? =

Yes. Each network has its own template that you can customize with variables such as `{title}`, `{url}`, `{excerpt}`, `{hashtags}`, `{author}`, etc.

== Screenshots ==

1. Main dashboard with statistics, connected networks and recent activity
2. Settings page with network configuration panels
3. Share history with filters and pagination
4. Queue management page

== Changelog ==

= 1.1.0 =
* NEW: Pinterest support (image required, API v5)
* NEW: Threads support
* NEW: Bluesky support (AT Protocol)
* NEW: Discord webhook support (simplest setup)
* NEW: Mastodon support (federated, any instance)
* FIX: Corrected the LinkedIn class name
* FIX: SQL queries are now prepared, including table names
* FIX: Board ID validation for Pinterest
* FIX: Request input is unslashed and sanitized
* IMPROVEMENT: Character limits for all networks
* IMPROVEMENT: Default templates for all networks
* IMPROVEMENT: Better credential validation
* Requires WordPress 6.2 or later

= 1.0.0 =
* Initial release
* Support for X/Twitter, Facebook, Instagram, LinkedIn and Telegram
* Smart post selection with cooldown and category filters
* Customizable message templates per network
* Queue system and share history
* Dashboard widget
* UTM tracking support
* Encrypted credential storage

== Upgrade Notice ==

= 1.1.0 =
Major update with 5 new social networks: Pinterest, Threads, Bluesky, Discord and Mastodon. Please re-save your network settings after updating. Requires WordPress 6.2 or later.

= 1.0.0 =
First release of RePostFlow. Connect your social accounts and start sharing automatically.

== Support ==

To report bugs or request features, visit: https://github.com/joelchala/repostflow/issues
