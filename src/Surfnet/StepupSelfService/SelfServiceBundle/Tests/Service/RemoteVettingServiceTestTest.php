<?php

/**
 * Copyright 2014 SURFnet bv
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

use DateTime;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Surfnet\StepupBundle\Tests\DateTimeHelper;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\ApplicationHelper;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\AttributeMapper;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption\IdentityEncrypter;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatch;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\FeedbackCollection;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVettingService;
use Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service\RemoteVetting\Encryption\Decrypter;
use Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service\RemoteVetting\Encryption\FakeIdentityWriter;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class RemoteVettingServiceTest extends TestCase
{
    /**
     * @var RemoteVettingService
     */
    private $service;
    /**
     * @var string
     */
    private $publicKey = '';
    /**
     * @var string
     */
    private $privateKey = '';
    /**
     * @var AttributeMapper
     */
    private $attributeMapper;
    /**
     * @var ApplicationHelper
     */
    private $applicationHelper;
    /**
     * @var m\LegacyMockInterface|m\MockInterface|RemoteVettingConfiguration
     */
    private $remoteVettingConfiguration;
    /**
     * @var FakeIdentityWriter
     */
    private $fakeIdentityWriter;
    /**
     * @var TestLogger
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $kernelRootPath = realpath(__DIR__ . '/../../../../../../');

        $this->publicKey = file_get_contents($kernelRootPath. '/src/Surfnet/StepupSelfService/SelfServiceBundle/Tests/Resources/encryption.crt');
        $this->privateKey = file_get_contents($kernelRootPath . '/src/Surfnet/StepupSelfService/SelfServiceBundle/Tests/Resources/encryption.key');
        
        $this->remoteVettingConfiguration = m::mock(RemoteVettingConfiguration::class);
        $this->applicationHelper = new ApplicationHelper($kernelRootPath);
        $this->fakeIdentityWriter = new FakeIdentityWriter();
        $this->logger = new TestLogger();
        $session = new Session(new MockFileSessionStorage());

        $identityEncrypter = new IdentityEncrypter($this->remoteVettingConfiguration, $this->fakeIdentityWriter);
        $logger = m::mock(LoggerInterface::class);
        $this->attributeMapper = new AttributeMapper($this->remoteVettingConfiguration, $logger);
        $remoteVettingContext = new RemoteVettingContext($session);

        $now =  new DateTime();
        $mockTime = new DateTime('@1614004537',$now->getTimezone());
        DateTimeHelper::setCurrentTime($mockTime);

        $this->service = new RemoteVettingService(
            $remoteVettingContext,
            $this->attributeMapper,
            $identityEncrypter,
            $this->applicationHelper,
            $this->logger
        );
    }

    public function test_happy_flow()
    {
        $processId = '4a46493c-7387-4c04-b491-a41f7323d73a';
        $identityId = '95515f44-71a2-4dc1-8e8f-e7e4021ee65b';
        $secondFactorId = 'dace3819-35e9-4205-8538-04bb09bdd479';

        $nameId = "john.doe@example.com";
        $institution = "stepup.example.com";
        $email = "johndoe@example.com";
        $commonName = "John Doe";
        $preferredLocale = "nl_NL";
        
        $processId = ProcessId::create($processId);

        $identity = Identity::fromData([
             'id' => $identityId,
            'name_id' => $nameId,
            'institution' => $institution,
            'email' => $email,
            'common_name' => $commonName,
            'preferred_locale' => $preferredLocale,
        ]);

        $localAttributes = new AttributeListDto(['email' => ['john@example.com'], 'firstName' => ['Johnie'], "familyName" => ["Doe"], "telephone" => ["0612345678"]], $nameId);
        $externalAttributes = new AttributeListDto(['emailAddress' => ['johndoe@example.com'], 'givenName' => ['John'], "familyName" => ["Doe"], "fullName" => ["John Doe"]], $nameId);

        $this->remoteVettingConfiguration->shouldReceive("getAttributeMapping")
            ->with('mock')
            ->andReturn([
                'email' => 'emailAddress',
                'firstName' => 'givenName',
                'familyName' => 'familyName',
            ]);

        $this->remoteVettingConfiguration->shouldReceive("getPublicKey")
            ->andReturn($this->publicKey);

        $matches = $this->attributeMapper->map('mock', $localAttributes, $externalAttributes);

        // Update match
        $match = $matches['email'];
        $matches['email'] = new AttributeMatch($match->getLocalAttribute(), $match->getRemoteAttribute(), true, "This email is valid");

        $feedbackCollection = new FeedbackCollection();
        $feedbackCollection["key1"] = ["val1"];
        $feedbackCollection["key2"] = ["val2"];

        $token = RemoteVettingTokenDto::create($identityId, $secondFactorId);
        $this->service->start('mock', $token);
        $this->service->startValidation($processId);
        $this->service->finishValidation($processId, $externalAttributes);
        $remoteVettingToken = $this->service->done($processId, $identity, $localAttributes, $matches, $feedbackCollection);

        // test token result
        $this->assertSame($identityId, $remoteVettingToken->getIdentityId());
        $this->assertSame($secondFactorId, $remoteVettingToken->getSecondFactorId());

        // test encrypted result
        $result = Decrypter::decrypt($this->fakeIdentityWriter->getData(), $this->privateKey);
        $this->assertSame('{"attribute-data":{"local-attributes":{"nameId":"john.doe@example.com","attributes":{"email":["john@example.com"],"firstName":["Johnie"],"familyName":["Doe"],"telephone":["0612345678"]}},"remote-attributes":{"nameId":"john.doe@example.com","attributes":{"emailAddress":["johndoe@example.com"],"givenName":["John"],"familyName":["Doe"],"fullName":["John Doe"]}},"matching-results":{"email":{"local":{"name":"email","value":["john@example.com"]},"remote":{"name":"emailAddress","value":["johndoe@example.com"]},"is-valid":true,"remarks":"This email is valid"},"firstName":{"local":{"name":"firstName","value":["Johnie"]},"remote":{"name":"givenName","value":["John"]},"is-valid":false,"remarks":""},"familyName":{"local":{"name":"familyName","value":["Doe"]},"remote":{"name":"familyName","value":["Doe"]},"is-valid":false,"remarks":""}},"feedback":{"key1":["val1"],"key2":["val2"]}},"name-id":"john.doe@example.com","institution":"stepup.example.com","remote-vetting-source":"mock","application-version":"Stepup-SelfService","time":"2021-02-22T14:35:37+00:00"}', $result);

        // test logs
        $this->assertSame([
            [
                'level' => 'notice',
                'message' => 'Starting a remote vetting process',
                'context' => [
                    'second-factor' => 'dace3819-35e9-4205-8538-04bb09bdd479',
                    'identity' => '95515f44-71a2-4dc1-8e8f-e7e4021ee65b',
                    'provider' => 'mock',
                ],
            ],[
                'level' => 'notice',
                'message' => 'Starting a remote vetting authentication',
                'context' => [
                    'second-factor' => 'dace3819-35e9-4205-8538-04bb09bdd479',
                    'process' => '4a46493c-7387-4c04-b491-a41f7323d73a',
                ],
            ],[
                'level' => 'notice',
                'message' => 'Finishing a remote vetting authentication',
                'context' => [
                    'second-factor' => 'dace3819-35e9-4205-8538-04bb09bdd479',
                    'process' => '4a46493c-7387-4c04-b491-a41f7323d73a',
                ],
            ],[
                'level' => 'notice',
                'message' => 'Saving the encrypted match data to the filesystem',
                'context' => [
                    'second-factor' => 'dace3819-35e9-4205-8538-04bb09bdd479',
                    'process' => '4a46493c-7387-4c04-b491-a41f7323d73a',
                ],
            ],[
                'level' => 'notice',
                'message' => 'Finished the remote vetting process',
                'context' => [
                    'second-factor' => 'dace3819-35e9-4205-8538-04bb09bdd479',
                    'process' => '4a46493c-7387-4c04-b491-a41f7323d73a',
                ],
            ],
        ], $this->logger->records);
    }
}
