<?php

namespace Sherlockode\AdvancedFormBundle\Command;

use Sherlockode\AdvancedFormBundle\Storage\FilesystemStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveTemporaryFileCommand
 */
class RemoveTemporaryFileCommand extends Command
{
    /**
     * RemoveTemporaryFileCommand constructor.
     *
     * @param FilesystemStorage $storage
     */
    public function __construct(FilesystemStorage $storage)
    {
        parent::__construct();
        $this->storage = $storage;
    }

    protected function configure()
    {
        $this
            ->setName('sherlockode:afb:cleanup-tmp')
            ->setDescription('Remove all temporary files.')
            ->addOption('older-than', null, InputOption::VALUE_REQUIRED)
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $olderThan = $input->getOption('older-than');
        $limit = null;
        if ($olderThan) {
            $limit = clone new \DateTime();
            $limit->sub(new \DateInterval(sprintf('P%s', strtoupper($olderThan))));
        }

        $files = $this->storage->all();
        $count = 0;

        foreach ($this->storage->all() as $filePath) {
            if ($limit) {
                $file = $this->storage->getFileObject($filePath);
                if ($file->getCTime() < $limit->getTimestamp()) {
                    $this->storage->remove($filePath);
                    $count++;
                }
            } else {
                $this->storage->remove($filePath);
                $count++;
            }
        }

        $output->writeln(sprintf('<info>%s files has been removed</info>', $count));

        return 0;
    }
}
