<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Command;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchConnectService;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchMappingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportDataToElasticsearchCommand extends Command
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private ElasticsearchConnectService $esConnect,
        private ElasticsearchMappingService $esMapping,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('elasticsearch:import')
            ->setDescription('Import data from the database to Elasticsearch.')
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity class to import data from')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Number of records to process per batch', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '10G');

        $entityClass = $input->getArgument('entity');
        $batchSize = (int)$input->getOption('batch-size');
        $index = $this->esMapping->getIndex($entityClass);

        if (!$entityClass) {
            $output->writeln('<error>No entity class specified.</error>');
            return CommandAlias::FAILURE;
        }

        if (!class_exists($entityClass)) {
            $output->writeln("<error>Entity class '$entityClass' does not exist.</error>");
            return CommandAlias::FAILURE;
        }

        $this->deleteIndex($index, $output);
        $settings = $this->esMapping->getSettings($entityClass) ?? [];
        if ($settings) {
            $settings = ['settings' => $settings];
        }
        $mappings = $this->esMapping->getMappings($entityClass) ?? [];
        if ($mappings) {
            $mappings = ['mappings' => $mappings];
        }
        $params = [
            'index' => $index,
            'body' => [
                ...$settings,
                ...$mappings
            ]
        ];
        $this->esConnect->getClient()->indices()->create($params);

        $entityManager = $this->entityManager;
        $repository = $entityManager->getRepository($entityClass);
        $queryBuilder = $repository->createQueryBuilder('e');
        $queryBuilder->addOrderBy('e.id', 'ASC');
        $queryBuilder->setMaxResults($batchSize);

        $previousTime = microtime(true);
        $page = 1;
        while (true) {
            $offset = ($page - 1) * $batchSize;
            $queryBuilder->where('e.id > '. $offset);
            $data = $queryBuilder->getQuery()->getResult();

            if (empty($data)) {
                break;
            }

            $params = ['body' => []];
            foreach ($data as $item) {
                $itemArray = $this->esMapping->convertEntityToArray($item);
                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_id' => $item->getId() ?? null,
                    ]
                ];
                $params['body'][] = $itemArray;
            }

            $this->esConnect->getClient()->bulk($params);

            $currentTime = microtime(true);
            $timeDifference = $currentTime - $previousTime;
            $time = (new \DateTime())->format('Y-m-d H:i:s');

            $output->writeln("<info>Batch $page indexed to Elasticsearch index '$index': $time ({$timeDifference}s).</info>");
            $previousTime = $currentTime;

            $page++;
            $entityManager->clear();
        }

        $output->writeln("<info>Data import completed for Elasticsearch index '$index'.</info>");
        return CommandAlias::SUCCESS;
    }

    private function deleteIndex(string $index, OutputInterface $output): void
    {
        $params = ['index' => $index];

        try {
            if ($this->esConnect->getClient()->indices()->exists($params)) {
                $this->esConnect->getClient()->indices()->delete($params);
                $output->writeln("<info>Index '$index' deleted successfully.</info>");
            } else {
                $output->writeln("<info>Index '$index' does not exist. Skipping deletion.</info>");
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to delete index '$index': " . $e->getMessage() . "</error>");
        }
    }
}