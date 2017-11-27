<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Result;

/**
 * Raw documents iterator.
 */
class RawIterator extends AbstractResultsIterator
{
    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $document;
    }
}
