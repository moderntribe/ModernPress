<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store;

use Tribe\Alert_Scoped\DI\FactoryInterface;
class Svg_Parser_Factory
{
    protected FactoryInterface $container;
    public function __construct(FactoryInterface $container)
    {
        $this->container = $container;
    }
    /**
     * Make a parser instance with the correct SVG property.
     *
     * @param string $file_or_xml The server path to the file, or SVG XML markup.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function make(string $file_or_xml) : Svg_Parser
    {
        $xml = \simplexml_load_string($file_or_xml, null, \LIBXML_NOERROR);
        $parser = $this->container->make(Svg_Parser::class);
        return $xml ? $parser->load_string($file_or_xml) : $parser->load_file($file_or_xml);
    }
}
