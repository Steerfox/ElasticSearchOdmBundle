<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Tests\Unit\Result;

use Steerfox\ElasticsearchBundle\Result\DocumentIterator;

class DocumentIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for getAggregation() in case requested aggregation is not set.
     */
    public function testGetAggregationNull()
    {
        $manager = $this->getMockBuilder('Steerfox\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new DocumentIterator([], $manager);

        $this->assertNull($iterator->getAggregation('foo'));
    }
}
