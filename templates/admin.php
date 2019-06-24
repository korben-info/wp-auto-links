<?php

/**
 * Admin page
 */

$helper  = WP_Auto_Links_Helper::get_instance();
$options = $helper->get_options();

$boolean_options = [
    // Content handle
    'on_post',
    'on_post_self',
    'on_page',
    'on_page_self',
    'on_comment',
    'on_heading',
    'on_feed',
    'on_archive',

    // Data source
    'keywords_enable',
    'posts_enable',
    'pages_enable',
    'categories_enable',
    'tags_enable',

    // HTML behavior
    'link_nofollow',
    'link_blank',

    // Config
    'case_sensitive',
    'prevent_duplicate_link',
];

$integer_options = [
    'max_links',
    'max_single_keyword',
    'max_single_url',
    'min_term_usage',
    'min_post_age',
];

if (isset($_POST['submitted'])) {
    check_admin_referer($helper::DOMAIN);

    foreach ($boolean_options as $option_name) {
        $options[$option_name] = (isset($_POST[$option_name]) && !empty($_POST[$option_name])) ? (bool) $_POST[$option_name] : false;
    }

    foreach ($integer_options as $option_name) {
        $val = (isset($_POST[$option_name]) && !empty($_POST[$option_name])) ? $_POST[$option_name] : 0;
        $options[$option_name] = is_numeric($val) ? (int) $val : 1;
    }

    $options['keywords'] = strip_tags($_POST['keywords']);
    $options['keyword_ignore'] = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['keyword_ignore']))));
    $options['post_ignore'] = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['post_ignore']))), 'is_numeric');
    $options['term_ignore'] = array_filter(array_map('trim', explode(',', sanitize_text_field($_POST['term_ignore']))), 'is_numeric');

    $helper->set_options($options);

    echo '<div class="updated"><p>' . __('Plugin settings saved.', $helper::DOMAIN) . '</p></div>';
}

foreach ($boolean_options as $option_name) {
    $options[$option_name] = $options[$option_name] ? 'checked' : '';
}

$options['keyword_ignore'] = implode(',', $options['keyword_ignore']);
$options['post_ignore'] = implode(',', $options['post_ignore']);
$options['term_ignore'] = implode(',', $options['term_ignore']);

array_walk($options, 'esc_attr');

$options['keywords'] = stripslashes($options['keywords']);

$options['max_links'] = $helper::option_integer($options['max_links']);
$options['max_single_keyword'] = $helper::option_integer($options['max_single_keyword'], -1);
$options['max_single_url'] = $helper::option_integer($options['max_single_url']);
$options['min_term_usage'] = $helper::option_integer($options['min_term_usage'], 1);
$options['min_post_age'] = $helper::option_integer($options['min_post_age']);

?>

