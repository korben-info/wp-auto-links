<?php

class WP_Auto_Links_Builder
{
    protected static function add($array, $url, $keywords)
    {
        $helper = WP_Auto_Links_Helper::get_instance();
        if ($helper->links_preventduplicatelink &&
            !empty(array_filter($array, function (WP_Auto_Links_Link $link) use ($url) {
                return $link->url === $url;
            }))) {
            return;
        }
        array_push($array, new WP_Auto_Links_Link($url, $keywords, $helper->links_max_single));
    }


    public static function keywords()
    {
        $helper = WP_Auto_Links_Helper::get_instance();

        $links = [];
        foreach (explode("\n", $helper->links) as $line) {
            $chunks = array_filter(array_map('trim', explode(',', $line)));
            if (count($chunks) < 2) {
                continue;
            }
            $url = array_pop($chunks);
            if (!empty($helper->ignores)) {
                $chunks = array_filter($chunks, function ($keyword) {
                    return in_array($keyword, $helper->ignores);
                });
                if (empty($chunks)) {
                    continue;
                }
            }
            self::add($links, $url, $chunks);
        }
        $helper->keywords = $links;
    }

    public static function posts()
    {
        global $wpdb;

        $helper = WP_Auto_Links_Helper::get_instance();
        $ignore = implode(',', $helper->ignores);

        $query = <<<SQL
SELECT  `ID`, `post_title` FROM `$wpdb->posts` 
WHERE `post_status` = 'publish' 
  AND `post_type` IN ()
  AND CHAR_LENGTH(`post_title`) BETWEEN 3 AND 20
  AND `ID` NOT IN ($ignore)
ORDER BY CHAR_LENGTH(`post_title`) DESC LIMIT 1000
SQL;
        $posts = $wpdb->get_results($query);

        $links = [];
        foreach ($posts as $post) {
            self::add($links, get_permalink($post->ID), $post->post_title);
        }
        $helper->posts = $links;
    }

    public static function categories()
    {
        global $wpdb;

        $helper = WP_Auto_Links_Helper::get_instance();
        $ignore = implode(',', $helper->ignores);

        // Categories
        $query = <<<SQL
SELECT `$wpdb->terms`.`term_id`, `$wpdb->terms`.`name` FROM `$wpdb->terms`
LEFT JOIN `$wpdb->term_taxonomy` ON `$wpdb->terms`.`term_id` = `$wpdb->term_taxonomy`.`term_id`
WHERE `$wpdb->term_taxonomy`.`taxonomy` = 'category'
  AND CHAR_LENGTH(`$wpdb->terms`.`name`) BETWEEN 3 AND 20
  AND `$wpdb->term_taxonomy`.`count` >= $minusage
  AND `$wpdb->terms`.`term_id` NOT IN ($ignore)
ORDER BY CHAR_LENGTH(`$wpdb->terms`.`name`) DESC LIMIT 1000
SQL;
        $categories = $wpdb->get_results($query);

        $links = [];
        foreach ($categories as $category) {
            self::add($links, get_category_link($category->term_id), $category->name);
        }
        $helper->categories = $links;
    }

    public static function tags()
    {
        global $wpdb;

        $helper = WP_Auto_Links_Helper::get_instance();
        $ignore = implode(',', $helper->ignores);

        // Tags
        $query = <<<SQL
SELECT `$wpdb->terms`.`term_id`, `$wpdb->terms`.`name` FROM `$wpdb->terms` 
LEFT JOIN `$wpdb->term_taxonomy` ON `$wpdb->terms`.`term_id` = `$wpdb->term_taxonomy`.`term_id`
WHERE `$wpdb->term_taxonomy`.`taxonomy` = 'post_tag' 
  AND CHAR_LENGTH(`$wpdb->terms`.`name`) BETWEEN 3 AND 20
  AND `$wpdb->term_taxonomy`.`count` >= $minusage
  AND `$wpdb->terms`.`term_id` NOT IN ($ignore)
ORDER BY LENGTH(`$wpdb->terms`.`name`) DESC LIMIT 1000
SQL;
        $tags = $wpdb->get_results($query);

        $links = [];
        foreach ($tags as $tag) {
            self::add($links, get_tag_link($tag->term_id), $tag->name);
        }
        $helper->tags = $links;
    }
}
