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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto;

use SAML2\XML\saml\NameID;
use Serializable;
use Surfnet\SamlBundle\SAML2\Attribute\Attribute as SAMLAttribute;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeSet;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\Attribute;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollection;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollectionInterface;

/**
 * The identity is a set of SAML Response assertion attributes
 *
 * Which of the attributes are considered identity data is to be decided by the
 * user of this DTO.
 */
class AttributeListDto implements Serializable, AttributeCollectionInterface
{
    /**
     * @var AttributeCollection
     */
    private $attributes;

    /**
     * @var string
     */
    private $nameId;

    public function __construct(array $attributes, $nameId)
    {
        Assert::string($nameId, 'The $nameId in an AttributeListDto must be a string value');

        $this->attributes = new AttributeCollection($attributes);
        $this->nameId = $nameId;
    }

    /**
     * @param AttributeSet $attributeSet
     * @return AttributeListDto
     */
    public static function fromAttributeSet(AttributeSet $attributeSet)
    {
        $attributes = [];
        $nameID = '';
        /** @var SAMLAttribute $attribute */
        foreach ($attributeSet as $attribute) {
            $name = $attribute->getAttributeDefinition()->getName();
            $values = $attribute->getValue();
            foreach ($values as $value) {
                if ($value instanceof NameID) {
                    $nameID = (string)$value->value;
                    continue;
                }

                $attributes[$name] = $values;
            }
        }

        return new self($attributes, $nameID);
    }

    /**
     * @param string $serialized
     * @return AttributeListDto
     */
    public static function deserialize($serialized)
    {
        $instance = new self([], '');
        $instance->unserialize($serialized);
        return $instance;
    }

    /**
     * @return AttributeListDto
     */
    public static function notSet()
    {
        return new self([], '');
    }

    /**
     * @return AttributeCollection|Attribute[]
     */
    public function getAttributeCollection()
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return json_encode($this->getAttributes());
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $this->nameId = $data['nameId'];
        $this->attributes = new AttributeCollection($data['attributes']);
    }

    public function getAttributes()
    {
        $attributes = [];
        foreach ($this->attributes as $item) {
            $attributes[$item->getName()] = $item->getValue();
        }

        return [
            'nameId' => $this->nameId,
            'attributes' => $attributes,
        ];
    }

    /**
     * @return string
     */
    public function getNameId()
    {
        return $this->nameId;
    }
}
