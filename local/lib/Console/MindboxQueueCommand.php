<?php

namespace Frizus\Console;

use Frizus\Mindbox\Executor\MindboxExecutor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mindbox:queue',
    description: 'Запуск запросов в майндбокс, которые по какой-то причине не выполнились на сайте'
)]
class MindboxQueueCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stats = [];
        $mindboxExecutor = new MindboxExecutor();
        while ($operation = $mindboxExecutor->getNextOperation()) {
            if (is_success($operation->execute())) {
                $stats['success'] ??= 0;
                $stats['success']++;
            } else {
                $stats['error'] ??= 0;
                $stats['error']++;
            }
        }

        if ($output->isVerbose()) {
            foreach ($stats as $name => $value) {
                $output->writeln($name . ': ' . $value);
            }
        }

        return Command::SUCCESS;
    }
}