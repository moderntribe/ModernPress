<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

use Tribe\Alert_Scoped\WP_CLI;
use function WP_CLI\Utils\get_flag_value;
class Block_Generator extends Generator_Command
{
    public const ARG_NAME = 'name';
    public const OPTION_DRY_RUN = 'dry-run';
    public const OPTION_WITH_MIDDLEWARE = 'with-middleware';
    public const OPTION_WITH_POST_LOOP_MIDDLEWARE = 'with-post-loop-middleware';
    /**
     * @var string Path to the theme's directory, with a trailing slash.
     */
    private $theme_path;
    /**
     * @var bool Whether the version of square-one includes block middleware.
     */
    private $supports_middelware;
    public function __construct(File_System $file_system, $src_path, $theme_path)
    {
        parent::__construct($file_system, $src_path);
        $this->theme_path = \trailingslashit($theme_path);
        // @phpstan-ignore-next-line
        $this->supports_middelware = \method_exists('Tribe\\Alert_Scoped\\Tribe\\Project\\Blocks\\Types\\Base_Model', 'init_data');
    }
    public function description() : string
    {
        return \__('Generates a block type.', 'tribe');
    }
    public function command() : string
    {
        return 'generate block';
    }
    public function arguments() : array
    {
        return [['type' => self::ARGUMENT, 'name' => self::ARG_NAME, 'optional' => \false, 'description' => \__('The name of the block type', 'tribe')], ['type' => self::FLAG, 'name' => self::OPTION_DRY_RUN, 'optional' => \true, 'description' => \__('During a dry-run, no files will be written', 'tribe'), 'default' => \false], ['type' => self::FLAG, 'name' => self::OPTION_WITH_MIDDLEWARE, 'optional' => \true, 'description' => \__('Automatically adds middleware params to the block config', 'tribe'), 'default' => \false], ['type' => self::FLAG, 'name' => self::OPTION_WITH_POST_LOOP_MIDDLEWARE, 'optional' => \true, 'description' => \__('Automatically adds Post Loop Field middleware to the block config and model', 'tribe'), 'default' => \false]];
    }
    public function run_command($args, $assoc_args)
    {
        $type_name = $this->type_name($args[0]);
        $class_name = $this->class_name($args[0]);
        $component_name = $this->component_name($args[0]);
        $dry_run = get_flag_value($assoc_args, self::OPTION_DRY_RUN, \false);
        $with_middleware = get_flag_value($assoc_args, self::OPTION_WITH_MIDDLEWARE, \false);
        $with_post_loop_middleware = get_flag_value($assoc_args, self::OPTION_WITH_POST_LOOP_MIDDLEWARE, \false);
        $this->make_block_config($type_name, $class_name, $dry_run, $with_middleware, $with_post_loop_middleware);
        $this->make_block_model($class_name, $component_name, $dry_run, $with_post_loop_middleware);
        $this->make_block_template($type_name, $class_name, $component_name, $dry_run);
        $this->make_component($component_name, $dry_run, $with_post_loop_middleware);
        $this->update_definer($type_name, $class_name, $dry_run);
        $this->update_block_middleware_definer($class_name, $dry_run, $with_post_loop_middleware);
        WP_CLI::success('Way to go! ' . WP_CLI::colorize("%W{$type_name}%n") . ' block has been created');
    }
    private function make_component($name, $dry_run, $with_post_loop_middleware) : void
    {
        WP_CLI::runcommand(\sprintf('s1 generate component blocks/%s %s %s', $name, $dry_run ? '--' . Component_Generator::OPTION_DRY_RUN : '', $with_post_loop_middleware ? '--' . Component_Generator::OPTION_WITH_POST_LOOP_MIDDLEWARE : ''), ['return' => \false, 'launch' => \false, 'exit_error' => \true]);
    }
    /**
     * Generate the Block_Config file for the block type
     *
     * @param  string  $name
     * @param  string  $class_name
     * @param  bool    $dry_run
     * @param  bool    $with_middleware
     * @param  bool    $with_post_loop_middleware
     *
     * @return void
     */
    private function make_block_config(string $name, string $class_name, bool $dry_run, bool $with_middleware, bool $with_post_loop_middleware) : void
    {
        $directory = $this->config_directory($class_name);
        $file_path = $directory . $class_name . '.php';
        if ($with_middleware && !$this->supports_middelware) {
            WP_CLI::warning('Sorry, this version of Square One does not support block middleware.');
        }
        $block_category = "'text'";
        $use_statements = [];
        if (\class_exists('Tribe\\Alert_Scoped\\Tribe\\Project\\Blocks\\Block_Category')) {
            $use_statements[] = 'use Tribe\\Project\\Blocks\\Block_Category;';
            $block_category = 'Block_Category::CUSTOM_BLOCK_CATEGORY_SLUG';
        }
        if ($this->supports_middelware) {
            if ($with_middleware || $with_post_loop_middleware) {
                $use_statements[] = 'use Tribe\\Project\\Block_Middleware\\Contracts\\Has_Middleware_Params;';
            }
            if ($with_post_loop_middleware) {
                $use_statements[] = 'use Tribe\\Project\\Blocks\\Middleware\\Post_Loop\\Config\\Post_Loop_Field_Config;';
                $use_statements[] = 'use Tribe\\Project\\Blocks\\Middleware\\Post_Loop\\Field_Middleware\\Post_Loop_Field_Middleware;';
            }
        }
        // Prefix use statements with a line break.
        if ($use_statements) {
            \sort($use_statements);
            $use_statements = \preg_filter('/^/', "\r\n", $use_statements);
        }
        $middleware_interface = $this->supports_middelware && ($with_middleware || $with_post_loop_middleware) ? ' implements Has_Middleware_Params' : '';
        $middleware_method = '';
        $additional_constants = '';
        $additional_section = '';
        if ($with_post_loop_middleware && $this->supports_middelware) {
            $middleware_method = $this->get_block_config_post_loop_middleware_method();
            $additional_constants = "public const SECTION_CARDS = 's-cards';\r\n    public const POST_LIST = 'post_list';\r\n";
            $additional_section = "\r\n\r\n\t\t" . '// Post loop fields will be added to this section via block middleware. ' . "\r\n\t\t" . '$this->add_section( new Field_Section( self::SECTION_CARDS, esc_html__( \'Cards\', \'tribe\' ), \'accordion\' ) );';
        } elseif ($with_middleware && $this->supports_middelware) {
            $middleware_method = $this->get_block_config_middleware_method();
        }
        $file_contents = \sprintf(\file_get_contents(__DIR__ . '/templates/block/config.php.tmpl'), $class_name, $name, $this->human_name($class_name), $use_statements ? \implode('', $use_statements) : '', $middleware_interface, $middleware_method, $additional_constants, $additional_section, $block_category);
        if ($dry_run) {
            WP_CLI::log('[Dry Run] Block config file: ' . $file_path);
            WP_CLI::log('Block config contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing block config file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function config_directory(string $class_name) : string
    {
        return \trailingslashit($this->src_path . 'Blocks/Types/' . $class_name . '/');
    }
    private function make_block_model(string $class_name, string $component_name, bool $dry_run, bool $with_post_loop_middleware) : void
    {
        $directory = $this->config_directory($class_name);
        $file_path = $directory . $class_name . '_Model.php';
        // Check for updated Base_Model method for versions of square-one that have block middleware.
        $template_path = __DIR__ . '/templates/block/model.php.tmpl';
        $file_contents = \sprintf(
            \file_get_contents($template_path),
            $class_name,
            $this->controller_namespace($component_name),
            $this->controller_classname($component_name),
            $this->supports_middelware ? 'init_data' : 'get_data',
            // method differs depending on the version of Square one
            $with_post_loop_middleware && $this->supports_middelware ? "use Tribe\\Project\\Blocks\\Middleware\\Post_Loop\\Post_Loop_Repository;\r\n" : '',
            $with_post_loop_middleware && $this->supports_middelware ? $this->get_block_model_post_loop_middleware_constructor() : '',
            $with_post_loop_middleware && $this->supports_middelware ? \sprintf("\r\n\t\t\t" . '%s::POSTS   => $this->post_loop->get_posts( (array) $this->get( %s::POST_LIST ) ),', $this->controller_classname($component_name), $class_name) : ''
        );
        if ($dry_run) {
            WP_CLI::log('[Dry Run] Block model file: ' . $file_path);
            WP_CLI::log('Block model contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing block model file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function make_block_template(string $type_name, string $class_name, string $component_name, bool $dry_run) : void
    {
        $directory = $this->theme_path . 'blocks/' . $type_name . '/';
        $file_path = $directory . $type_name . '.php';
        $file_contents = \sprintf(\file_get_contents(__DIR__ . '/templates/block/template.php.tmpl'), $class_name, $component_name);
        if ($dry_run) {
            WP_CLI::log('[Dry Run] Block template file: ' . $file_path);
            WP_CLI::log('Block template contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing block template file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function update_definer(string $type_name, string $class_name, bool $dry_run) : void
    {
        $definer_path = $this->src_path . 'Blocks/Blocks_Definer.php';
        $type_registration = "\t\t\t\t" . \sprintf('DI\\get( Types\\%1$s\\%1$s::class ),', $class_name) . "\n";
        $allowlist_registration = "\t\t\t\t" . \sprintf("'acf/%s',", $type_name) . "\n";
        if ($dry_run) {
            WP_CLI::log('[Dry Run] Skipping registration of block type in Blocks_Definer.php ');
        } else {
            WP_CLI::log('Registering block type in Blocks_Definer.php ');
            $this->file_system->insert_into_existing_file($definer_path, $type_registration, 'self::TYPES');
            $this->file_system->insert_into_existing_file($definer_path, $allowlist_registration, 'self::ALLOW_LIST');
        }
    }
    /**
     * Sanitize the block type name to remove all non-alpha characters
     * (because ACF does not play well with non-alpha characters)
     *
     * @param string $name
     *
     * @return string
     */
    private function type_name(string $name) : string
    {
        return \preg_replace('/[^a-z]/', '', \strtolower($name));
    }
    /**
     * Sanitize the block type name into a valid PHP class name
     *
     * @param string $name
     *
     * @return string
     */
    private function class_name(string $name) : string
    {
        $name = \sanitize_title($name);
        $name = \str_replace('-', '_', $name);
        return $this->ucwords($name);
    }
    private function human_name(string $class_name) : string
    {
        $parts = \array_map('ucwords', \explode('_', $class_name));
        return \implode(' ', $parts);
    }
    private function component_name(string $name) : string
    {
        $name = \sanitize_title($name);
        return \str_replace('-', '_', $name);
    }
    private function controller_namespace(string $name) : string
    {
        return 'Tribe\\Project\\Templates\\Components\\blocks\\' . $name;
    }
    private function controller_classname(string $name) : string
    {
        return $this->class_name($name) . '_Controller';
    }
    private function update_block_middleware_definer(string $class_name, bool $dry_run, bool $with_post_loop_middleware) : void
    {
        if (!$with_post_loop_middleware || !$this->supports_middelware) {
            return;
        }
        $definer_path = $this->src_path . 'Block_Middleware/Block_Middleware_Definer.php';
        $type_registration = $this->get_post_loop_block_middleware_definition($class_name) . \PHP_EOL;
        if ($dry_run) {
            WP_CLI::log('[Dry Run] Skipping registration of block in Block_Middleware_Definer.php ');
        } else {
            WP_CLI::log('Registering block in Block_Middleware_Definer.php ');
            $this->file_system->insert_into_existing_file($definer_path, $type_registration, 'self::BLOCK_MIDDLEWARE');
        }
    }
    private function get_block_config_middleware_method() : string
    {
        return <<<METHOD
\t\t
\t/**
\t * @TODO Provide specific middleware parameters.
\t *
\t * @return array<int, array<string, array<int, mixed>>>
\t */
\tpublic function get_middleware_params(): array {
\t\treturn [
\t\t\t[
\t\t\t\t'the_middleware_key' => [
\t\t\t\t\t'the_middleware_data',
\t\t\t\t],\t\t
\t\t\t],
\t\t];
\t}
\t\t\t
METHOD;
    }
    private function get_block_config_post_loop_middleware_method() : string
    {
        return <<<'METHOD'
		
	/**
	 * @TODO Customize the Post Loop Field Configuration.
	 *
	 * @return array<array{post_loop_field_configs: \Tribe\Project\Blocks\Middleware\Post_Loop\Config\Post_Loop_Field_Config[]}>
	 */
	public function get_middleware_params(): array {
		$config             = new Post_Loop_Field_Config();
		$config->field_name = self::POST_LIST;
		$config->group      = $this->get_section_key( self::SECTION_CARDS );
		
		return [
			[
				Post_Loop_Field_Middleware::MIDDLEWARE_KEY => [
					$config,
				],		
			],
		];
	}
			
METHOD;
    }
    private function get_block_model_post_loop_middleware_constructor() : string
    {
        return <<<'CONSTRUC'
		
	protected Post_Loop_Repository $post_loop;

	public function __construct( array $block, Post_Loop_Repository $post_loop ) {
		$this->post_loop = $post_loop;

		parent::__construct( $block );
	}
			
CONSTRUC;
    }
    private function get_post_loop_block_middleware_definition(string $class_name) : string
    {
        $class = \sprintf('\\Tribe\\Project\\Blocks\\Types\\%1$s\\%1$s::class', $class_name);
        return <<<DEFINITION
\t\t\t\t// TODO: replace class with an import "use" statement
\t\t\t\t{$class}    => [
\t\t\t\t\tPost_Loop_Field_Middleware::class,
\t\t\t\t],\t\t\t\t
DEFINITION;
    }
}
