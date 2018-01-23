<?php
/**
* Copyright 2016 aheadWorks. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Aheadworks\ShopByBrand\Test\Unit\Model\Brand\Content;

use Aheadworks\ShopByBrand\Api\Data\BrandInterface;
use Aheadworks\ShopByBrand\Api\Data\BrandContentInterface;
use Aheadworks\ShopByBrand\Api\Data\BrandContentInterfaceFactory;
use Aheadworks\ShopByBrand\Model\Brand\Content\ReadHandler;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Aheadworks\ShopByBrand\Model\Brand\Content\ReadHandler
 */
class ReadHandlerTest extends TestCase
{
    /**
     * @var ReadHandler
     */
    private $readHandler;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var DataObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataObjectHelperMock;

    /**
     * @var BrandContentInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $brandContentFactoryMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->resourceConnectionMock = $this->createPartialMock(
            ResourceConnection::class,
            ['getConnectionByName', 'getTableName']
        );
        $this->metadataPoolMock = $this->createPartialMock(MetadataPool::class, ['getMetadata']);
        $this->dataObjectHelperMock = $this->createPartialMock(DataObjectHelper::class, ['populateWithArray']);
        $this->brandContentFactoryMock = $this->createPartialMock(
            BrandContentInterfaceFactory::class,
            ['create']
        );
        $this->readHandler = $objectManager->getObject(
            ReadHandler::class,
            [
                'resourceConnection' => $this->resourceConnectionMock,
                'metadataPool' => $this->metadataPoolMock,
                'dataObjectHelper' => $this->dataObjectHelperMock,
                'brandContentFactory' => $this->brandContentFactoryMock
            ]
        );
    }

    public function testExecute()
    {
        $brandId = 1;
        $connectionName = 'default';
        $tableName = 'aw_sbb_brand_content';
        $metaTitle = 'Brand meta title';
        $metaDescription = 'Brand meta description';
        $description = 'Brand description';
        $data = [
            'brand_id' => $brandId,
            'store_id' => 0,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'description' => $description
        ];

        /** @var BrandInterface|\PHPUnit_Framework_MockObject_MockObject $brandMock */
        $brandMock = $this->getMockBuilder(BrandInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $contentMock = $this->getMockBuilder(BrandContentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $selectMock = $this->createPartialMock(Select::class, ['from', 'where']);

        $brandMock->expects($this->once())
            ->method('getBrandId')
            ->willReturn($brandId);
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(BrandInterface::class)
            ->willReturn($metadataMock);
        $metadataMock->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($connectionName);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnectionByName')
            ->with($connectionName)
            ->willReturn($connectionMock);
        $connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with($tableName)
            ->willReturnArgument(0);
        $selectMock->expects($this->once())
            ->method('from')
            ->with($tableName)
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('where')
            ->with('brand_id = :id')
            ->willReturnSelf();
        $connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($selectMock, ['id' => $brandId])
            ->willReturn([$data]);
        $this->brandContentFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($contentMock);
        $this->dataObjectHelperMock->expects($this->once())
            ->method('populateWithArray')
            ->with($contentMock, $data, BrandContentInterface::class);
        $brandMock->expects($this->once())
            ->method('setContent')
            ->with([$contentMock])
            ->willReturnSelf();
        $brandMock->expects($this->once())
            ->method('setMetaTitle')
            ->with($metaTitle)
            ->willReturnSelf();
        $brandMock->expects($this->once())
            ->method('setMetaDescription')
            ->with($metaDescription)
            ->willReturnSelf();
        $brandMock->expects($this->once())
            ->method('setDescription')
            ->with($description)
            ->willReturnSelf();

        $this->assertSame($brandMock, $this->readHandler->execute($brandMock));
    }
}
