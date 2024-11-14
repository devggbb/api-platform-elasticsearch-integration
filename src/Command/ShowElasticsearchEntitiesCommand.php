<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Command;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchMappingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ShowElasticsearchEntitiesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ElasticsearchMappingService $esMapping,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('elasticsearch:show-entities')
            ->setDescription('Show all entities and their Elasticsearch fields.')
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Filter by namespace');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $namespace = $input->getOption('namespace');
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metadata as $classMetadata) {
            $className = $classMetadata->getName();

            if ($namespace && !str_starts_with($className, $namespace)) {
                continue;
            }

            if ($this->esMapping->hasElasticsearchEntity($className)) {
                $output->writeln("<info>Entity: $className</info>");

                $fields = $this->esMapping->getElasticsearchFields($className);
                $methodFields = $this->esMapping->getElasticsearchMethods($className);

                if (!empty($fields) || !empty($methodFields)) {
                    if (!empty($fields)) {
                        $output->writeln("  <comment>Fields:</comment>");
                        foreach ($fields as $field => $name) {
                            $output->writeln("    - $field: $name");
                        }
                    }

                    if (!empty($methodFields)) {
                        $output->writeln("  <comment>Method Fields:</comment>");
                        foreach ($methodFields as $method => $name) {
                            $output->writeln("    - $method: $name");
                        }
                    }
                } else {
                    $output->writeln("  <comment>No Elasticsearch fields found.</comment>");
                }
            }
        }

        return Command::SUCCESS;
    }
}