<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\Unit\Synchronization;

use Doctrine\ORM\EntityManagerInterface;
use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Executor\DatabaseCleanerInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Executor\DataTransfer;
use Farmitoo\ReplibyteBundle\Synchronization\Provider\ConfigurationProviderInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Provider\TableModelProvider;
use Farmitoo\ReplibyteBundle\Synchronization\Replicator;
use PHPUnit\Framework\TestCase;

class ReplicatorTest extends TestCase
{
    protected EntityManagerInterface $em;
    protected PDOConnectionInterface $distantPDOConnection;
    protected PDOConnectionInterface $localPDOConnection;
    protected DatabaseCleanerInterface $databaseCleaner;
    protected ConfigurationProviderInterface $configurationProvider;
    protected TableModelProvider $tableModelProvider;
    protected DataTransfer $dataTransfer;

    protected Replicator $replicator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->databaseCleaner = $this->createMock(DatabaseCleanerInterface::class);
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->tableModelProvider = $this->createMock(TableModelProvider::class);
        $this->dataTransfer = $this->createMock(DataTransfer::class);
        $this->distantPDOConnection = $this->createMock(PDOConnectionInterface::class);
        $this->localPDOConnection = $this->createMock(PDOConnectionInterface::class);

        $this->replicator = new Replicator(
            $this->em,
            $this->distantPDOConnection,
            $this->localPDOConnection,
            $this->databaseCleaner,
            $this->configurationProvider,
            $this->tableModelProvider,
            $this->dataTransfer
        );
    }

    public function testExecute(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method("setGlobalLimit")
            ->with(100);

        $this->databaseCleaner->expects($this->once())
            ->method("clean");

        $this->tableModelProvider->expects($this->once())
            ->method("provideAll");

        $this->dataTransfer->expects($this->once())
            ->method("fromDistantToLocal");

        $this->replicator->execute(100);
    }

    public function testInsertData(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method("setGlobalLimit")
            ->with(100);

        $this->databaseCleaner->expects($this->never())
            ->method("clean");

        $this->tableModelProvider->expects($this->once())
            ->method("provideAll");

        $this->dataTransfer->expects($this->once())
            ->method("fromDistantToLocal");

        $this->replicator->insertNewData(100);
    }

    public function testSetConstraints(): void
    {
        $tableCustomConstraints = [
            "table_name_1" => [
                "data" => [
                    "WHERE #aliasTable#.code = 'custom-code'",
                ],
            ],
            "table_name_2" => [
                "data" => [
                    "WHERE #aliasTable#.id = 1",
                ],
            ],
        ];
        $this->configurationProvider->expects($this->once())
            ->method("resetTablesConstraintData");

        $this->configurationProvider->expects($this->exactly(2))
            ->method("addTableConstraint")
            ->withConsecutive(
                [
                    "table_name_1",
                    [
                        "data" => [
                            "WHERE #aliasTable#.code = 'custom-code'",
                        ],
                    ],
                ],
                [
                    "table_name_2",
                    [
                        "data" => [
                            "WHERE #aliasTable#.id = 1",
                        ],
                    ],
                ]
            );

        $this->replicator->setConstraints($tableCustomConstraints);
    }
}
