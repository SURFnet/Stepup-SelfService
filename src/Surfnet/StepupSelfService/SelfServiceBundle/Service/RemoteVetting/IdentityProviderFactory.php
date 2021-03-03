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

use SAML2\Configuration\PrivateKey;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingIdentityProviderException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;

class IdentityProviderFactory
{
    /**
     * @var IdentityProvider[]
     */
    private $identityProviders = [];

    /**
     * @param RemoteVettingConfiguration $configuration
     */
    public function __construct(RemoteVettingConfiguration $configuration)
    {
        foreach ($configuration->getRemoteVettingIdps() as $idp) {
            Assert::file($idp->getPrivateKey(), 'privateKey should be a file');
            Assert::file($idp->getCertificateFile(), 'certificateFile should be a file');

            $idpConfiguration = [
                'name' => $idp->getName(),
                'entityId' => $idp->getEntityId(),
                'ssoUrl' => $idp->getSsoUrl(),
                'certificateFile' => $idp->getCertificateFile(),
                'privateKeys' => [new PrivateKey($idp->getPrivateKey(), PrivateKey::NAME_DEFAULT)],
            ];

            // set idp
            $this->identityProviders[$idp->getSlug()] = new IdentityProvider($idpConfiguration);
        }
    }

    /**
     * @param string $name
     * @return IdentityProvider
     */
    public function create($name)
    {
        if (array_key_exists($name, $this->identityProviders)) {
            return $this->identityProviders[$name];
        }

        throw new InvalidRemoteVettingIdentityProviderException(sprintf("Invalid IdP requested '%s'", $name));
    }
}
