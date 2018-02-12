<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Command;

use Steerfox\ElasticsearchBundle\Service\IndexSuffixFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for creating elasticsearch index.
 */
class IndexUpdateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('steerfox:es:index:update')
            ->setDescription('Update elasticsearch index if exist.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getManager($input->getOption('manager'));
        $results = $manager->updateAllManagerIndex();
        foreach ($results as $resultLine){
            $output->writeln($resultLine);
        }
    }
}
