<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

class CLI_Generator extends Generator_Command
{
    protected $slug = '';
    protected $class_name = '';
    protected $assoc_args = [];
    public function command()
    {
        return 'generate cli';
    }
    public function description()
    {
        return 'Generates a new CLI command';
    }
    public function arguments()
    {
        return [['type' => 'positional', 'name' => 'command', 'optional' => \false, 'description' => \__('The command slug.', 'tribe')], ['type' => 'assoc', 'name' => 'description', 'optional' => \true, 'description' => \__('Command description.', 'tribe')]];
    }
    public function run_command($args, $assoc_args)
    {
        $this->slug = $this->sanitize_slug($args);
        $this->class_name = $this->ucwords($this->slug);
        // Set up associate args with some defaults.
        $this->assoc_args = $this->parse_assoc_args($assoc_args);
        $this->create_cli_file();
        $this->update_subscriber();
    }
    protected function parse_assoc_args($assoc_args)
    {
        $defaults = ['description' => 'A generated CLI command.'];
        return \wp_parse_args($assoc_args, $defaults);
    }
    protected function create_cli_file()
    {
        $new_cli_command = $this->src_path . 'CLI/' . $this->ucwords($this->slug) . '.php';
        $this->file_system->write_file($new_cli_command, $this->get_cli_file_contents());
    }
    protected function get_cli_file_contents()
    {
        $command_file = $this->file_system->get_file($this->templates_path . 'cli/cli.php');
        return \sprintf($command_file, $this->class_name, $this->assoc_args['description'], $this->slug);
    }
    protected function update_subscriber()
    {
        $cli_subscriber = $this->src_path . 'CLI/CLI_Subscriber.php';
        // Add to hook.
        $this->file_system->insert_into_existing_file($cli_subscriber, \sprintf("\t\t\t\$container->get( %s::class )->register();%s", $this->class_name, \PHP_EOL), '->register()');
    }
}
