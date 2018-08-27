<?php

declare(strict_types=1);

namespace App\Tests\Bundle\SwooleBundle\Bridge\Doctrine\ORM;

use App\Bundle\SwooleBundle\Bridge\Doctrine\ORM\EntityManagerHandler;
use App\Bundle\SwooleBundle\Server\RequestHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Request;
use Swoole\Http\Response;

class EntityManagerHandlerTest extends TestCase
{
    /**
     * @var EntityManagerHandler
     */
    private $httpDriver;

    /**
     * @var RequestHandlerInterface|ObjectProphecy
     */
    private $decoratedProphecy;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface|ObjectProphecy
     */
    private $entityManagerProphecy;

    /**
     * @var Connection|ObjectProphecy
     */
    private $connectionProphecy;

    protected function setUp(): void
    {
        $this->entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $this->decoratedProphecy = $this->prophesize(RequestHandlerInterface::class);
        $this->connectionProphecy = $this->prophesize(Connection::class);

        /** @var RequestHandlerInterface $decoratedMock */
        $decoratedMock = $this->decoratedProphecy->reveal();

        /** @var EntityManagerInterface $emMock */
        $emMock = $this->entityManagerProphecy->reveal();

        $this->setUpEntityManagerConnection();
        $this->httpDriver = new EntityManagerHandler($decoratedMock, $emMock);
    }

    public function testBoot(): void
    {
        $this->decoratedProphecy->boot([])->shouldBeCalled();
        $this->httpDriver->boot([]);
    }

    public function testHandleNoReconnect(): void
    {
        $this->connectionProphecy->ping()->willReturn(true)->shouldBeCalled();

        $request = new Request();
        $response = new Response();
        $this->decoratedProphecy->handle($request, $response)->shouldBeCalled();

        $this->entityManagerProphecy->clear()->shouldBeCalled();

        $this->httpDriver->handle($request, $response);
    }

    public function testHandleWithReconnect(): void
    {
        $this->connectionProphecy->ping()->willReturn(false)->shouldBeCalled();
        $this->connectionProphecy->close()->shouldBeCalled();
        $this->connectionProphecy->connect()->willReturn(true)->shouldBeCalled();

        $request = new Request();
        $response = new Response();
        $this->decoratedProphecy->handle($request, $response)->shouldBeCalled();

        $this->entityManagerProphecy->clear()->shouldBeCalled();

        $this->httpDriver->handle($request, $response);
    }

    private function setUpEntityManagerConnection(): void
    {
        $this->entityManagerProphecy->getConnection()->willReturn($this->connectionProphecy->reveal());
    }
}
