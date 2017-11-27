<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Tests\Unit\EventListener;

use Steerfox\ElasticsearchBundle\EventListener\TerminateListener;

/**
 * Tests TerminateListener class
 */
class TerminateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests kernel terminate event
     */
    public function testKernelTerminate()
    {
        $manager = $this->getMockBuilder('Steerfox\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())
            ->method('commit');

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->any())
            ->method('get')
            ->with('es.manager.test_available')
            ->willReturn($manager);

        $listener = new TerminateListener(
            $container,
            [
                'test_available' => [
                    'force_commit' => true,
                ],
                'test_unavailable' => [
                    'force_commit' => true,
                ],
            ]
        );

        $listener->onKernelTerminate();
    }
}
