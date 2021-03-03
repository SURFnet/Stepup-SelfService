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

use Surfnet\StepupSelfService\SelfServiceBundle\Assert;

class RemoteVettingIdenityProviderDto
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $descriptions;

    /**
     * @var string
     */
    private $logo;

    /**
     * @var string
     */
    private $slug;


    /**
     * @var string
     */
    private $entityId;

    /**
     * @var string
     */
    private $ssoUrl;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var string
     */
    private $certificateFile;

    /**
     * @param string $identityId
     * @param string $secondFactorId
     * @return RemoteVettingIdenityProviderDto
     */
    public static function create(array $configData)
    {
        Assert::notBlank($configData['name'], 'The name of a remote vetting identity provider must not be blank');
        Assert::string($configData['name'], 'The name of a remote vetting identity provider must be of type string');
        Assert::allString($configData['description'], 'All description entries must be of type string');
        Assert::notBlank($configData['logo'], 'The logo of a remote vetting identity provider must not be blank');
        Assert::string($configData['logo'], 'The logo of a remote vetting identity provider must be of type string');
        Assert::string($configData['slug'], 'The slug of a remote identity provider must be of type string');
        Assert::alnum($configData['slug'], 'The slug must be alphanumeric');

        Assert::keyExists($configData, 'entityId', 'entityId should be set');
        Assert::keyExists($configData, 'ssoUrl', 'ssoUrl should be set');
        Assert::keyExists($configData, 'privateKey', 'privateKey should be set');
        Assert::keyExists($configData, 'certificateFile', 'certificateFile should be set');

        Assert::url($configData['entityId'], 'entityId should be an url');
        Assert::url($configData['ssoUrl'], 'ssoUrl should be an url');
        Assert::string($configData['privateKey'], 'privateKey should be a string');
        Assert::string($configData['certificateFile'], 'certificateFile should be a string');

        $identityProvider = new self();
        $identityProvider->name = $configData['name'];
        $identityProvider->descriptions = $configData['description'];
        $identityProvider->logo = $configData['logo'];
        $identityProvider->slug = $configData['slug'];

        $identityProvider->entityId = $configData['entityId'];
        $identityProvider->ssoUrl = $configData['ssoUrl'];
        $identityProvider->privateKey = $configData['privateKey'];
        $identityProvider->certificateFile = $configData['certificateFile'];

        return $identityProvider;
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
    public function getDescription($lang)
    {
        Assert::keyExists($this->descriptions, $lang);
        return $this->descriptions[$lang];
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getSsoUrl(): string
    {
        return $this->ssoUrl;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @return string
     */
    public function getCertificateFile(): string
    {
        return $this->certificateFile;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }
}
