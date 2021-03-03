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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption;

use Surfnet\StepupBundle\DateTime\DateTime;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollectionInterface;

class IdentityData
{
    /**
     * @var AttributeCollectionInterface
     */
    private $attributeCollectionAggregate;

    /**
     * @var string
     */
    private $nameId;

    /**
     * @var string
     */
    private $applicationVersion;

    /**
     * @var string
     */
    private $institution;

    /**
     * @var string
     */
    private $remoteVettingSource;

    /**
     * @param AttributeCollectionInterface $attributeCollectionAggregate
     * @param string $nameId
     * @param string $applicationVersion
     * @param $institution
     * @param $remoteVettingSource
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        AttributeCollectionInterface $attributeCollectionAggregate,
        $nameId,
        $applicationVersion,
        $institution,
        $remoteVettingSource
    ) {
        Assert::string($nameId, 'The name id must have a string value');
        Assert::string($applicationVersion, 'The application version must have a string value');
        Assert::string($institution, 'The SHO of the institution must have a string value');
        Assert::string($remoteVettingSource, 'The remote vetting source must have a string value');

        $this->attributeCollectionAggregate = $attributeCollectionAggregate;
        $this->nameId = $nameId;
        $this->applicationVersion = $applicationVersion;
        $this->institution = $institution;
        $this->remoteVettingSource = $remoteVettingSource;
    }

    public function serialize()
    {
        return json_encode(
            [
                'attribute-data' => $this->attributeCollectionAggregate->getAttributes(),
                'name-id' => $this->nameId,
                'institution' => $this->institution,
                'remote-vetting-source' => $this->remoteVettingSource,
                'application-version' => $this->applicationVersion,
                'time' => DateTime::now()->format('c'),
            ]
        );
    }
}
