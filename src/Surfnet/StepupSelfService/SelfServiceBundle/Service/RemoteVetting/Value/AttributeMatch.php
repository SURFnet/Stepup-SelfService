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

class AttributeMatch implements JsonSerializable
{
    /**
     * @var bool
     */
    private $valid;
    /**
     * @var string
     */
    private $remarks = '';
    /**
     * @var Attribute
     */
    private $localAttribute;
    /**
     * @var Attribute
     */
    private $remoteAttribute;

    /**
     * @param Attribute $localAttribute
     * @param Attribute $remoteAttribute
     * @param bool $valid
     * @param string $remarks
     */
    public function __construct(Attribute $localAttribute, Attribute $remoteAttribute, $valid, $remarks)
    {
        Assert::boolean($valid, 'valid should be boolean');
        Assert::string($remarks, 'remarks should be string');

        $this->localAttribute = $localAttribute;
        $this->remoteAttribute = $remoteAttribute;
        $this->valid = $valid;
        $this->remarks = $remarks;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @return Attribute
     */
    public function getLocalAttribute()
    {
        return $this->localAttribute;
    }

    /**
     * @return Attribute
     */
    public function getRemoteAttribute()
    {
        return $this->remoteAttribute;
    }

    public function jsonSerialize()
    {
        return ['local' => $this->localAttribute, 'remote' => $this->remoteAttribute, 'is-valid' => $this->valid, 'remarks' => $this->remarks];
    }
}
