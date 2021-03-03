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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service\RemoteVetting;

use PHPUnit\Framework\TestCase;
use Surfnet\StepupMiddlewareClientBundle\Uuid\Uuid;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingContextException;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingStateException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class RemoteVettingContextTest extends TestCase
{
    /**
     * @var Session
     */
    private $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session(new MockArraySessionStorage());
    }

    /**
     * @test
     * @group rv
     */
    public function a_new_remote_vetting_process_can_always_be_started()
    {
        $token = $this->createToken();
        $token2 = $this->createToken();
        $token3 = $this->createToken();

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->initialize('IRMA', $token2, AttributeListDto::notSet());
        $context->initialize('IRMA', $token3, AttributeListDto::notSet());

        $this->assertInstanceOf(RemoteVettingContext::class, $context);
    }

    /**
     * @test
     * @group rv
     */
    public function a_succesfull_remote_vetting_process_will_result_in_a_validated_token()
    {
        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);
        $context->validated($processId, AttributeListDto::notSet());
        $context->done($processId);

        $validatedToken = $context->getValidatedToken();

        $this->assertEquals($token, $validatedToken);
    }

    /**
     * @test
     * @group rv
     */
    public function a_incomplete_remote_vetting_process_wil_never_result_in_a_validated_token_0()
    {
        $this->expectException(InvalidRemoteVettingStateException::class);
        $this->expectExceptionMessage('Unable to find a validated token');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);
        $context->validated($processId, AttributeListDto::notSet());

        $context->getValidatedToken();
    }


    /**
     * @test
     * @group rv
     */
    public function a_incomplete_remote_vetting_process_wil_never_result_in_a_validated_token_1()
    {
        $this->expectException(InvalidRemoteVettingStateException::class);
        $this->expectExceptionMessage('Unable to find a validated token');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);
        $context->validated($processId, AttributeListDto::notSet());

        $context->getValidatedToken();
    }

    /**
     * @test
     * @group rv
     */
    public function a_incomplete_remote_vetting_process_wil_never_result_in_a_validated_token_2()
    {
        $this->expectException(InvalidRemoteVettingStateException::class);
        $this->expectExceptionMessage('Unable to find a validated token');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);

        $context->getValidatedToken();
    }

    /**
     * @test
     * @group rv
     */
    public function a_incomplete_remote_vetting_process_wil_never_result_in_a_validated_token_3()
    {
        $this->expectException(InvalidRemoteVettingStateException::class);
        $this->expectExceptionMessage('Unable to find a validated token');

        $token = $this->createToken();

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());

        $context->getValidatedToken();
    }

    /**
     * @test
     * @group rv
     */
    public function a_incomplete_remote_vetting_process_wil_never_result_in_a_validated_token_4()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('No remote vetting process found');

        $context = new RemoteVettingContext($this->session);

        $context->getValidatedToken();
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_which_is_not_initialized_could_not_be_validating()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('No remote vetting process found');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->validating($processId);
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_which_is_not_validating_could_not_be_validated()
    {
        $this->expectException(InvalidRemoteVettingStateException::class);
        $this->expectExceptionMessage('Unable to finish validation of a token');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        //$context->validating($processId);
        $context->validated($processId, AttributeListDto::notSet());
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_which_is_not_validated_should_never_result_in_a_validated_token()
    {
        $this->expectException(InvalidRemoteVettingStateException::class);
        $this->expectExceptionMessage('Unable to end the validation of a token');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);
        //$context->validated($processId);
        $context->done($processId);
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_validation_will_ony_work_with_a_matching_process_id()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('Invalid remote vetting context found');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $wrongProcessId = ProcessId::create('wrong');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);
        $context->validated($wrongProcessId, AttributeListDto::notSet());
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_finish_will_ony_work_with_a_matching_process_id()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('Invalid remote vetting context found');

        $token = $this->createToken();
        $processId = ProcessId::create('12345');

        $wrongProcessId = ProcessId::create('wrong');

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());
        $context->validating($processId);
        $context->validated($wrongProcessId, AttributeListDto::notSet());
        $context->validated($processId, AttributeListDto::notSet());
        $context->matched($processId, AttributeListDto::notSet());
        $context->done($wrongProcessId);
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_could_not_be_started_with_A_method_other_then_initialize_1()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('No remote vetting process found');

        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->validating($processId);
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_could_not_be_started_with_A_method_other_then_initialize_2()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('No remote vetting process found');

        $processId = ProcessId::create('No remote vetting process found');

        $context = new RemoteVettingContext($this->session);

        $context->validated($processId, AttributeListDto::notSet());
    }

    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_could_not_be_started_with_A_method_other_then_initialize_3()
    {
        $this->expectException(InvalidRemoteVettingContextException::class);
        $this->expectExceptionMessage('No remote vetting process found');

        $processId = ProcessId::create('12345');

        $context = new RemoteVettingContext($this->session);

        $context->done($processId);
    }


    /**
     * @test
     * @group rv
     */
    public function a_remote_vetting_process_holds_the_name_of_the_identity_provider()
    {
        $token = $this->createToken();

        $context = new RemoteVettingContext($this->session);

        $context->initialize('IRMA', $token, AttributeListDto::notSet());


        $this->assertSame('IRMA', $context->getIdentityProviderSlug());
    }


    /**
     * @return RemoteVettingTokenDto
     */
    private function createToken()
    {
        $identityId = Uuid::generate();
        $secondFactorId = Uuid::generate();

        return RemoteVettingTokenDto::create($identityId, $secondFactorId);
    }
}
