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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service\RemoteVetting\Value;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollection;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollectionAggregate;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatch;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatchCollection;

class AttributeCollectionInterfaceIntegrationTest extends TestCase
{
    public function test_correct_integration_of_collections()
    {
        $attributes = [
            'name' => ['John'],
            'lastName' => ['Doe'],
            'shacHomeOrganization' => ['institution-a.example.com']
        ];

        $institutionAttributes = new AttributeListDto($attributes, 'johndoe.institution-a.example.com');
        $remoteVettingAttributes = new AttributeListDto(array_merge($attributes, ['documentNumber' => ["1234567890"]]), 'identifier-at-rv-idp');

        $attributeCollection = new AttributeCollection($attributes);
        $attributeMatches = new AttributeMatchCollection();
        foreach ($attributeCollection as $attribute) {
            $attributeMatches->add('name', new AttributeMatch($attribute, $attribute, false, ''));
        }

        $aggregate = new AttributeCollectionAggregate();
        $aggregate->add('local-attributes', $institutionAttributes);
        $aggregate->add('remote-attributes', $remoteVettingAttributes);
        $aggregate->add('attribute-matches', $attributeMatches);

        $smashed = $aggregate->getAttributes();

        $this->assertTrue($this->isJsonSerializable($smashed));
        $this->assertArrayHasKey('local-attributes', $smashed);
        $this->assertArrayHasKey('remote-attributes', $smashed);
        $this->assertArrayHasKey('attribute-matches', $smashed);
    }

    /**
     * Simple validation logic to ensure no objects reside in the aggregated collections
     * @param $value
     * @return bool
     */
    private function isJsonSerializable($value)
    {
        $return = true;
        $wrapped = array($value);
        array_walk_recursive($wrapped, function ($element) use (&$return) {
            if (is_object($element) && !$element instanceof JsonSerializable) {
                var_dump($element); die;
                $return = false;
            }
        });
        return $return;
    }
}
