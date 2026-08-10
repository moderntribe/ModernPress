<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

class Taxonomy_Generator extends Generator_Command
{
    protected $slug = '';
    protected $class_name = '';
    protected $namespace = '';
    protected $taxonomy_directory = '';
    protected $assoc_args = [];
    public function command()
    {
        return 'generate tax';
    }
    public function description()
    {
        return 'Generates a Taxonomy';
    }
    public function arguments()
    {
        return [['type' => 'positional', 'name' => 'taxonomy', 'optional' => \false, 'description' => \__('The name of the Taxonomy.', 'tribe')], ['type' => 'assoc', 'name' => 'post-types', 'optional' => \true, 'description' => \__('Comma separated list of post types to register this taxonomy to.', 'tribe')], ['type' => 'assoc', 'name' => 'single', 'optional' => \true, 'description' => \__('Singular taxonomy.', 'tribe')], ['type' => 'assoc', 'name' => 'plural', 'optional' => \true, 'description' => \__('Plural taxonomy.', 'tribe')]];
    }
    public function run_command($args, $assoc_args)
    {
        $this->setup($args, $assoc_args);
        // Create directory.
        $this->create_taxonomy_directory();
        // Write file(s).
        $this->create_taxonomy_class();
        // Write subscriber.
        $this->create_subscriber();
        // Update Core.
        $this->update_core();
        \Tribe\Alert_Scoped\WP_CLI::success('Way to go! ' . \Tribe\Alert_Scoped\WP_CLI::colorize("%W{$this->slug}%n") . ' taxonomy has been created');
    }
    protected function setup($args, $assoc_args)
    {
        $this->slug = $this->sanitize_slug($args);
        $this->class_name = $this->ucwords($this->slug);
        $this->namespace = 'Tribe\\Project\\Taxonomies\\' . $this->class_name;
        $this->assoc_args = $this->parse_assoc_args($assoc_args);
    }
    private function parse_assoc_args($assoc_args)
    {
        $defaults = ['single' => $this->ucwords($this->slug), 'plural' => $this->ucwords($this->slug) . 's'];
        $assoc_args['post-types'] = $this->get_post_types($assoc_args);
        return \wp_parse_args($assoc_args, $defaults);
    }
    private function get_post_types($assoc_args)
    {
        if (!isset($assoc_args['post-types'])) {
            return [];
        }
        $post_types = \explode(',', $assoc_args['post-types']);
        foreach ($post_types as $post_type) {
            if (!\post_type_exists($post_type)) {
                \Tribe\Alert_Scoped\WP_CLI::error('Sorry...post type ' . $post_type . ' does not exist.');
            }
        }
        return $post_types;
    }
    private function create_taxonomy_directory()
    {
        $directory = \trailingslashit($this->src_path) . 'Taxonomies/' . $this->ucwords($this->slug);
        $this->taxonomy_directory = $directory;
        $this->file_system->create_directory($directory);
    }
    private function create_taxonomy_class()
    {
        $this->new_taxonomy_class_file();
        $this->new_taxonomy_config_file();
    }
    private function create_subscriber()
    {
        $service_provider_file = $this->src_path . 'Taxonomies/' . $this->ucwords($this->slug) . '/Subscriber.php';
        $this->file_system->write_file($service_provider_file, $this->get_subscriber_contents());
    }
    private function update_core()
    {
        $core_file = $this->src_path . 'Core.php';
        $new_subscriber_registration = "\t\t" . \sprintf('Taxonomies\\%s\\Subscriber::class,', $this->class_name) . "\n";
        $below_line = '// our taxonomies';
        $this->file_system->insert_into_existing_file($core_file, $new_subscriber_registration, $below_line);
    }
    private function new_taxonomy_class_file()
    {
        $class_file = \trailingslashit($this->taxonomy_directory) . $this->ucwords($this->slug) . '.php';
        $this->file_system->write_file($class_file, $this->get_taxonomy_class_contents());
    }
    private function new_taxonomy_config_file()
    {
        $config_file = \trailingslashit($this->taxonomy_directory) . 'Config.php';
        $this->file_system->write_file($config_file, $this->get_taxonomy_config_contents());
    }
    private function get_taxonomy_class_contents()
    {
        $taxonomy_file = $this->file_system->get_file($this->templates_path . 'taxonomies/taxonomy.php');
        return \sprintf($taxonomy_file, $this->class_name, $this->slug);
    }
    private function get_taxonomy_config_contents()
    {
        $config_file = $this->file_system->get_file($this->templates_path . 'taxonomies/config.php');
        return \sprintf($config_file, $this->class_name, $this->assoc_args['single'], $this->assoc_args['plural'], $this->slug, $this->format_post_types());
    }
    private function get_subscriber_contents()
    {
        $service_provider = $this->file_system->get_file($this->templates_path . 'taxonomies/subscriber.php');
        return \sprintf($service_provider, $this->class_name);
    }
    private function format_post_types()
    {
        if (empty($this->assoc_args['post-types'])) {
            return '';
        }
        $post_types = '';
        foreach ($this->assoc_args['post-types'] as $post_type) {
            $post_types .= "'{$post_type}',";
        }
        return $post_types;
    }
}
