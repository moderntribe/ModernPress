<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\CLI;

use Throwable;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\Command;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Contracts\Svg_Store;
use Tribe\Alert_Scoped\WP_CLI;
use WP_Query;
use function WP_CLI\Utils\get_flag_value;
class Svg_Command extends Command
{
    public const OPTION_TASK = 'task';
    public const OPTION_ADD = 'add';
    public const OPTION_REMOVE = 'remove';
    public const FLAG_YES = 'yes';
    protected string $meta_key;
    protected Svg_Store $svg_store;
    public function __construct(string $meta_key, Svg_Store $svg_store)
    {
        parent::__construct();
        $this->meta_key = $meta_key;
        $this->svg_store = $svg_store;
    }
    protected function command() : string
    {
        return 'svg store';
    }
    protected function description() : string
    {
        return \__('Add or remove SVG markup storage to existing attachments', 'tribe');
    }
    protected function arguments() : array
    {
        return [['type' => self::OPTION, 'name' => self::OPTION_TASK, 'optional' => \false, 'description' => \__('Add or remove SVG markup meta to all SVG attachments in the database.', 'tribe'), 'options' => [self::OPTION_ADD, self::OPTION_REMOVE]], ['type' => self::FLAG, 'name' => self::FLAG_YES, 'description' => \__('Skip all confirmations.', 'tribe'), 'optional' => \true]];
    }
    public function run_command($args, $assoc_args)
    {
        $task = get_flag_value($assoc_args, self::OPTION_TASK);
        $skip_confirmation = get_flag_value($assoc_args, self::FLAG_YES, \false);
        if ($task === self::OPTION_ADD) {
            $this->add_markup($skip_confirmation);
        } else {
            $this->remove_markup($skip_confirmation);
        }
    }
    protected function add_markup(bool $skip_confirmation)
    {
        $query = new WP_Query(['post_type' => 'attachment', 'post_mime_type' => 'image/svg+xml', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids']);
        if (!$query->posts) {
            WP_CLI::warning(\__('No SVGs found in the database.', 'tribe'));
            return;
        }
        if (!$skip_confirmation) {
            WP_CLI::confirm(\sprintf(\__('This will add SVG markup meta for "%d" SVG attachment(s). Continue?', 'tribe'), $query->post_count));
        }
        foreach ($query->posts as $id) {
            WP_CLI::debug(\sprintf(\__('Adding SVG markup to attachment: %d', 'tribe'), $id));
            $file = \get_attached_file($id);
            if (!$file) {
                WP_CLI::debug(\sprintf(\__('No attached file for attachment with ID: %d. Skipping...', 'tribe'), $id));
                continue;
            }
            try {
                if (!$this->svg_store->save($file, $id)) {
                    WP_CLI::warning(\sprintf(\__('Error storing SVG markup for attachment ID: %d', 'tribe'), $id));
                }
            } catch (Throwable $e) {
                WP_CLI::debug($e->getMessage() . ': ' . $e->getTraceAsString());
                WP_CLI::warning(\sprintf(\__('An error occurred processing attachment ID "%d. Rerun the command with the --debug option', 'tribe'), $id));
            }
        }
        WP_CLI::success(\sprintf(\__('Finished processing "%d" attachments', 'tribe'), $query->post_count));
    }
    protected function remove_markup(bool $skip_confirmation)
    {
        global $wpdb;
        if (!$skip_confirmation) {
            WP_CLI::confirm(\sprintf(\__('Delete all meta keys in the postmeta table with the key of "%s"?', 'tribe'), $this->meta_key));
        }
        $count = $wpdb->delete($wpdb->postmeta, ['meta_key' => $this->meta_key], ['%s']);
        if ($count === \false) {
            WP_CLI::error(\__('Deleting SVG markup from attachments failed.', 'tribe'));
        } else {
            WP_CLI::success(\sprintf(\__('Deleted "%d" records.', 'tribe'), $count));
        }
    }
}
