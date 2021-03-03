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
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class ServiceProviderFactory
{
    /**
     * @var ServiceProvider
     */
    private $serviceProvider;

    public function __construct(RouterInterface $router, string $entityId, string $assertionConsumerUrlSlug, string $privateKey)
    {
        Assert::notEmpty($entityId, 'entityId');
        Assert::notEmpty($assertionConsumerUrlSlug, 'assertionConsumerUrl');
        Assert::notEmpty($privateKey, 'privateKey');

        $configuration = [];
        $configuration['entityId'] = $entityId;
        $configuration['assertionConsumerUrl'] = $router->generate($assertionConsumerUrlSlug, [], UrlGeneratorInterface::ABSOLUTE_URL);
        $configuration['privateKey'] = $privateKey;

        Assert::url($configuration['entityId']);
        Assert::url($configuration['assertionConsumerUrl']);
        Assert::file($configuration['privateKey']);

        $configuration['privateKeys'] = [new PrivateKey($configuration['privateKey'], PrivateKey::NAME_DEFAULT)];
        unset($configuration['privateKey']);

        $this->serviceProvider = new ServiceProvider($configuration);
    }

    /**
     * @return ServiceProvider
     */
    public function create()
    {
        return $this->serviceProvider;
    }
}
