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

use Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use Steerfox\ElasticsearchBundle\Service\Repository;
use Steerfox\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class HashMapObjectIteratorTest extends AbstractElasticsearchTestCase
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
                        '_id' => '1',
                        'title' => 'Foo Product',
                        'custom_attributes' => [
                            [
                                'one' => 'Acme',
                            ],
                            [
                                'two' => 'Bar',
                            ],
                            [
                                'three' => 'Foo',
                            ],
                        ],
                    ],
                    [
                        '_id' => '2',
                        'title' => 'Bar Product',
                        'custom_attributes' => [
                            [
                                'title' => 'Acme',
                            ],
                            [
                                'title' => 'Bar',
                            ],
                            [
                                'title' => 'Foo',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Iteration test.
     */
    public function testIteration()
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('TestBundle:Product');
        $match = new MatchAllQuery();
        $search = $repo->createSearch()->addQuery($match);
        $iterator = $repo->findDocuments($search);

        $this->assertInstanceOf('Steerfox\ElasticsearchBundle\Result\DocumentIterator', $iterator);

        /** @var Product $document */
        foreach ($iterator as $document) {
            $this->assertInstanceOf(
                'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
                $document
            );

            $this->assertCount(3, $document->getCustomAttributes());

            $attributes = $document->getCustomAttributes();

            switch ($document->getId()) {
                case '1':
                    $this->assertEquals($attributes[0]['one'], 'Acme');
                    $this->assertEquals($attributes[1]['two'], 'Bar');
                    $this->assertEquals($attributes[2]['three'], 'Foo');
                    break;
                case '2':
                    $this->assertEquals($attributes[0]['title'], 'Acme');
                    $this->assertEquals($attributes[1]['title'], 'Bar');
                    $this->assertEquals($attributes[2]['title'], 'Foo');
                    break;
            }
        }
    }
}
