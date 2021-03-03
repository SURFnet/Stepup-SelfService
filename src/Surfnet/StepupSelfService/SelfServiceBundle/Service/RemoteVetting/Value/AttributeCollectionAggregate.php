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

use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;

class AttributeCollectionAggregate implements AttributeCollectionInterface
{
    /**
     * @var AttributeCollectionInterface[]
     */
    private $attributeCollections = [];

    public function add($name, AttributeCollectionInterface $collection)
    {
        Assert::string(
            $name,
            'The name of the attribute collection should not be null, and should identify the purpose of the collection'
        );
        Assert::keyNotExists(
            $this->attributeCollections,
            $name,
            sprintf('The collection named: "%s" is already present in the collection', $name)
        );
        $this->attributeCollections[$name] = $collection;
    }

    /**
     * @return AttributeListDto[]
     */
    public function getAttributes()
    {
        $output = [];
        foreach ($this->attributeCollections as $name => $collection) {
            $output[$name] = $collection->getAttributes();
        }
        return $output;
    }
}
