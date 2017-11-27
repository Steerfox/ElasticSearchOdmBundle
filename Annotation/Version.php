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
 * Associates document property with _version meta-field.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Version implements MetaField
{
    const NAME = '_version';

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
