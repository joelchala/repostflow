# RePostFlow — Dedicated Agent Instructions

## Agent Identity & Role
You are the **dedicated development agent for RePostFlow**, a WordPress plugin that automatically reshares evergreen content to social media networks on a smart schedule. You are responsible for all development, bug fixes, feature additions, testing, and optimization of this plugin.

## Project Location
- **Plugin root**: `C:\xampp\htdocs\wordpress\wp-content\plugins\repostflow\`
- **WordPress root**: `C:\xampp\htdocs\wordpress\`
- **Production site**: https://juegosonlinerd.com/ (user handles FTP deployment to Hostinger)

## CRITICAL RULES
1. **NEVER create .zip files** — the user works directly with the plugin folder and uploads via FTP
2. **NEVER modify WordPress core files** — only work within the plugin directory
3. **ALWAYS follow WordPress Coding Standards** (WPCS)
4. **ALWAYS use proper security**: nonce verification, capability checks, sanitization, escaping
5. **ALL user-facing strings must use i18n functions** (`__()`, `_e()`, `esc_html__()`, etc.) with text domain `repostflow`
6. **Prefix all functions, classes, and hooks with `rpf_` or `RPF_`**
7. **NEVER store API credentials in plain text** — always use `RPF_Encryption` class
8. **ALWAYS use `$wpdb->prepare()` for database queries**
9. **Communication language**: Spanish (Latin American) with the user, English in code/comments

## Plugin Architecture

### Core Design Pattern
- **Singleton pattern** for main `RePostFlow` class
- **Interface + Abstract + Concrete** for network adapters
- **WordPress hooks** for all integrations (no direct function calls between components)
- **Settings stored in** `wp_options` as serialized array under key `rpf_settings`
- **Share log stored in** custom table `{prefix}rpf_share_log`
- **WP-Cron** for scheduled execution

### Directory Structure
```
repostflow/
├── repostflow.php                    # Main bootstrap (constants, requires, hooks)
├── uninstall.php                     # Clean removal of all plugin data
├── readme.txt                        # WordPress.org format
├── CLAUDE.md                         # This file
│
├── includes/
│   ├── class-repostflow.php          # Singleton orchestrator - holds all components
│   ├── class-rpf-activator.php       # Activation: create table, set defaults, schedule cron
│   ├── class-rpf-deactivator.php     # Deactivation: clear cron hooks
│   ├── class-rpf-encryption.php      # AES-256-CBC encryption using WordPress AUTH_KEY
│   ├── class-rpf-logger.php          # CRUD for rpf_share_log table + stats
│   ├── class-rpf-template-engine.php # Variable replacement: {title}, {url}, {hashtags}, etc.
│   ├── class-rpf-post-selector.php   # Smart random selection with cooldown + category filters
│   ├── class-rpf-queue-manager.php   # Queue operations: enqueue, dequeue, mark status
│   ├── class-rpf-scheduler.php       # WP-Cron management + main process() pipeline
│   │
│   ├── networks/
│   │   ├── interface-rpf-network.php   # Contract: get_slug, publish, validate, get_settings_fields
│   │   ├── abstract-rpf-network.php    # Shared: http_post, http_get, http_post_json, success/error helpers
│   │   ├── class-rpf-network-registry.php # Registry: register, get_all, get_configured, get_by_slug
│   │   ├── class-rpf-twitter.php       # X/Twitter API v2 + OAuth 1.0a signature
│   │   ├── class-rpf-facebook.php      # Facebook Graph API v18 (Page feed + photos)
│   │   ├── class-rpf-instagram.php     # Instagram Graph API (2-step: container → publish)
│   │   ├── class-rpf-linkedin.php      # LinkedIn v2 API (ugcPosts)
│   │   └── class-rpf-telegram.php      # Telegram Bot API (sendMessage + sendPhoto)
│   │
│   └── admin/
│       ├── class-rpf-admin.php         # Menu registration + page rendering + save_settings
│       ├── class-rpf-settings.php      # Settings API registration
│       ├── class-rpf-ajax-handler.php  # AJAX: test_network, share_now, clear_queue, delete_log, toggle
│       ├── class-rpf-dashboard-widget.php # WP Dashboard widget
│       └── class-rpf-list-table.php    # Placeholder for future WP_List_Table
│
├── templates/admin/
│   ├── dashboard-page.php      # Stats cards + networks + recent activity
│   ├── settings-page.php       # 4 tabs: General, Schedule, Networks, Advanced
│   ├── queue-page.php          # Pending items management
│   └── history-page.php        # Share log with filters + pagination
│
├── assets/
│   ├── css/rpf-admin.css       # Admin UI styles (cards, badges, panels, responsive)
│   └── js/rpf-admin.js         # jQuery: accordion, AJAX calls, toggle, share now
│
└── languages/                  # i18n files (future)
```

### Database Schema
**Table: `{prefix}rpf_share_log`**
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT AUTO_INCREMENT | Primary key |
| post_id | BIGINT | WordPress post ID |
| network | VARCHAR(50) | Network slug (twitter, facebook, etc.) |
| status | VARCHAR(20) | queued, processing, published, failed, skipped |
| message | TEXT | Rendered message sent to network |
| image_url | VARCHAR(500) | Featured image URL |
| response | TEXT | JSON API response |
| external_id | VARCHAR(255) | Post ID on external network |
| external_url | VARCHAR(500) | URL on external network |
| scheduled_at | DATETIME | When scheduled to post |
| shared_at | DATETIME | When actually posted |
| created_at | DATETIME | Record creation time |

### Settings Structure (`rpf_settings` option)
```php
[
    'general' => [
        'enabled' => bool,
        'post_types' => ['post'],
        'categories_include' => [],
        'categories_exclude' => [],
        'min_post_age_days' => 0,
        'cooldown_days' => 7,
        'posts_per_run' => 1,
    ],
    'schedule' => [
        'frequency' => 'daily', // or rpf_every_2_hours, rpf_every_4_hours, etc.
    ],
    'networks' => [
        'twitter' => ['enabled' => bool, 'api_key' => encrypted, 'template' => string, ...],
        'facebook' => ['enabled' => bool, 'page_id' => string, 'page_token' => encrypted, ...],
        // ... each network has its own settings
    ],
    'advanced' => [
        'include_image' => true,
        'utm_enabled' => false,
        'utm_source' => 'repostflow',
        'utm_medium' => 'social',
        'utm_campaign' => '',
        'log_retention_days' => 90,
    ],
]
```

### Processing Pipeline (rpf_process_queue cron)
```
1. Check if enabled → exit if not
2. Process existing queued items first
3. Get all configured networks
4. Use RPF_Post_Selector to pick random post(s)
5. For each post × each network:
   a. Get network template
   b. Render message via RPF_Template_Engine
   c. Get featured image URL
   d. Enqueue via RPF_Queue_Manager
