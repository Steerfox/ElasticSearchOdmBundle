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

use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use Steerfox\ElasticsearchBundle\Service\Repository;
use Steerfox\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class DocumentIteratorTest extends AbstractElasticsearchTestCase
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
                        '_id' => 'doc1',
                        'title' => 'Foo Product',
                        'related_categories' => [
                            [
                                'title' => 'Acme',
                            ],
                        ],
                    ],
                    [
                        '_id' => 'doc2',
                        'title' => 'Bar Product',
                        'related_categories' => [
                            [
                                'title' => 'Acme',
                            ],
                            [
                                'title' => 'Bar',
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

        foreach ($iterator as $document) {
            $categories = $document->getRelatedCategories();

            $this->assertInstanceOf(
                'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
                $document
            );
            $this->assertInstanceOf('Steerfox\ElasticsearchBundle\Result\ObjectIterator', $categories);

            foreach ($categories as $category) {
                $this->assertInstanceOf(
                    'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\CategoryObject',
                    $category
                );
            }
        }
    }

    /**
     * Tests if current() returns null when data doesn't exist.
     */
    public function testCurrentWithEmptyIterator()
    {
        $repo = $this->getManager()->getRepository('TestBundle:User');
        $search = $repo
            ->createSearch()
            ->addQuery(new MatchAllQuery());
        $result = $repo->findDocuments($search);

        $this->assertNull($result->current());
    }

    /**
     * Tests AbstractResultsIterator#first method.
     */
    public function testIteratorFirst()
    {
        $repo = $this->getManager()->getRepository('TestBundle:Product');
        $search = $repo
            ->createSearch()
            ->addQuery(new MatchAllQuery());
        $document = $repo->findDocuments($search)->first();

        $this->assertEquals('Foo Product', $document->getTitle());
    }
}
