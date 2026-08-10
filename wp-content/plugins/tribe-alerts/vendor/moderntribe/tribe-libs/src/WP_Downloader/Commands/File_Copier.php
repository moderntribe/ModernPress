<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\WP_Downloader\Commands;

use Tribe\Alert_Scoped\Symfony\Component\Console\Attribute\AsCommand;
use Tribe\Alert_Scoped\Symfony\Component\Console\Command\Command;
use Tribe\Alert_Scoped\Symfony\Component\Console\Input\InputArgument;
use Tribe\Alert_Scoped\Symfony\Component\Console\Input\InputInterface;
use Tribe\Alert_Scoped\Symfony\Component\Console\Output\OutputInterface;
#[\Symfony\Component\Console\Attribute\AsCommand(name: 'copy', description: 'Copies a file from one place to another. Useful for sample files.')]
class File_Copier extends Command
{
    public const ARG_FROM = 'from';
    public const ARG_TO = 'to';
    protected string $project_root;
    public function __construct(string $project_root)
    {
        $this->project_root = $project_root;
        parent::__construct();
    }
    protected function configure()
    {
        $this->addArgument(self::ARG_FROM, InputArgument::REQUIRED, 'The from path to the file, relative to the project root');
        $this->addArgument(self::ARG_TO, InputArgument::REQUIRED, 'The to path to the file, relative to the project root');
    }
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $from = \realpath($this->project_root) . '/' . $input->getArgument(self::ARG_FROM);
        $to = \realpath($this->project_root) . '/' . $input->getArgument(self::ARG_TO);
        if (!\file_exists($to)) {
            $output->writeln(\sprintf('Copying file %s to %s...', $from, $to));
            if (\copy($from, $to)) {
                return Command::SUCCESS;
            }
            return Command::FAILURE;
        }
        $output->writeln(\sprintf('File "%s" already exists. Skipping...', $to));
        return Command::SUCCESS;
    }
}
