<?php
/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Tests\Functional\Result;

use Steerfox\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;

class DocumentNullObjectFieldTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 'foo',
                        'title' => 'Bar Product',
                        'location' => null,
                        'released' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test if fetched object field is actually NULL.
     */
    public function testResultWithNullObjectField()
    {
        /** @var Product $document */
        $document = $this->getManager()->find('TestBundle:Product', 'foo');

        $this->assertInstanceOf(
            'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
            $document
        );

        $this->assertNull($document->getLocation());
        $this->assertNull($document->getReleased());
    }
}
