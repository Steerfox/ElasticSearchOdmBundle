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

use Steerfox\ElasticsearchBundle\Result\AbstractResultsIterator;

class DummyIterator extends AbstractResultsIterator
{
    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $document;
    }
}
