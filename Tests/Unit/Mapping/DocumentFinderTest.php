<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Tests\Unit\Mapping;

use Steerfox\ElasticsearchBundle\Mapping\DocumentFinder;

class DocumentFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testGetNamespace().
     *
     * @return array
     */
    public function getTestGetNamespaceData()
    {
        return [
            [
                'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\Product',
                'TestBundle:Product'
            ],
            [
                'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\User',
                'TestBundle:User'
            ],
        ];
    }

    /**
     * Tests for getNamespace().
     *
     * @param string $expectedNamespace
     * @param string $className
     *
     * @dataProvider getTestGetNamespaceData()
     */
    public function testGetNamespace($expectedNamespace, $className)
    {
        $bundles = [
            'TestBundle' => 'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\TestBundle'
        ];
        $finder = new DocumentFinder($bundles);

        $this->assertEquals($expectedNamespace, $finder->getNamespace($className));
    }

    /**
     * Test for getBundleDocumentClasses().
     */
    public function testGetBundleDocumentClasses()
    {
        $bundles = [
            'TestBundle' => 'Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\TestBundle'
        ];
        $finder = new DocumentFinder($bundles);

        $documents = $finder->getBundleDocumentClasses('TestBundle');

        $this->assertGreaterThan(0, count($documents));
        $this->assertContains('Product', $documents);
        $this->assertContains('User', $documents);
    }
}
