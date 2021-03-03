<?php

/**
 * Copyright 2020 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;

class AttributeCollection implements IteratorAggregate, AttributeCollectionInterface
{
    /**
     * @var Attribute[]
     */
    private $attributes = [];

    public function __construct($attributes)
    {
        Assert::isArray($attributes, 'The $attributes of an AttributeCollection must be an array value');

        foreach ($attributes as $attributeName => $attributeValue) {
            $this->add(new Attribute($attributeName, $attributeValue));
        }
    }

    private function add(Attribute $attribute)
    {
        // Attributes are not
        $this->attributes[] = $attribute;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

    public function getAttributes()
    {
        $output = [];
        foreach ($this->attributes as $attribute) {
            $output[$attribute->getName()] = $attribute;
        }
        return $output;
    }
}
