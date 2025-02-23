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
    use TheCadien\Bundle\SuluImportExportBundle\Service\ExportInterface;

    class ExportCommand extends Command
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
         * @var ExportInterface
         */
        private $exportService;

        public function __construct(
            ExportInterface $exportService
        ) {
            parent::__construct();
            $this->exportService = $exportService;
        }

        protected function configure()
        {
            $this
                ->setName('sulu:export')
                ->addOption('skip-db', 'd', InputOption::VALUE_OPTIONAL, 'Skips database export', false)
                ->setDescription('Exports all Sulu contents (PHPCR, database, uploads) to the web directory.');
        }

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $skipDb = $input->getOption('skip-db');
            $this->input = $input;
            $this->output = $output;
            $this->progressBar = new ProgressBar($this->output, 3);
            $this->progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% <info>%message%</info>');

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

            $this->progressBar->setMessage('Exporting PHPCR repository...');
            $this->exportService->exportPHPCR();
            $this->progressBar->advance();

            if (!$skipDb) {
                $this->progressBar->setMessage('Exporting database...');
                $this->exportService->exportDatabase();
            } else {
                $this->output->writeln([
                    '',
                    'Skipping db...',
                ]);
            }
            $this->progressBar->advance();

            $this->progressBar->setMessage('Exporting uploads...');
            $this->exportService->exportUploads();
            $this->progressBar->advance();

            $this->progressBar->finish();
            $this->output->writeln(
                \PHP_EOL.'<info>Successfully exported contents.</info>'
            );

            return 0;
        }
    }
