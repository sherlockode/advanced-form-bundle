<?php

namespace Sherlockode\AdvancedFormBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sherlockode\AdvancedFormBundle\Storage\FilesystemStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveTemporaryFileCommand
 */
class RemoveTemporaryFileCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var FilesystemStorage
     */
    private $storage;

    /**
     * @var string
     */
    private $tmpUploadFileClass;

    /**
     * RemoveTemporaryFileCommand constructor.
     *
     * @param EntityManagerInterface $em
     * @param FilesystemStorage      $storage
     * @param string                 $tmpUploadFileClass
     */
    public function __construct(EntityManagerInterface $em, FilesystemStorage $storage, $tmpUploadFileClass = null)
    {
        parent::__construct();
        $this->em = $em;
        $this->storage = $storage;
        $this->tmpUploadFileClass = $tmpUploadFileClass;
    }

    protected function configure()
    {
        $this
            ->setName('sherlockode:afb:cleanup-tmp')
            ->setDescription('Remove all temporary files.')
            ->addOption('older-than', null, InputOption::VALUE_OPTIONAL)
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

        if (!$this->tmpUploadFileClass) {
            throw new RuntimeException(sprintf('The "tmp_uploaded_file_class" has to be configured for this action.'));
        }

        $qb = $this->em->createQueryBuilder()
            ->select('f')
            ->from($this->tmpUploadFileClass, 'f');

        if ($limit) {
            $qb
                ->andWhere('f.createdAt < :createdAt')
                ->setParameter('createdAt', $limit);
        }

        $files = $qb->getQuery()->getResult();

        foreach ($files as $file) {
            $this->storage->remove($file->getKey());
            $this->em->remove($file);
        }

        $this->em->flush();
        $output->writeln(sprintf('<info>%s files has been removed</info>', count($files)));

        return 0;
    }
}
