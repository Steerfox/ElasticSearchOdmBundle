<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Tests\Unit\Event;

use Steerfox\ElasticsearchBundle\Event\BulkEvent;

class BulkEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $event = new BulkEvent('index', 'test_type', []);

        $this->assertEquals('test_type', $event->getType());
        $this->assertEquals('test_type', $event->getType());
        $this->assertEquals([], $event->getQuery());
    }
}
