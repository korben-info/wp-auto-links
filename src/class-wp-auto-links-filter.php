<?php

class WP_Auto_Links_Filter
{
    /**
     * @var WP_Auto_Links_Helper
     */
    private $helper;

    /**
     * @var WP_Auto_Links_Link[]
     */
    private $links = [];

    /**
     * @var string|void
     */
    private $blog_url;

    /**
     * @var int
     */
    private $links_available;

    /**
     * WP_Auto_Links_Filter constructor.
     *
     * @param WP_Auto_Links_Helper $helper
     */
    public function __construct(WP_Auto_Links_Helper $helper)
    {
        $this->helper = $helper;
        $this->blog_url = get_bloginfo('url');
        $this->links_available = $this->max_links ?: -1;
    }

    /**
     * Helps to get an option value.
     *
     * @param string $name Option name.
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->helper->get_option($name);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function add_spec_char(string $str): string
    {
        $split = str_split($str);
        return implode('<!---->', $split);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function remove_spec_char(string $str): string
    {
        $split = explode('<!---->', $str);
        $str = implode('', $split);
        return stripslashes($str);
    }

    /**
     * @param string $b
     * @return string
     */
    protected function match_case(string $b): string
    {
        return $this->case_sensitive ? $b : strtolower($b);
    }

    /**
     * Handle a preg match an apply the link.
     *
     * @param array $matches
     * @return string
     */
    protected function handle_match(array $matches): string
    {
        $match = array_pop($matches);
        $link = false;

        foreach ($this->links as $potential_link) {
            if ($potential_link->has_keyword($match)) {
                $link = $potential_link;
                break;
            }
        }

        if (!$link) {
            return $match;
        }
        if (!$link->increment()) {
            return $match;
        }

        if ($this->links_available > 0) {
            $this->links_available--;
        }

        $prepend = '';
        if (stripos($this->blog_url, $link->url) !== 0) {
            $prepend = 'rel="';
            if ($this->link_nofollow) {
                $prepend .= 'nofollow';
            }
            if ($this->link_blank) {
                $prepend .= ' noopener" target="_blank';
            }
            $prepend .= '" ';
        }
        return "<a {$prepend}href=\"" . $link->url . "\">{$match}</a>";
    }


    /**
     * Process text provided by the filter.
     *
     * May alter the content.
     *
     * @param string $text The content.
     * @return string
     */
    public function process(string $text): string
    {
        if (!$this->on_heading) {
            $text = preg_replace_callback('|(<h\d[^>]*>)((?:(?!<\/h).)*)(<\/h\d>)|si', function ($matches) {
                return $matches[1] . $this->add_spec_char($matches[2]) . $matches[3];
            }, $text);
        }

        $text = preg_replace_callback(
            array_map(function ($ignore) {
                return "|$ignore|";
            }, $this->keyword_ignore),
            function ($matches) {
                return $this->add_spec_char($matches[0]);
            },
            $text
        );

        $active_types = array_filter(array_keys($this->helper::TYPES), function ($type) {
            return $this->helper->get_option("{$type}_enable");
        });

        foreach ($active_types as $type) {
            if (!$this->links = $this->helper->$type) {
                continue;
            }

            $strpos = $this->case_sensitive ? 'stripos' : 'strpos';
            $this->links = array_filter($this->links, function (WP_Auto_Links_Link $link) use ($text, $strpos) {
                foreach ($link->keywords as $keyword) {
                    if ($strpos($text, $keyword) !== false) {
                        return true;
                    }
                }
                return false;
            });

            if (empty($this->links)) {
                continue;
            }

            $search = array_reduce($this->links, function (string $carry, WP_Auto_Links_Link $link) {
                return ($carry ? $carry . '|'  : '') . implode('|', $link->keywords);
            }, '');
            $text = preg_replace_callback("%(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))\b($search)\b%", [$this, 'handle_match'], $text, $this->links_available);

            if ($this->links_available === 0) {
                break;
            }
        }

        $text = preg_replace_callback(
            array_map(function ($ignore) {
                return "|{$this->add_spec_char($ignore)}|";
            }, $this->keyword_ignore),
            function ($matches) {
                return $this->remove_spec_char($matches[0]);
            },
            $text
        );

        if (!$this->on_heading) {
            $text = preg_replace_callback('|(<h\d[^>]*>)((?:(?!<\/h).)*)(<\/h\d>)|si', function ($matches) {
                return $matches[1] . $this->remove_spec_char($matches[2]) . $matches[3];
            }, $text);
            $text = stripslashes($text);
        }

        return $text;
    }
}
