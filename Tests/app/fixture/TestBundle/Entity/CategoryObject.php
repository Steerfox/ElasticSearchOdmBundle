<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Tests\app\fixture\TestBundle\Entity;

use Steerfox\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Object
 */
class CategoryObject
{
    /**
     * @var string
     * @ES\Property(type="text", options={"index"="not_analyzed"})
     */
    public $title;
}
