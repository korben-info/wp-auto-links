<?php

class WP_Auto_Links_Filter
{
    private $helper;

    private $links = [];

    private $blog_url;
    private $links_available = 0;

    public function __construct(WP_Auto_Links_Helper $helper)
    {
        $this->helper = $helper;
        $this->blog_url = get_bloginfo('url');
        $this->links_available = $this->links_max_count ?: -1;
    }

    public function __get(string $name)
    {
        return $this->helper->get_option($name);
    }

    protected function add_spec_char(string $str): string
    {
        $strarr = str_split($str);
        return implode('<!---->', $strarr);
    }

    protected function remove_spec_char(string $str): string
    {
        $strarr = explode('<!---->', $str);
        $str = implode('', $strarr);
        return stripslashes($str);
    }

    protected function match_case(string $b): string
    {
        return $this->casesens ? $b : strtolower($b);
    }

    protected function handle_match($matches): string
    {
        $link = array_shift(array_filter($this->links, function (WP_Auto_Links_Link $link) use ($matches): bool {
            return $link->has_keyword($matches);
        }));
        if (!$link) {
            return $matches;
        }
        if (!$link->increment()) {
            return $matches;
        }

        if ($this->links_available > 0) {
            $this->links_available--;
        }

        $prepend = '';
        if (stripos($this->blog_url, $link->url) !== 0) {
            if ($this->blank) {
                $prepend .= 'target="_blank" ';
            }
            if ($this->nofollow) {
                $prepend .= 'rel="nofollow" ';
            }
        }
        return "<a ${prepend}href=\"" . $link->url . "\">${matches}</a>";
    }


    /**
     * @param string $text
     * @return string
     */
    public function process(string $text): string
    {
        if ($this->excludeheading) {
            $text = preg_replace_callback('/(<h\d[^>]*>)(.*)(</h\d>)/si', function ($matches) {
                return $matches[1] . $this->add_spec_char($matches[2]) . $matches[3];
            }, $text);
        }

        foreach ($this->ignores as $ignore) {
            // TODO: fix and check case
            $text = preg_replace_callback($ignore, function ($matches) {
                return $this->add_spec_char($matches[0]);
            }, $text);
        }

        foreach ($this->types as $type) {
            if (!$this->links = $this->helper->fetch_type($type)) {
                continue;
            }

            $strpos = $this->casesens ? 'stripos' : 'strpos';
            $this->links = array_filter($this->links, function (WP_Auto_Links_Link $link) use ($text, $strpos) {
                return $strpos($link->keyword, $text) !== false;
            });

            if (empty($this->links)) {
                continue;
            }

            $search = array_reduce($this->links, function (string $carry, WP_Auto_Links_Link $link) {
                return $carry . '|' . implode('|', $link->keyword);
            }, '');
            preg_replace_callback("%(?!(?:[^<\[]+[>\]]|[^>\]]+<\/a>))\b($search)\b%", [$this, 'handle_match'], $text, $this->links_available);
        }

        foreach ($this->ignores as $ignore) {
            $text = preg_replace_callback($this->add_spec_char($ignore), function ($matches) {
                return $this->remove_spec_char($matches[0]);
            }, $text);
        }

        if ($this->excludeheading) {
            $text = preg_replace_callback('/(<h\d[^>]*>)(.*)(</h\d>)/si', function ($matches) {
                return $matches[1] . $this->remove_spec_char($matches[2]) . $matches[3];
            }, $text);
            $text = stripslashes($text);
        }

        return $text;
    }
}
