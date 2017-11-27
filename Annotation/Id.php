<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Annotation;

/**
 * Annotation to associate document property with _id meta-field.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Id implements MetaField
{
    const NAME = '_id';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return [];
    }
}
