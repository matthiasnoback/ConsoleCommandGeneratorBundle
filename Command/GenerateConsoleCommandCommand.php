<?php

namespace Matthias\Bundle\ConsoleCommandGeneratorBundle\Command;

use Matthias\Bundle\ConsoleCommandGeneratorBundle\Generator\ConsoleCommandGenerator;
use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class GenerateConsoleCommandCommand extends GeneratorCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:console-command')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'Name of the Bundle')
            ->addOption(
                'command',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the command, e.g. doctrine:schema:create'
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                'Class name of the command, e.g. CreateSchemaDoctrineCommand'
            )
            ->addOption(
                'argument',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Argument for this command, e.g. title:required'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Symfony2 console command generator');

        $class = $dialog->askAndValidate(
            $output,
            $dialog->getQuestion('Command class', $input->getOption('class')),
            function ($name) {
                if (!preg_match('/^(\w+)Command$/', $name)) {
                    throw new \InvalidArgumentException('Command class should end with "Command" and contain only word characters');
                }

                return $name;
            },
            false,
            $input->getOption('class')
        );
        $input->setOption('class', $class);

        while (true) {
            $bundleName = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion('Bundle name', $input->getOption('bundle')),
                array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateBundleName'),
                false,
                $input->getOption('bundle')
            );

            try {
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);
                /** @var $bundle Bundle */

                $commandFile = '/Command/' . $class . '.php';
                if (!file_exists($bundle->getPath() . $commandFile)) {
                    break;
                }

                $output->writeln(
                    sprintf('<bg=red>A file named "%s" already exists in %s.</>', $commandFile, $bundleName)
                );
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundleName));
            }
        }

        $input->setOption('bundle', $bundleName);

        $command = $dialog->askAndValidate(
            $output,
            $dialog->getQuestion('Command name', $input->getOption('command')),
            function ($command) {
                if (!preg_match('/^[a-z0-9:-_]+$/', $command)) {
                    throw new \InvalidArgumentException('Command name should only contain a-z, 0-9, :, - and _ characters');
                }

                return $command;
            },
            false,
            $input->getOption('command')
        );
        $input->setOption('command', $command);

        $arguments = array();

        while (true) {
            $name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion('Argument (press enter to stop adding arguments)', ''),
                function ($name) {
                    if ($name == '') {
                        return $name;
                    }

                    if (!preg_match('/^[a-zA-Z0-9]+$/', $name)) {
                        throw new \InvalidArgumentException('Invalid argument name');
                    }

                    return $name;
                }
            );

            if (!$name) {
                break;
            }

            $mode = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion('Mode (required, optional, array)', 'required'),
                function ($mode) {
                    if (!in_array($mode, array('required', 'optional', 'array'))) {
                        throw new \InvalidArgumentException('Invalid mode');
                    }

                    return $mode;
                },
                false,
                'required'
            );

            $arguments[] = array('name' => $name, 'mode' => $mode, 'description' => '', 'default' => '');
        }

        $input->setOption(
            'argument',
            array_filter(
                $arguments,
                function (array $argument) {
                    return implode(';', $argument);
                }
            )
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation(
                $output,
                $dialog->getQuestion('Do you confirm generation', 'yes', '?'),
                true
            )
            ) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        $dialog->writeSection($output, 'Console command generation');

        $bundleName = $input->getOption('bundle');
        $command = $input->getOption('command');
        $class = $input->getOption('class');

        try {
            $bundle = $this->getContainer()->get('kernel')->getBundle($bundleName);
        } catch (\Exception $e) {
            $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundleName));
            return 1;
        }

        $generator = $this->getGenerator($bundle);
        /** @var $generator ConsoleCommandGenerator */

        $generator->generate($bundle, $command, $class, $input->getOption('argument'));

        return 0;
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        return array_merge(
            parent::getSkeletonDirs($bundle),
            array(
                __DIR__ . '/../Resources/skeleton'
            )
        );
    }

    protected function createGenerator()
    {
        return new ConsoleCommandGenerator($this->getContainer()->get('filesystem'));
    }
}
