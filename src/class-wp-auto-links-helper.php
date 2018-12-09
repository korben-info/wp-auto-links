<?php

class WP_Auto_Links_Helper
{
    const DOMAIN = 'wp_auto_links';

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

    public function __construct()
    {
        // Fetch options
        $this->get_options();

        add_action('create_category', [$this, 'delete_cache']);
        add_action('edit_category', [$this, 'delete_cache']);
        add_action('edit_post', [$this, 'delete_cache']);
        add_action('save_post', [$this, 'delete_cache']);
        add_action('admin_menu', function () {
            add_options_page(
                'Auto Links',
                'Auto Links',
                'manage_options',
                basename(__FILE__),
                [$this, 'handle_options']
            );
        });

        foreach ($types as $type => $recurrence) {
            $this->register_hook($type, $recurrence);
        }

        // Bootstrap the tracker
        $this->bootstrap();
    }

    public function __get(string $name)
    {
        return wp_cache_get($name, self::DOMAIN);
    }

    public function __set(string $name, array $data)
    {
        wp_cache_set($name, $data, self::DOMAIN, 86400);
    }

    public function __unset(string $name)
    {
        wp_cache_delete($name, self::DOMAIN);
    }

    protected function bootstrap(): void
    {
        $options = $this->get_options();
        if ($options) {
            if ($options['post'] || $options['page']) {
                add_filter('the_content', [$this, 'filter'], 10);
            }
            if ($options['comment']) {
                add_filter('comment_text', [$this, 'filter'], 10);
            }
        }
    }

    protected function register_hook($name, $recurrence)
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
    public function activate()
    {
        $this->set_options($this->get_default_options());
    }

    /**
     * Deactivation hook.
     */
    public function deactivate()
    {
        delete_option(self::DOMAIN);
    }

    /**
     * Set helper options.
     *
     * @param array $options The linker options to use.
     */
    public function set_options(array $options)
    {
        $this->options = $options;
        update_option(self::DOMAIN, $options);
    }

    /**
     * Get helper options.
     *
     * @return mixed
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
    public function get_default_options(): array
    {
        return [
            'post' => true,
            'postself' => '',
            'page' => true,
            'pageself' => '',
            'comment' => '',
            'excludeheading' => true,
            'lposts' => true,
            'lpages' => true,
            'lcats' => '',
            'ltags' => '',
            'ignore' => 'about',
            'ignorepost' => 'contact',
            'links_count_max' => 3,
            'maxsingle' => 1,
            'minusage' => 1,
            'links' => '',
            'links_preventduplicatelink' => false,
            'links_url' => '',
            'links_url_value' => '',
            'links_url_datetime' => '',
            'nofolow' =>'',
            'blank' =>'',
            'onlysingle' => true,
            'casesens' =>'',
            'allowfeed' => '',
            'maxsingleurl' => '1'
        ];
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get_option(string $name)
    {
        // TODO: catch missing
        return $this->options[$name];
    }

    /**
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

    protected function is_relevant(): bool
    {
        // Exclude admin
        if (is_admin()) {
            return false;
        }

        // Exclude feeds
        if (!$this->get_option('enable_feed') && is_feed()) {
            return false;
        }

        // Exclude archives and home
        if ($this->get_option('only_single') && !(is_single() || is_page())) {
            return false;
        }

        // Exclude posts and/or pages
        $post_type = get_post_type();
        if ($post_type === 'post' && !$this->get_option('enable_posts')) {
            return false;
        }
        if ($post_type === 'page' && !$this->get_option('enable_pages')) {
            return false;
        }

        // Exclude blacklist
        $ignore_posts = array_map('trim', explode(',', $this->get_option('ignorepost')));
        if (is_page($ignore_posts) || is_single($ignore_posts)) {
            return false;
        }

        return true;
    }
}