<div class="wrap">
    <h1>Auto Links</h1>

    <form method="post" action="<?= $_SERVER['REQUEST_URI']; ?>">
        <input type="hidden" name="option_page" value="discussion">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?= wp_create_nonce($helper::DOMAIN); ?>"/>
        <input type="hidden" name="submitted" value="1"/>

        <h2 class="title"><?php _e('Keywords'); ?></h2>
        <p><?php _e('Which custom keywords to automatically link.', $helper::DOMAIN); ?></p>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="keywords">
                        <?php _e('Keywords'); ?>
                    </label>
                </th>
                <td>
                    <textarea name="keywords" id="keywords" rows="10" cols="90"><?= $options['keywords']; ?></textarea>
                    <p class="description">
                        <?php _e('Use comma to separate keywords and add target url at the end. Use a new line for new url and set of keywords. You can have these keywords link to any url, not only your site.', $helper::DOMAIN); ?>
                    </p>
                    <code>example,auto links,https://example.com</code><br/>
                    <code>wiki,wikipedia,https://en.wikipedia.org/wiki/Main_Page</code>
                </td>
            </tr>
            <tr>
                <th><?php _e('Duplicates', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="prevent_duplicate_link" <?= $options['prevent_duplicate_link']; ?> />
                        <strong><?php _e('Prevent a link to be used more than once in a content', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="title"><?php _e('Placements', $helper::DOMAIN); ?></h2>
        <p><?php _e('Where to automatically add links.', $helper::DOMAIN); ?></p>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php _e('Posts'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="on_post" <?= $options['on_post']; ?>/>
                        <strong><?php _e('Enable to link in posts'); ?></strong>
                    </label>
                    <br/>
                    <label>
                        <input type="checkbox" name="on_post_self" <?= $options['on_post_self']; ?>/>
                        <strong><?php _e('Enable self linking for posts', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php _e('Pages'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="on_page" <?= $options['on_page']; ?>/>
                        <strong><?php _e('Enable to link in pages'); ?></strong>
                    </label>
                    <br/>
                    <label>
                        <input type="checkbox" name="on_page_self" <?= $options['on_page_self']; ?>/>
                        <strong><?php _e('Enable self linking for pages', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php _e('Comments'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="on_comment" <?= $options['on_comment']; ?> />
                        <strong><?php _e('Enable to link in comments'); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Feeds', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="on_feed" <?= $options['on_feed']; ?> />
                        <strong><?php _e('Enable to link in feeds', $helper::DOMAIN); ?></strong>
                    </label>
                    <p class="description">

                    </p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Archives', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="on_archive" <?= $options['on_archive']; ?>/>
                        <strong><?php _e('Enable to link on archive and index pages (including home page)', $helper::DOMAIN); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Heading', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="on_heading" <?= $options['on_heading']; ?>/>
                        <strong><?php _e('Enable to link in heading tags (h1, h2, h3, h4, h5 and h6)', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="title"><?php _e('Targeting', $helper::DOMAIN); ?></h2>
        <p><?php _e('What to automatically link.', $helper::DOMAIN); ?></p>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php _e('Keywords'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="keywords_enable" <?= $options['keywords_enable']; ?> />
                        <strong><?php _e('Enable to link links with custom keywords', $helper::DOMAIN); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Posts'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="posts_enable" <?= $options['posts_enable']; ?> />
                        <strong><?php _e('Enable to link internal links to posts', $helper::DOMAIN); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Pages'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="pages_enable" <?= $options['pages_enable']; ?> />
                        <strong><?php _e('Enable to link internal links to pages', $helper::DOMAIN); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Categories'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="categories_enable" <?= $options['categories_enable']; ?> />
                        <strong><?php _e('Enable to link internal links to categories', $helper::DOMAIN); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Tags'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="tags_enable" <?= $options['tags_enable']; ?> />
                        <strong><?php _e('Enable to link internal links to tags', $helper::DOMAIN); ?></strong>
                    </label>
                    <?= $helper->may_slow_down(); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="min_term_usage">
                        <?php _e('Minimum categories / tags', $helper::DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="min_term_usage" id="min_term_usage" size="2" value="<?= $options['min_term_usage']; ?>"/>
                    <p class="description">
                        <?php _e('Only link categories and tags that have been used the above number of times or more.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="title"><?php _e('Excluding', $helper::DOMAIN); ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php _e('Exclude Keywords', $helper::DOMAIN); ?></th>
                <td>
                    <input type="text" name="keyword_ignore" size="90"
                           value="<?= $options['keyword_ignore']; ?>" placeholder="<?php _e('Add a word', $helper::DOMAIN); ?>"/>
                    <p class="description">
                        <?php _e('You may wish to ignore certain words or phrases from automatic linking. Separate them by comma.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Exclude Posts / Pages', $helper::DOMAIN); ?></th>
                <td>
                    <input type="text" name="post_ignore" size="90"
                           value="<?= $options['post_ignore']; ?>" placeholder="<?php _e('Add an ID'); ?>"/>
                    <p class="description">
                        <?php _e('You may wish to forbid automatic linking on certain posts or pages. Separate their ID by a comma.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Exclude Categories / Tags', $helper::DOMAIN); ?></th>
                <td>
                    <input type="text" name="term_ignore" size="90"
                           value="<?= $options['term_ignore']; ?>" placeholder="<?php _e('Add an ID', $helper::DOMAIN); ?>"/>
                    <p class="description">
                        <?php _e('You may wish to forbid automatic linking on certain categories or tags. Separate their ID by a comma.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="title"><?php _e('Options'); ?></h2>
        <p><?php _e('How automatically link', $helper::DOMAIN); ?></p>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="max_links">
                        <?php _e('Max Links', $helper::DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="max_links" id="max_links" size="2" value="<?= $options['max_links']; ?>"/>
                    <p class="description">
                        <?php _e('You can limit the maximum number of different links that will be generated per post. Set to 0 for no limit.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="max_single_keyword">
                        <?php _e('Max Keyword Links', $helper::DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="max_single_keyword" id="max_single_keyword" size="2" value="<?= $options['max_single_keyword']; ?>"/>
                    <p class="description">
                        <?php _e('You can limit the maximum number of links created with the same keyword. Set to 0 for no limit.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="max_single_url">
                        <?php _e('Max Same URLs', $helper::DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="max_single_url" id="max_single_url" size="2" value="<?= $options['max_single_url']; ?>"/>
                    <p class="description">
                        <?php _e('Limit number of same URLs the plugin will link to. Works only when Max Keyword Links above is set to 1. Set to 0 for no limit.', $helper::DOMAIN); ?>
                    </p>
                </td>
            </tr>
            <tr>
              <th>
                <label for="min_post_age">
                    <?php _e('Min post age', $helper::DOMAIN); ?>
                </label>
              </th>
              <td>
                <input type="number" name="min_post_age" id="min_post_age" size="2" value="<?= $options['min_post_age']; ?>"/>
                <p class="description">
                    <?php _e('Limit linking on posts older than this minimum age. Value in days', $helper::DOMAIN); ?>
                </p>
              </td>
            </tr>
            <tr>
                <th><?php _e('Case sensitive', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="case_sensitive" <?= $options['case_sensitive']; ?> />
                        <strong><?php _e('Enable case sensitivity', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php _e('No follow', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="link_nofollow" <?= $options['link_nofollow']; ?>/>
                        <strong><?php _e('Add a nofollow attribute to the external links.', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php _e('Open in new window', $helper::DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="link_blank" <?= $options['link_blank']; ?>/>
                        <strong><?php _e('Open the external links in a new window.', $helper::DOMAIN); ?></strong>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
