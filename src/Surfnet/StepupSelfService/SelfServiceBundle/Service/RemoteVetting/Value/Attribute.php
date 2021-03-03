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

use JsonSerializable;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\AssertionFailedException;

/**
 * A name value pair, representing a (simplified) SAML assertion attribute
 */
class Attribute implements JsonSerializable
{
    private $name;
    private $value;

    /**
     * @param string $name
     * @param string[] $value
     */
    public function __construct($name, $value)
    {
        Assert::string($name, 'The $name of an Attribute must be a scalar value');
        Assert::isArray($value, 'The $value of an Attribute must be an array with strings');
        Assert::allString($value, 'The $value of an Attribute must be an array with strings');

        $this->name = $name;
        $this->value = array_values($value);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return ['name' => $this->name, 'value' => $this->value];
    }
}
