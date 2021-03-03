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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration;

use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingIdentityProviderException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingIdenityProviderDto;

class RemoteVettingConfiguration
{
    private $publicKey;

    private $location;

    private $idps = [];

    private $attributeMapping = [];

    public function __construct(string $privateKey, array $configurationSettings, array $remoteVettingIdpConfig)
    {
        Assert::string($privateKey, 'privateKey should be a string');
        Assert::file($privateKey, 'privateKey should be a file');

        Assert::string($configurationSettings['encryption_public_key'], 'identity_encryption_configuration.encryption_public_key should be a string');
        Assert::string($configurationSettings['storage_location'], 'identity_encryption_configuration.storage_location should be a string');

        $this->publicKey = $configurationSettings['encryption_public_key'];
        $this->location = $configurationSettings['storage_location'];

        foreach ($remoteVettingIdpConfig as $idpConfig) {
            $idpConfig['privateKey'] = $privateKey;

            $idp = RemoteVettingIdenityProviderDto::create($idpConfig);
            $this->idps[$idp->getSlug()] = $idp;

            Assert::keyExists($idpConfig, 'attributeMapping', sprintf('attributeMapping should be set: %s', $idp->getSlug()));
            Assert::isArray($idpConfig['attributeMapping'], 'attributeMapping should be an array');
            Assert::allString($idpConfig['attributeMapping'], 'attributeMapping should consist of strings');

            $this->attributeMapping[$idp->getSlug()] = $idpConfig['attributeMapping'];
        }
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return RemoteVettingIdenityProviderDto[]
     */
    public function getRemoteVettingIdps(): array
    {
        return $this->idps;
    }

    public function getRemoteVettingIdp(string $name): RemoteVettingIdenityProviderDto
    {
        if (array_key_exists($name, $this->idps)) {
            return $this->idps[$name];
        }

        throw new InvalidRemoteVettingIdentityProviderException(sprintf("Invalid IdP requested '%s'", $name));
    }

    /**
     * @param string
     * @return array
     */
    public function getAttributeMapping($name)
    {
        if (array_key_exists($name, $this->attributeMapping)) {
            return $this->attributeMapping[$name];
        }

        throw new InvalidRemoteVettingIdentityProviderException(sprintf("Invalid IdP requested '%s'", $name));
    }
}
