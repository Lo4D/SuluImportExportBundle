<?php

declare(strict_types=1);

/*
 * This file is part of TheCadien/SuluImportExportBundle.
 *
 * (c) Oliver Kossin
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TheCadien\Bundle\SuluImportExportBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use TheCadien\Bundle\SuluImportExportBundle\Service\ImportInterface;

class ImportCommand extends Command
{
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var ImportInterface
     */
    private $importService;

    /**
     * ImportCommand constructor.
     */
    public function __construct(
        ImportInterface $importService
    ) {
        parent::__construct();
        $this->importService = $importService;
    }

    protected function configure()
    {
        $this
            ->setName('sulu:import')
            ->addOption('skip-db', 'd', InputOption::VALUE_OPTIONAL, 'Skips database export', false)
            ->setDescription('Imports contents exported with the sulu:export command from the remote host.')
            ->addOption(
                'add-assets',
                null,
                null,
                'Add assets.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $skipDb = $input->getOption('skip-db');
        $this->input = $input;
        $this->output = $output;
        $skipAssets = $this->input->getOption('add-assets');
        $this->progressBar = new ProgressBar($this->output, $skipAssets ? 4 : 6);
        $this->progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% <info>%message%</info>');

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('<question>Continue with this options?(y/N)</question> ', false);

        $output->writeln([
            '<info>Content export</info>',
            '<info>==============</info>',
            '<info>Options:</info>',
            'Skip DB: '.$skipDb,
        ]);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Abort!</error>');

            return 0;
        }

        $this->progressBar->setMessage('Importing PHPCR repository...');
        $this->importService->importPHPCR();
        $this->progressBar->advance();

        if (!$skipDb) {
            $this->progressBar->setMessage('Importing database...');
            $this->importService->importDatabase();
        }
        $this->progressBar->advance();

        if ($skipAssets) {
            $this->progressBar->setMessage('Importing uploads...');
            $this->importService->importUploads();
            $this->progressBar->advance();
        }

        $this->progressBar->finish();
        $this->output->writeln(
            \PHP_EOL."<info>Successfully imported contents. You're good to go!</info>"
        );

        return 0;
    }
}
