<?php

class WP_Auto_Links_Builder
{
    /**
     * Stack links into an array.
     *
     * @param array $array The array to push on.
     * @param string $url The link URL.
     * @param array $keywords The link keywords.
     */
    protected static function add(array &$array, string $url, array $keywords): void
    {
        $helper = WP_Auto_Links_Helper::get_instance();
//        if ($helper->get_option('prevent_duplicate_link') &&
//            !empty(array_filter($array, function (WP_Auto_Links_Link $link) use ($url) {
//                return $link->url === $url;
//            }))) {
//            return;
//        }
        array_push($array, new WP_Auto_Links_Link($url, $keywords, $helper->get_option('max_single_url')));
    }


    /**
     * Build custom-keywords links.
     */
    public static function keywords(): void
    {
        $helper = WP_Auto_Links_Helper::get_instance();

        if (!$helper->get_option("keywords_enable")) {
            return;
        }

        $links = [];
        foreach (explode("\n", $helper->get_option('keywords')) as $line) {
            $chunks = array_filter(array_map('trim', explode(',', $line)));
            if (count($chunks) < 2) {
                continue;
            }
            $url = array_pop($chunks);
            if (!empty($ignore = $helper->get_option('keyword_ignore'))) {
                $chunks = array_filter($chunks, function ($keyword) use ($ignore) {
                    return !in_array($keyword, $ignore);
                });
                if (empty($chunks)) {
                    continue;
                }
            }
            self::add($links, $url, $chunks);
        }

        $helper->keywords = $links;
    }

    /**
     * @param string $type Single type: post or page.
     */
    protected static function singles(string $type): void
    {
        global $wpdb;

        $helper = WP_Auto_Links_Helper::get_instance();

        if (!$helper->get_option("{$type}s_enable")) {
            return;
        }

        $ignore = implode(',', $helper->get_option('post_ignore'));
        if ($ignore) {
            $ignore = "AND `ID` NOT IN ($ignore)";
        }

        $query = <<<SQL
SELECT  `ID`, `post_title` FROM `$wpdb->posts` 
WHERE `post_status` = 'publish'
  AND `post_type` = '$type'
  AND CHAR_LENGTH(`post_title`) BETWEEN 3 AND 50
  $ignore
ORDER BY CHAR_LENGTH(`post_title`) DESC LIMIT 1000;
SQL;
        $posts = $wpdb->get_results($query);

        $links = [];
        foreach ($posts as $post) {
            self::add($links, get_permalink($post->ID), [$post->post_title]);
        }

        $helper->$type = $links;
    }

    /**
     * Build posts links.
     */
    public static function posts(): void
    {
        self::singles('post');
    }

    /**
     * Build page links.
     */
    public static function pages(): void
    {
        self::singles('page');
    }

    /**
     * @param string $type Term type: tag or categorie (not category!).
     */
    protected static function terms(string $type): void
    {
        global $wpdb;

        $helper = WP_Auto_Links_Helper::get_instance();
        if (!$helper->get_option("{$type}s_enable")) {
            return;
        }

        $ignore = implode(',', $helper->get_option('term_ignore'));
        if ($ignore) {
            $ignore = "AND `$wpdb->terms`.`term_id` NOT IN ($ignore)";
        }

        $query_type = $type === 'tag' ? 'post_tag' : 'category';
        $min_usage = $helper->get_option('min_term_usage');
        $query = <<<SQL
SELECT `$wpdb->terms`.`term_id`, `$wpdb->terms`.`name` FROM `$wpdb->terms`
LEFT JOIN `$wpdb->term_taxonomy` ON `$wpdb->terms`.`term_id` = `$wpdb->term_taxonomy`.`term_id`
WHERE `$wpdb->term_taxonomy`.`taxonomy` = '$query_type'
  AND CHAR_LENGTH(`$wpdb->terms`.`name`) BETWEEN 3 AND 30
  AND `$wpdb->term_taxonomy`.`count` >= $min_usage
  $ignore
ORDER BY CHAR_LENGTH(`$wpdb->terms`.`name`) DESC LIMIT 1000;
SQL;
        $terms = $wpdb->get_results($query);

        $links = [];
        foreach ($terms as $term) {
            self::add($links, get_category_link($term->term_id), [$term->name]);
        }

        $helper->$type = $links;
    }

    /**
     * Build categories links.
     */
    public static function categories(): void
    {
        self::terms('categorie');
    }

    /**
     * Build tags links.
     */
    public static function tags(): void
    {
        self::terms('tag');
    }
}
