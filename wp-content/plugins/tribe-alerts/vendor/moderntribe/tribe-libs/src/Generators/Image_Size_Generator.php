<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

use function WP_CLI\Utils\get_flag_value;
class Image_Size_Generator extends Generator_Command
{
    protected function command()
    {
        return 'generate image-size';
    }
    protected function description()
    {
        return \__('Adds an image size to the core plugin Image_Sizes class.', 'tribe');
    }
    protected function arguments()
    {
        return [['type' => 'positional', 'name' => 'name', 'optional' => \false, 'description' => \__('The name of the image size. E.g., "core-medium"', 'tribe')], ['type' => 'assoc', 'name' => 'width', 'optional' => \true, 'description' => \__('The width of the image size. Required if height is zero.', 'tribe'), 'default' => 0], ['type' => 'assoc', 'name' => 'height', 'optional' => \true, 'description' => \__('The height of the image size. Required if width is zero.', 'tribe'), 'default' => 0], ['type' => 'assoc', 'name' => 'ratio', 'optional' => \true, 'description' => \__('The ratio of the image size width to height, as a decimal value. Used to calculate width or height, if one has a zero value.', 'tribe'), 'default' => 0], ['type' => 'assoc', 'name' => 'crop', 'optional' => \true, 'description' => \__('Whether to crop the image. Accepts true, false, or a comma-delimited x,y string such as "left,top"', 'tribe'), 'default' => \false], ['type' => 'flag', 'name' => 'dry-run', 'optional' => \true, 'description' => \__('During a dry-run, no files will be written', 'tribe'), 'default' => \false]];
    }
    public function run_command($args, $assoc_args)
    {
        // read the cli arguments
        $dry_run = get_flag_value($assoc_args, 'dry-run', \false);
        $name = \sanitize_title($args[0]);
        $const = \strtoupper(\str_replace('-', '_', $name));
        $width = \absint($assoc_args['width'] ?? 0);
        $height = \absint($assoc_args['height'] ?? 0);
        $ratio = \abs($assoc_args['ratio'] ?? 0);
        $crop = $this->sanitize_crop($assoc_args['crop'] ?? \false);
        if (empty($width) && empty($height)) {
            \Tribe\Alert_Scoped\WP_CLI::error('Either width or height must be set');
        }
        // use ratio to calculate missing size
        if ($ratio) {
            if ($width && !$height) {
                $height = \round($width * $ratio);
            } elseif ($height && !$width) {
                $width = \round($height / $ratio);
            }
        }
        // translate the given values into code strings
        $const_string = \sprintf("\tpublic const %s = '%s';\n", $const, $name);
        $crop_string = \is_array($crop) ? \sprintf("[ '%s', '%s' ]", $crop[0], $crop[1]) : ($crop === \true ? 'true' : 'false');
        $definition_string = \sprintf("\t\tself::%s => [\n\t\t\t'width'  => %d,\n\t\t\t'height' => %d,\n\t\t\t'crop'   => %s,\n\t\t],\n", $const, $width, $height, $crop_string);
        \Tribe\Alert_Scoped\WP_CLI::log(\sprintf("Adding image size '%s'", $name));
        \Tribe\Alert_Scoped\WP_CLI::debug($const_string);
        \Tribe\Alert_Scoped\WP_CLI::debug($definition_string);
        if ($dry_run) {
            \Tribe\Alert_Scoped\WP_CLI::log('Dry run: no files will be written.');
            return;
        }
        // write to disk
        $file_path = $this->src_path . 'Theme/Config/Image_Sizes.php';
        $this->file_system->insert_into_existing_file($file_path, $const_string, 'class Image_Sizes');
        $this->file_system->insert_into_existing_file($file_path, $definition_string, 'private $sizes');
        \Tribe\Alert_Scoped\WP_CLI::success(\sprintf("Image size '%s' added to %s", $name, $file_path));
    }
    /**
     * Sanitizes the crop argument into a value that is usable by WP image sizes.
     * The value may be a boolean, or an array with two strings.
     *
     * @param bool|string $crop
     *
     * @return string[]|bool
     * @see add_image_size()
     */
    private function sanitize_crop($crop)
    {
        $boolean = \filter_var($crop, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        if (!\is_null($boolean)) {
            return $boolean;
        }
        if (!\is_string($crop)) {
            return \false;
        }
        $crop_array = \array_map('trim', \explode(',', $crop));
        $x_values = ['left', 'center', 'right'];
        $y_values = ['top', 'center', 'bottom'];
        if (\count($crop_array) !== 2 || !\in_array($crop_array[0], $x_values, \true) || !\in_array($crop_array[1], $y_values, \true)) {
            \Tribe\Alert_Scoped\WP_CLI::error(\sprintf('Invalid crop value: %s', $crop));
        }
        return $crop_array;
    }
}
