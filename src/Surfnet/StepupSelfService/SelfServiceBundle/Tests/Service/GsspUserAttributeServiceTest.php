<?php

/**
 * Copyright 2021 SURFnet bv
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service;

use Mockery as m;
use Monolog\Logger;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\Extensions\Extensions;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity;
use Surfnet\StepupSelfService\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupSelfService\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\GsspUserAttributeService;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class GsspUserAttributeServiceTest extends m\Adapter\Phpunit\MockeryTestCase
{
    private $identity;

    public function setUp(): void
    {
        $identity = new Identity();
        $identity->id = 'testId';
        $identity->nameId = 'testNameId';
        $identity->institution = 'testInstitution';
        $identity->email = 'test@test.nl';
        $identity->commonName = 'testCommonName';
        $identity->preferredLocale = 'nl_nl';
        $this->identity = $identity;

        parent::setUp();
    }

    public function test_if_it_adds_extensions()
    {
        $provider = new Provider(
            'azuremfa',
            m::spy(ServiceProvider::class),
            m::spy(IdentityProvider::class),
            new StateHandler(m::mock(NamespacedAttributeBag::class), 'test')
        );

        $extensions = m::mock(Extensions::class);
        $extensions
            ->shouldReceive('addChunk')
            ->once();

        $authnRequest = m::mock(AuthnRequest::class);
        $authnRequest
            ->shouldReceive('getExtensions')
            ->once()
            ->andReturn($extensions);
        $authnRequest
            ->shouldReceive('setExtensions')
            ->once();

        $logger = m::mock(Logger::class);
        $logger->shouldReceive('info')->once();

        $service = new GsspUserAttributeService($logger);
        $service->addGsspUserAttributes($authnRequest, $provider, $this->identity);
    }

    public function test_if_it_skips_extensions()
    {
        $provider = new Provider(
            'tiqr',
            m::spy(ServiceProvider::class),
            m::spy(IdentityProvider::class),
            new StateHandler(m::mock(NamespacedAttributeBag::class), 'test')
        );

        $extensions = m::mock(Extensions::class);
        $extensions->shouldReceive('addChunk');

        $authnRequest = m::mock(AuthnRequest::class);
        $authnRequest
            ->shouldReceive('getExtensions')
            ->once()
            ->andReturn($extensions);
        $authnRequest->shouldReceive('setExtensions');

        $logger = m::mock(Logger::class);
        $logger->shouldNotReceive('info');

        $service = new GsspUserAttributeService($logger);
        $service->addGsspUserAttributes($authnRequest, $provider, $this->identity);
    }
}