6. Process queue:
   a. Dequeue next item
   b. Mark as processing
   c. Call network->publish()
   d. Mark published or failed
   e. Fire rpf_after_share_success or rpf_after_share_failure
```

### Template Variables
`{title}`, `{url}`, `{raw_url}`, `{excerpt}`, `{author}`, `{date}`, `{categories}`, `{tags}`, `{hashtags}`, `{site_name}`, `{site_url}`, `{newline}`

### Network Interface Contract
Every network adapter must implement:
- `get_slug()` → unique identifier
- `get_label()` → display name
- `get_icon()` → SVG string
- `get_color()` → hex color
- `is_configured()` → bool (has valid credentials)
- `publish($post_id, $message, $image_url)` → ['success' => bool, 'external_id', 'external_url', 'error']
- `get_settings_fields()` → array of field definitions
- `validate_credentials()` → ['valid' => bool, 'message']

### Extensibility Hooks
**Filters:**
- `rpf_registered_networks` — add custom networks
- `rpf_post_query_args` — modify post selection WP_Query
- `rpf_template_variables` — add custom template variables
- `rpf_message_before_publish` — modify message before sending
- `rpf_should_share_post` — allow/deny specific shares

**Actions:**
- `rpf_register_networks` — register additional network adapters
- `rpf_after_process` — after scheduled run completes
- `rpf_after_share_success` — after successful share
- `rpf_after_share_failure` — after failed share

## Current Status (v1.0.0)

### Completed ✅
- Full plugin architecture with OOP design
- 5 network adapters (Twitter, Facebook, Instagram, LinkedIn, Telegram)
- Smart post selector with cooldown and category filters
- Template engine with 12 variables
- WP-Cron scheduler with 9 frequency options
- Queue manager with status tracking
- Admin UI: Dashboard, Settings (4 tabs), Queue, History pages
- AJAX handlers: test connection, share now, clear queue, delete log, toggle
- Dashboard widget for WordPress admin
- API credential encryption (AES-256-CBC)
- CSS responsive design
- Uninstall cleanup
- readme.txt for WordPress.org

### Pending / Roadmap 🔜
- **Pinterest adapter** (class-rpf-pinterest.php) — needs image, v5 API
- **TikTok adapter** (class-rpf-tiktok.php) — complex, video-focused
- **OAuth flow in-plugin** — currently users paste tokens manually
- **Preview before publish** — show how post will look per network
- **Bulk retry failed** — retry all failed shares
- **Email notifications** — notify admin on failures
- **WP_List_Table implementation** — replace simple tables in queue/history
- **REST API endpoints** — for headless/external integrations
- **Multisite support** — network-wide activation
- **Rate limit tracking** — respect API rate limits per network
- **A/B testing templates** — test different message formats
- **Analytics integration** — clicks, engagement tracking
- **Shortcodes** — [rpf_recent_shares] for frontend display
- **POT file generation** — translation template
- **Unit tests** — PHPUnit test suite
- **Character counter** — real-time in template textarea
- **Duplicate container ID protection** for Adsterra-like networks

## How To Add a New Network

1. Create `includes/networks/class-rpf-{network}.php`
2. Extend `RPF_Network_Abstract` and implement `RPF_Network_Interface`
3. Implement all interface methods
4. Register in `RePostFlow::register_networks()` in `class-repostflow.php`
5. Add `require_once` in `repostflow.php`

## Production Context
- **Website**: https://juegosonlinerd.com/ — Free online games portal (Dominican Republic/Latin America)
- **Hosting**: Hostinger
- **Content**: ~200+ game posts with thumbnails (MyArcadePlugin Pro)
- **Primary use case**: Auto-post 1 random game daily to X, Facebook, Instagram
- **Related plugin**: gamefuga-seo-booster (SEO optimization, same developer)
- **Related theme**: Game Fuga (custom game portal theme)

## Useful Commands for Development
```bash
# Check PHP syntax of all plugin files
find "C:\xampp\htdocs\wordpress\wp-content\plugins\repostflow" -name "*.php" -exec php -l {} \;

# Count lines of code
find "C:\xampp\htdocs\wordpress\wp-content\plugins\repostflow" -name "*.php" -o -name "*.css" -o -name "*.js" | xargs wc -l

# List all files
find "C:\xampp\htdocs\wordpress\wp-content\plugins\repostflow" -type f | sort
```
