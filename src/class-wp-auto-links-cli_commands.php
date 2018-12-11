<?php

/**
 * Auto Links commands.
 *
 * ## EXAMPLES
 *
 *     # Build links for insertion.
 *     $ wp redis status
 */
class WP_Auto_Links_CLI_Commands extends WP_CLI_Command
{
    /**
     * Build links for insertion.
     *
     * ## OPTIONS
     *
     * <type>
     * : The name of the link type to build.
     *
     * ## EXAMPLES
     *
     *     wp auto-links build posts
     */
    public function build($args): void
    {
        list($type) = $args;

        try {
            WP_Auto_Links_Builder::$type();
            \WP_CLI::success("Links build for $type!");
        } catch (\Exception $exception) {
            \WP_CLI::error($exception->getMessage());
        }
    }

    /**
     * Clean links saved.
     *
     * ## OPTIONS
     *
     * <type>
     * : The name of the link type to clean.
     *
     * ## EXAMPLES
     *
     *     wp auto-links clean tags
     */
    public function clean($args): void
    {
        list($type) = $args;

        $helper = WP_Auto_Links_Helper::get_instance();

        try {
            unset($helper->$type);
            \WP_CLI::success("Links cleaned for $type!");
        } catch (\Exception $exception) {
            \WP_CLI::error($exception->getMessage());
        }
    }

    /**
     * Status.
     *
     * ## EXAMPLES
     *
     *     wp auto-links status
     */
    public function status(): void
    {
        $helper = WP_Auto_Links_Helper::get_instance();

        foreach (array_keys($helper::TYPES) as $type) {
            $links = $helper->$type;
            if ($links) {
                \WP_CLI::line("Count of $type links: " . count($links));
            } else {
                \WP_CLI::line("No $type links.");
            }
        }
    }
}
