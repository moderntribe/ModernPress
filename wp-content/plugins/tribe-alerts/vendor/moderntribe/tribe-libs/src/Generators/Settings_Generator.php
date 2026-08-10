<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

use Tribe\Alert_Scoped\WP_CLI;
class Settings_Generator extends Generator_Command
{
    private $slug = '';
    private $class_name = '';
    public function description()
    {
        return \__('Generate a settings page.', 'tribe');
    }
    public function command()
    {
        return 'generate settings';
    }
    public function arguments()
    {
        return [['type' => 'positional', 'name' => 'settings', 'optional' => \false, 'description' => 'The name of the settings page.']];
    }
    public function run_command($args, $assoc_args)
    {
        $this->slug = $this->sanitize_slug($args);
        $this->class_name = $this->ucwords($this->slug);
        $this->create_settings_file();
        $this->update_subscriber();
        WP_CLI::success('Way to go! ' . WP_CLI::colorize("%W{$this->slug}%n") . ' settings page has been created');
    }
    private function create_settings_file()
    {
        $new_settings = $this->src_path . 'Settings/' . $this->class_name . '.php';
        $this->file_system->write_file($new_settings, $this->get_settings_file_contents());
    }
    private function get_settings_file_contents()
    {
        $settings_file = $this->file_system->get_file($this->templates_path . 'settings/settings.php');
        return \sprintf($settings_file, $this->class_name, \str_replace('_', ' ', $this->class_name));
    }
    protected function update_subscriber()
    {
        $subscriber = $this->src_path . 'Settings/Settings_Subscriber.php';
        $method = "\t\t\t\$container->get( {$this->class_name}::class )->hook();" . \PHP_EOL;
        // insert after the first settings page we find hooking into WP
        $this->file_system->insert_into_existing_file($subscriber, $method, '->hook()');
    }
}
