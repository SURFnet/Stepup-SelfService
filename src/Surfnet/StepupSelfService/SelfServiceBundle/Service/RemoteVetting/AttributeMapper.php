<?php
/**
 * Copyright 2010 SURFnet B.V.
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting;

use Psr\Log\LoggerInterface;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatch;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatchCollection;

class AttributeMapper
{
    /**
     * @var RemoteVettingConfiguration
     */
    private $configuration;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(RemoteVettingConfiguration $configuration, LoggerInterface  $logger)
    {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @param string $identityProviderName
     * @param AttributeListDto $localAttributes
     * @param AttributeListDto $remoteAttributes
     * @return AttributeMatchCollection
     */
    public function map($identityProviderName, AttributeListDto $localAttributes, AttributeListDto $remoteAttributes)
    {
        $attributeMapping = $this->configuration->getAttributeMapping($identityProviderName);

        $localMap = $this->attributeListToMap($localAttributes);
        $remoteMap = $this->attributeListToMap($remoteAttributes);

        $matchCollection = new AttributeMatchCollection();
        foreach ($attributeMapping as $localName => $remoteName) {
            if (!array_key_exists($localName, $localMap)) {
                $this->logger->warning(sprintf(
                    'Invalid remote vetting attribute mapping, local attribute with name "%s" not found, skipping',
                    $localName
                ));
                continue;
            }

            if (!array_key_exists($remoteName, $remoteMap)) {
                $this->logger->warning(sprintf(
                    'Invalid remote vetting attribute mapping, remote attribute with name "%s" not found, skipping',
                    $remoteName
                ));
                continue;
            }

            $attributeMatch = new AttributeMatch($localMap[$localName], $remoteMap[$remoteName], false, '');
            $matchCollection->add($localName, $attributeMatch);
        };

        return $matchCollection;
    }

    /**
     * @param AttributeListDto $attributeList
     * @return array
     */
    private function attributeListToMap(AttributeListDto $attributeList)
    {
        $result = [];
        foreach ($attributeList->getAttributeCollection() as $attribute) {
            $result[$attribute->getName()] = $attribute;
        }
        return $result;
    }
}
