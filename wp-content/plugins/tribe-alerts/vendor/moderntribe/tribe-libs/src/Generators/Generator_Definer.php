<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\CLI_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Generator_Definer implements Definer_Interface
{
    public const SRC_PATH = 'libs.generator.path.src';
    public const THEME_PATH = 'libs.generator.path.theme';
    public const TESTS_PATH = 'libs.generator.path.tests';
    public function define() : array
    {
        return [
            self::SRC_PATH => static fn(ContainerInterface $container) => \plugin_dir_path($container->get('plugin.file')) . 'src',
            self::THEME_PATH => static fn(ContainerInterface $container) => \get_template_directory(),
            self::TESTS_PATH => static fn() => \trailingslashit(\realpath(\ABSPATH . '/../dev/tests/tests')),
            File_System::class => DI\create(),
            CLI_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH)),
            CPT_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH)),
            Component_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH), DI\get(self::THEME_PATH)),
            Block_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH), DI\get(self::THEME_PATH)),
            Block_Middleware_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH), DI\get(self::TESTS_PATH)),
            Settings_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH)),
            Taxonomy_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH)),
            Image_Size_Generator::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH)),
            Meta_Importer::class => DI\create()->constructor(DI\get(File_System::class), DI\get(self::SRC_PATH)),
            /**
             * Add commands for the CLI subscriber to register
             */
            CLI_Definer::COMMANDS => DI\add([DI\get(CLI_Generator::class), DI\get(CPT_Generator::class), DI\get(Component_Generator::class), DI\get(Block_Generator::class), DI\get(Block_Middleware_Generator::class), DI\get(Settings_Generator::class), DI\get(Taxonomy_Generator::class), DI\get(Image_Size_Generator::class), DI\get(Meta_Importer::class)]),
        ];
    }
}
