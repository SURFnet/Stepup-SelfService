<?php
/**
 * Copyright 2019 SURFnet B.V.
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Mock\RemoteVetting;

use DateTime;
use Hamcrest\Core\IsEqual;
use Mockery as m;
use Psr\Log\NullLogger;
use SAML2\Certificate\KeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Response\Processor;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\Attribute\Attribute;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeSet;
use Surfnet\SamlBundle\Signing\SignatureVerifier;
use Surfnet\StepupMiddlewareClientBundle\Configuration\Dto\InstitutionConfigurationOptions;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\UnverifiedSecondFactorCollection;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\VerifiedSecondFactorCollection;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\VettedSecondFactorCollection;
use Surfnet\StepupSelfService\SelfServiceBundle\Command\RemoteVetCommand;
use Surfnet\StepupSelfService\SelfServiceBundle\Security\Authentication\Token\SamlToken;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\IdentityProviderFactory;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\SamlCalloutHelper;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\ServiceProviderFactory;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVettingService;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\SecondFactorService;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\SecondFactorTypeCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;

class MockRemoteVetControllerTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;
    /**
     * @var string
     */
    private $publicKey;
    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var RemoteVettingService
     */
    private $remoteVettingService;
    /**
     * @var SamlCalloutHelper
     */
    private $samlCalloutHelper;
    /**
     * @var AttributeSet
     */
    private $localAttributeSet;
    /**
     * @var SecondFactorService|m
     */
    private $secondFactorService;
    /**
     * @var m\MockInterface|InstitutionConfigurationOptionsService
     */
    private $institutionConfigurationOptionsService;

    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $this->client->disableReboot();

        $this->remoteVettingService = $this->client->getKernel()->getContainer()->get(RemoteVettingService::class);

        // Mock second factor service
        $this->secondFactorService = m::mock(SecondFactorService::class);
        $this->client->getKernel()->getContainer()->set('surfnet_stepup_self_service_self_service.service.second_factor', $this->secondFactorService);

        $this->mockSecondFactorOverviewPage();

        $projectDir = self::$kernel->getProjectDir();
        $keyPath = '/src/Surfnet/StepupSelfService/SelfServiceBundle/Tests/Resources';
        $this->publicKey = $projectDir . $keyPath . '/test.crt';
        $this->privateKey = $projectDir . $keyPath . '/test.key';

        $this->samlCalloutHelper = $this->setupSamlCalloutHelper();

        $this->localAttributeSet = AttributeSet::create([
            new Attribute(new AttributeDefinition('givenName', 'urn:mace:firstName', 'urn:oid:0.2.1'), ['John']),
            new Attribute(new AttributeDefinition('surname', 'urn:mace:lastName', 'urn:oid:0.2.2'), ['Doe']),
            new Attribute(new AttributeDefinition('isMemberOf', 'urn:mace:isMemberOf', 'urn:oid:0.2.7'), ['team-a', 'a-team']),
            new Attribute(new AttributeDefinition('nameId', 'urn:mace:nameId', 'urn:oid:0.2.7'), [NameID::fromArray(['Value' => 'johndoe.example.com', 'Format' => 'unspecified'])]),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }


    /**
     * @test
     * @group rv
     */
    public function the_mock_remote_vetting_idp_should_present_us_with_possible_results_for_testing_purposes()
    {
        $this->logIn();
        $this->remoteVettingService->start('mock', RemoteVettingTokenDto::create('identity-id-123456', 'second-factor-id-56789'));
        $authnRequestUrl = $this->samlCalloutHelper->createAuthnRequest('mock');

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());
    }

    /**
     * @test
     * @group rv
     */
    public function a_successful_response_from_a_remote_vetting_idp_should_succeed()
    {
        $this->logIn();
        $this->remoteVettingService->start('mock', RemoteVettingTokenDto::create('identity-id-123456', 'second-factor-id-56789'));
        $authnRequestUrl = $this->samlCalloutHelper->createAuthnRequest('mock');

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test valid response
        $this->postMockIdpForm($crawler, 'success');

        // Test if on manual matching form
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('https://selfservice.stepup.example.com/second-factor/remote-vetting/match/', $this->client->getRequest()->getUri());
        $this->assertStringContainsString('Questions for you', $this->client->getResponse()->getContent());
    }

    /**
     * @test
     * @group rv
     */
    public function a_user_cancelled_response_from_a_remote_vetting_idp_should_fail()
    {
        $this->logIn();
        $this->remoteVettingService->start('mock', RemoteVettingTokenDto::create('identity-id-123456', 'second-factor-id-56789'));
        $authnRequestUrl = $this->samlCalloutHelper->createAuthnRequest('mock');

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test user cancelled response
        $this->postMockIdpForm($crawler, 'user-cancelled');

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode()); // this could be enabled if the request to MW are mocked
        $this->assertEquals('https://selfservice.stepup.example.com/overview', $this->client->getRequest()->getUri());
        $this->assertStringContainsString('Unable to validate the information', $this->client->getResponse()->getContent());
    }

    /**
     * @test
     * @group rv
     */
    public function an_unsuccessful_response_from_a_remote_vetting_idp_should_fail()
    {
        $this->logIn();
        $this->remoteVettingService->start('mock', RemoteVettingTokenDto::create('identity-id-123456', 'second-factor-id-56789'));
        $authnRequestUrl = $this->samlCalloutHelper->createAuthnRequest('mock');

        $crawler = $this->client->request('GET', $authnRequestUrl);

        // Test unknown response
        $this->postMockIdpForm($crawler, 'unknown');

        // Test if on sp acs
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode()); // this could be enabled if the request to MW are mocked
        $this->assertEquals('https://selfservice.stepup.example.com/overview', $this->client->getRequest()->getUri());
        $this->assertStringContainsString('Unable to validate the information', $this->client->getResponse()->getContent());
    }


    /**
     * @test
     * @group rv
     */
    public function a_verified_token_must_be_vetted_with_external_idp_e2e()
    {
        // Mock remote vet vetting
        $remoteVetCommand = new RemoteVetCommand();
        $remoteVetCommand->identity = "identity-id-123456";
        $remoteVetCommand->secondFactor = "second-factor-id-56789";

        $this->secondFactorService
            ->shouldReceive('remoteVet')
            ->with(IsEqual::equalTo($remoteVetCommand))->once()
            ->andReturn(true);

        // Login
        $this->logIn();

        // On second factor overview page start vetting
        $crawler = $this->client->request('GET', 'https://selfservice.stepup.example.com/overview');
        $link = $crawler->selectLink('Validate identity')->link();
        $this->assertSame('https://selfservice.stepup.example.com/second-factor/second-factor-id-56789/vetting-types', $link->getUri());
        $crawler = $this->client->click($link);

        // Select 'mock' as vetting type
        $this->assertSame('https://selfservice.stepup.example.com/second-factor/second-factor-id-56789/vetting-types', $this->client->getRequest()->getUri());
        $button = $crawler->selectButton('select-rv-idp-mock');
        $form = $button->form();
        $crawler = $this->client->submit($form);

        // Handle IdP callout
        $this->assertSame('https://selfservice.stepup.example.com/second-factor/mock/sso', $this->client->getRequest()->getSchemeAndHttpHost() . $this->client->getRequest()->getPathInfo());
        $crawler = $this->postMockIdpForm($crawler, 'success');

        // Test if on manual matching form
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('https://selfservice.stepup.example.com/second-factor/remote-vetting/match/', $this->client->getRequest()->getUri());
        $this->assertStringContainsString('Questions for you', $this->client->getResponse()->getContent());

        // Set response attributes and post form
        $form = $crawler->selectButton('ss_remote_vet_validation[validate]')->form();
        $crawler = $this->client->submit($form, [
            'ss_remote_vet_validation[matches][surname][valid]' => '1',
            'ss_remote_vet_validation[matches][givenName][remarks]' => 'This is not my full first name',
            'ss_remote_vet_validation[feedback][remarks]' => 'All other info seems valid',
        ]);

        // Check if on overview page with success flashbag message
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('https://selfservice.stepup.example.com/overview', $this->client->getRequest()->getUri());
        $this->assertStringContainsString('Your identity information was validated successfully', $this->client->getResponse()->getContent());
    }

    /**
     * @return SamlCalloutHelper
     */
    private function setupSamlCalloutHelper()
    {
        $identityProviderFactory = $this->client->getKernel()->getContainer()->get(IdentityProviderFactory::class);

        $serviceProviderFactory = m::mock(ServiceProviderFactory::class);
        $serviceProviderFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andReturn($this->createServiceProvider());

        $logger = new NullLogger();

        $responseProcessor = new Processor($logger);
        $keyLoader = new KeyLoader();
        $signatureVerifier = new SignatureVerifier($keyLoader, $logger);
        $postBinding = new PostBinding($responseProcessor, $logger, $signatureVerifier);

        return new SamlCalloutHelper(
            $identityProviderFactory,
            $serviceProviderFactory,
            $postBinding,
            $this->remoteVettingService,
            $logger
        );
    }

    private function createServiceProvider()
    {
        return new ServiceProvider(
            [
                'entityId' => 'https://selfservice.stepup.example.com/rv/metadata',
                'assertionConsumerUrl' => 'https://selfservice.stepup.example.com/second-factor/acs',
                'certificateFile' => $this->publicKey,
                'privateKeys' => [
                    new PrivateKey(
                        $this->privateKey,
                        'default'
                    ),
                ],
                'sharedKey' => $this->publicKey,
            ]
        );
    }

    /**
     * @param Crawler $crawler
     * @param string $state State button to press
     * @return Crawler
     */
    private function postMockIdpForm(Crawler $crawler, $state)
    {
        $data = '[
            {"name":"firstName","value":["john"]},
            {"name":"lastName","value":["doe"]},
            {"name":"eduPersonAffiliation","value":["users","role1"]}
        ]';

        // Test if on decision page
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Select response', $crawler->filter('h2')->text());

        // Set response attributes and post form
        $form = $crawler->selectButton($state)->form();
        $form->get('attributes')->setValue($data);
        $crawler = $this->client->submit($form);

        // Post response
        $form = $crawler->selectButton('Post')->form();

        return $this->client->submit($form);
    }

    private function logIn()
    {
        $session = $this->client->getKernel()->getContainer()->get('session');

        $firewallContext = 'saml_based';

        $user = Identity::fromData([
            'id' => 'identity-id-123456',
            'name_id' => 'name-id',
            'institution' => 'institution',
            'email' => 'name@institution.tld',
            'common_name' => 'Common Name',
            'preferred_locale' => 'en_GB',
        ]);

        $token = new SamlToken(['ROLE_USER']);
        $token->setUser($user);

        $token->setAttribute(SamlToken::ATTRIBUTE_SET, $this->localAttributeSet);

        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    private function mockSecondFactorOverviewPage()
    {
        // Mock institution configuration for second factor overview page
        $this->institutionConfigurationOptionsService = m::mock(InstitutionConfigurationOptionsService::class);
        $this->client->getKernel()->getContainer()->set('self_service.service.institution_configuration_options', $this->institutionConfigurationOptionsService);

        $verifiedResult = json_decode('{
            "collection": {
                "total_items": 1,
                "page": 1,
                "page_size": 1
            },
            "items": [
                {
                    "id": "second-factor-id-56789",
                    "type": "yubikey",
                    "second_factor_identifier": "2340897143",
                    "registration_code": "DMHKJKH8",
                    "registration_requested_at": "' . date(DateTime::ISO8601) . '",
                    "identity_id": "identity-id-123456",
                    "institution": "institution-f.example.com",
                    "common_name": "joe-f3 Institution-f.example.com"
                }
            ],
            "filters": []
        }', true);

        $emptyResult = json_decode('{
            "collection": {
                "total_items": 0,
                "page": 0,
                "page_size": 0
            },
            "items": [],
            "filters": []
        }', true);

        $tokenCollection = new SecondFactorTypeCollection();
        $tokenCollection->verified = VerifiedSecondFactorCollection::fromData($verifiedResult);
        $tokenCollection->unverified = UnverifiedSecondFactorCollection::fromData($emptyResult);
        $tokenCollection->vetted = VettedSecondFactorCollection::fromData($emptyResult);

        $this->secondFactorService
            ->shouldReceive('getSecondFactorsForIdentity')
            ->zeroOrMoreTimes()
            ->andReturn($tokenCollection);

        $this->secondFactorService->shouldReceive('findOneVerified')
            ->andReturn($tokenCollection->verified->getOnlyElement());

        // Mock institution configuration
        $institutionConfigurationOptions = new InstitutionConfigurationOptions();

        $this->institutionConfigurationOptionsService
            ->shouldReceive('getInstitutionConfigurationOptionsFor')
            ->with('institution')
            ->zeroOrMoreTimes()
            ->andReturn($institutionConfigurationOptions);
    }
}
