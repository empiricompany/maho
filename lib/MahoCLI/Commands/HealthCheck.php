<?php
/**
 * Maho
 *
 * @category   Maho
 * @package    MahoCLI
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MahoCLI\Commands;

use Mage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'health-check',
    description: 'Health check your Maho project'
)]
class HealthCheck extends BaseMahoCommand
{
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hasErrors = false;

        $output->write('Checking custom APIs... ');
        exec('grep -ir -l -E "urn:Magento|urn:OpenMage" . --include="*.xml"', $matchingFiles, $returnCode);

        if (empty($matchingFiles)) {
            $output->writeln('<info>OK</info>');
        } else {
            $hasErrors = true;
            $output->writeln('');
            $output->writeln('<error>Error: Found "urn:Magento" or "urn:OpenMage" in the following files:</error>');
            foreach ($matchingFiles as $file) {
                $output->writeln('- ' . substr($file, 2));
            }
            $output->writeln('Replace all occurrences of "urn:Magento" or "urn:OpenMage" with "urn:Maho".');
        }

        if ($hasErrors) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}