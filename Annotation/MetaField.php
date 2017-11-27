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
 * All meta-field annotations must implement this interface.
 */
interface MetaField
{
    /**
     * Returns meta-field name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns meta-field settings.
     *
     * @return array
     */
    public function getSettings();
}
