<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

use Tribe\Alert_Scoped\WP_CLI;
use function WP_CLI\Utils\get_flag_value;
class Block_Middleware_Generator extends Generator_Command
{
    public const ARG_NAME = 'name';
    public const OPTION_DRY_RUN = 'dry-run';
    /**
     * @var bool Whether the version of square-one includes block middleware.
     */
    private $supports_middelware;
    /**
     * @var bool Whether we are running dry run mode.
     */
    private $dry_run = \false;
    /**
     * @var string The absolute path to the tests directory
     */
    private $tests_path;
    public function __construct(File_System $file_system, $src_path, $tests_path)
    {
        parent::__construct($file_system, $src_path);
        // @phpstan-ignore-next-line
        $this->supports_middelware = \method_exists('Tribe\\Alert_Scoped\\Tribe\\Project\\Blocks\\Types\\Base_Model', 'init_data');
        $this->tests_path = $tests_path;
    }
    /**
     * wp s1 generate block:middleware <name>
     */
    protected function command() : string
    {
        return 'generate block:middleware';
    }
    protected function description() : string
    {
        return \__('Generates block field and model middleware skeletons', 'tribe');
    }
    protected function arguments() : array
    {
        return [['type' => self::ARGUMENT, 'name' => self::ARG_NAME, 'optional' => \false, 'description' => \__('The name of the block middleware', 'tribe')], ['type' => self::FLAG, 'name' => self::OPTION_DRY_RUN, 'optional' => \true, 'description' => \__('During a dry-run, no files will be written', 'tribe'), 'default' => \false]];
    }
    /**
     * @throws \WP_CLI\ExitException
     */
    public function run_command($args, $assoc_args)
    {
        if (!$this->supports_middelware) {
            WP_CLI::error('Sorry, your version of Square One does not support Block Middleware. Skipping creation...');
        }
        $this->dry_run = get_flag_value($assoc_args, self::OPTION_DRY_RUN, \false);
        $class_name = $this->class_name($args[0]);
        $this->make_field_middleware($class_name);
        $this->make_field_middleware_test($class_name);
        $this->make_model_middleware($class_name);
        $this->make_model_middleware_test($class_name);
        $this->update_definer($class_name);
        WP_CLI::success('Way to go! ' . WP_CLI::colorize("%W{$class_name}%n") . ' block middleware has been created');
    }
    private function make_field_middleware(string $class_name) : void
    {
        $suffix = 'Field_Middleware';
        $directory = $this->config_directory($class_name);
        $directory .= \trailingslashit($suffix);
        $file_path = \sprintf('%s%s_%s.php', $directory, $class_name, $suffix);
        $file_contents = \sprintf(\file_get_contents(__DIR__ . '/templates/middleware/field.php.tmpl'), $class_name);
        if ($this->dry_run) {
            WP_CLI::log('[Dry Run] Block field middleware file: ' . $file_path);
            WP_CLI::log('Block field middleware contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing block field middleware file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function make_field_middleware_test(string $class_name) : void
    {
        $suffix = 'Field_Middleware';
        $directory = $this->integration_tests_directory($class_name);
        $directory .= \trailingslashit($suffix);
        $file_path = \sprintf('%s%s_%s_Test.php', $directory, $class_name, $suffix);
        $file_contents = \sprintf(\file_get_contents(__DIR__ . '/templates/middleware/tests/field.php.tmpl'), $class_name);
        if ($this->dry_run) {
            WP_CLI::log('[Dry Run] Block field middleware test file: ' . $file_path);
            WP_CLI::log('Block field middleware test contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing block field middleware test file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function make_model_middleware(string $class_name) : void
    {
        $suffix = 'Model_Middleware';
        $directory = $this->config_directory($class_name);
        $directory .= \trailingslashit($suffix);
        $file_path = \sprintf('%s%s_%s.php', $directory, $class_name, $suffix);
        $file_contents = \sprintf(\file_get_contents(__DIR__ . '/templates/middleware/model.php.tmpl'), $class_name);
        if ($this->dry_run) {
            WP_CLI::log('[Dry Run] Block model middleware file: ' . $file_path);
            WP_CLI::log('Block model middleware contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing model field middleware file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function make_model_middleware_test(string $class_name) : void
    {
        $suffix = 'Model_Middleware';
        $directory = $this->integration_tests_directory($class_name);
        $directory .= \trailingslashit($suffix);
        $file_path = \sprintf('%s%s_%s_Test.php', $directory, $class_name, $suffix);
        $file_contents = \sprintf(\file_get_contents(__DIR__ . '/templates/middleware/tests/model.php.tmpl'), $class_name);
        if ($this->dry_run) {
            WP_CLI::log('[Dry Run] Block model middleware test file: ' . $file_path);
            WP_CLI::log('Block model middleware test contents: ' . "\n" . $file_contents);
        } else {
            WP_CLI::log('Writing model field middleware test file to ' . $file_path);
            $this->file_system->create_directory($directory);
            $this->file_system->write_file($file_path, $file_contents);
        }
    }
    private function update_definer(string $class_name) : void
    {
        $definer_path = $this->src_path . 'Block_Middleware/Block_Middleware_Definer.php';
        $field_registration = "\t\t\t\t" . \sprintf('DI\\get( \\Tribe\\Project\\Blocks\\Middleware\\%1$s\\Field_Middleware\\%1$s_Field_Middleware::class ), // @TODO: replace with use import', $class_name) . "\n";
        $model_registration = "\t\t\t\t" . \sprintf('DI\\get( \\Tribe\\Project\\Blocks\\Middleware\\%1$s\\Model_Middleware\\%1$s_Model_Middleware::class ), // @TODO: replace with use import', $class_name) . "\n";
        if ($this->dry_run) {
            WP_CLI::log('[Dry Run] Skipping registration of block middleware in Block_Middleware_Definer.php ');
        } else {
            WP_CLI::log('Registering block middleware in Block_Middleware_Definer.php ');
            $this->file_system->insert_into_existing_file($definer_path, $field_registration, 'self::FIELD_MIDDLEWARE_COLLECTION');
            $this->file_system->insert_into_existing_file($definer_path, $model_registration, 'self::MODEL_MIDDLEWARE_COLLECTION');
        }
    }
    /**
     * Sanitize the block type name into a valid PHP class name
     *
     * @param  string  $name
     *
     * @return string
     */
    private function class_name(string $name) : string
    {
        $name = \sanitize_title($name);
        $name = \str_replace('-', '_', $name);
        return $this->ucwords($name);
    }
    private function config_directory(string $class_name) : string
    {
        return \trailingslashit($this->src_path . 'Blocks/Middleware/' . $class_name . '/');
    }
    private function integration_tests_directory(string $class_name) : string
    {
        return \trailingslashit($this->tests_path . 'integration/Tribe/Project/Blocks/Middleware/' . $class_name . '/');
    }
}
