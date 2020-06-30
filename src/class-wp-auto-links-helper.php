<?php

class WP_Auto_Links_Helper
{
    const DOMAIN = 'wp_auto_links';

    const TYPES = [
        'keywords' => 'daily',
        'posts' => 'hourly',
        'pages' => 'daily',
        'categories' => 'twicedaily',
        'tags' => 'twicedaily'
    ];

    /**
     * Holds the class instance.
     *
     * @var self
     */
    private static $instance;

    /**
     * Holds the helper options.
     *
     * @var array
     */
    private $options;

    /**
     * WP_Auto_Links_Helper constructor.
     */
    public function __construct()
    {
        // Fetch options
        $this->get_options();

        foreach (self::TYPES as $type => $recurrence) {
            if ($this->get_option("{$type}_enable")) {
                $this->register_hook($type, $recurrence);
            }
        }

        if (is_admin()) {
            // add_action('create_category', [$this, 'delete_cache']);
            // add_action('edit_category', [$this, 'delete_cache']);
            // add_action('edit_post', [$this, 'delete_cache']);
            // add_action('save_post', [$this, 'delete_cache']);

            add_action('admin_menu', function () {
                add_options_page(
                    'Auto Links',
                    'Auto Links',
                    'manage_options',
                    self::DOMAIN,
                    [$this, 'show_options']
                );
            });
        } else {
            $this->register_filters();
        }
    }

    /**
     * Get an array of links from store.
     *
     * @param string $name The links type.
     * @return bool|WP_Auto_Links_Link[]
     * @throws Exception
     */
    public function __get(string $name)
    {
        if (!in_array($name, array_keys(self::TYPES))) {
            throw new Exception("Trying to get links for an undefined type ($name).");
        }

        return wp_cache_get($name, self::DOMAIN);
    }

    /**
     * Set an array of links into store.
     *
     * @param string $name The links type.
     * @param WP_Auto_Links_Link[] $data The array of links.
     * @throws Exception
     */
    public function __set(string $name, array $data): void
    {
        if (!in_array($name, array_keys(self::TYPES))) {
            throw new Exception("Trying to set links for an undefined type ($name).");
        }

        wp_cache_set($name, $data, self::DOMAIN, 86400);
    }

    /**
     * Delete an array of links in store.
     *
     * @param string $name The links type.
     * @throws Exception
     */
    public function __unset(string $name)
    {
        if (!in_array($name, array_keys(self::TYPES))) {
            throw new Exception("Trying to delete links for an undefined type ($name).");
        }

        wp_cache_delete($name, self::DOMAIN);
    }

    /**
     * Register filters.
     */
    protected function register_filters(): void
    {
        if ($this->get_option('on_post') || $this->get_option('on_page')) {
            add_filter('the_content', [$this, 'filter'], 10);
        }
        if ($this->get_option('on_comment')) {
            add_filter('comment_text', [$this, 'filter'], 10);
        }
    }

    /**
     * Register a cron hook.
     *
     * @param string $name The links type.
     * @param string $recurrence The cron recurrence.
     */
    protected function register_hook(string $name, string $recurrence)
    {
        add_action(self::DOMAIN . '_' . $name, [WP_Auto_Links_Builder::class, $name]);
        if (!wp_next_scheduled(self::DOMAIN . '_' . $name)) {
            wp_schedule_event(time(), $recurrence, self::DOMAIN . '_' . $name);
        }
    }

    /**
     * Get the helper instance.
     *
     * @return self
     */
    public static function get_instance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }

    /**
     * Activation hook.
     */
    public static function activate(): void
    {
        update_option(self::DOMAIN, self::get_default_options());
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate(): void
    {
        foreach (self::TYPES as $type => $recurrence) {
            wp_cache_delete($type, self::DOMAIN);
        }
        delete_option(self::DOMAIN);
    }

    /**
     * Set helper options.
     *
     * @param array $options The linker options to use.
     */
    public function set_options(array $options): void
    {
        $this->options = $options;
        update_option(self::DOMAIN, $options);
    }

    /**
     * Get helper options.
     *
     * @return array|bool
     */
    public function get_options()
    {
        if (empty($this->options)) {
            $this->options = get_option(self::DOMAIN);
        }

        return $this->options;
    }

    /**
     * Get helper default options.
     *
     * @return array
     */
    public static function get_default_options(): array
    {
        return [
            // Data
            'keywords' => '',

            // Content handle
            'on_post' => true,
            'on_post_self' => false,
            'on_page' => true,
            'on_page_self' => false,
            'on_comment' => false,
            'on_heading' => false,
            'on_feed' => false,
            'on_archive' => false,

            // Data source
            'keywords_enable' => true,
            'posts_enable' => true,
            'pages_enable' => true,
            'categories_enable' => false,
            'tags_enable' => false,

            // HTML behavior
            'link_nofollow' => true,
            'link_blank' => false,

            // Config
            'case_sensitive' => false,
            'prevent_duplicate_link' => false,

            // Limits
            'max_links' => 3,
            'max_single_keyword' => 1,
            'max_single_url' => 1,
            'min_term_usage' => 1,
            'min_post_age' => 0,

            // Ignore
            'keyword_ignore' => ['about'],
            'post_ignore' => [1],
            'term_ignore' => []
        ];
    }

    /**
     * Get an option value.
     *
     * @param string $name The option name.
     * @return mixed
     */
    public function get_option(string $name)
    {
        return $this->options[$name] ?? self::get_default_options()[$name];
    }

    /**
     * Print admin page.
     */
    public function show_options()
    {
        include dirname(__DIR__) . '/templates/admin.php';
    }

    /**
     * @param mixed $value
     * @param int $null
     * @return int
     */
    public static function option_integer($value, int $null = 0): int
    {
        return (is_numeric($value) && $value > 0) ? (int) $value : $null;
    }

    /**
     * Instantiate the filter.
     *
     * @param string $text
     * @return string
     */
    public function filter(string $text): string
    {
        if (!$this->is_relevant()) {
            return $text;
        }

        $filter = new WP_Auto_Links_Filter(self::$instance);

        return $filter->process($text);
    }

    /**
     * Check if the filter usage is relevant.
     *
     * @return bool
     */
    protected function is_relevant(): bool
    {
        // Exclude feeds
        if (!$this->get_option('on_feed') && is_feed()) {
            return false;
        }

        // Exclude archives and home
        if (!$this->get_option('on_archive') && !(is_single() || is_page())) {
            return false;
        }

        $post_type = get_post_type();
        // Exclude posts and/or pages
        if ($post_type === 'post') {
            if (!$this->get_option('on_post')) {
                return false;
            }
            // Exclude too young posts
            if (
                $this->get_option('min_post_age') > 0 &&
                (time() - get_post_time() < $this->get_option('min_post_age') * (24 * 60 * 60))
            ) {
                return false;
            }
        } else if ($post_type === 'page' && !$this->get_option('on_page')) {
            return false;
        }

        // Exclude blacklist
        $ignore_posts = $this->get_option('post_ignore');
        if (!empty($ignore_posts) && (is_page($ignore_posts) || is_single($ignore_posts))) {
            return false;
        }

        return true;
    }

    /**
     * Print a notice that the option may slow down the site.
     *
     * @return string
     */
    public function may_slow_down(): string
    {
        return '<p class="description"><strong>⚠️ ' . __('May slow down performance.', self::DOMAIN) . '</strong></p>';
    }
}
