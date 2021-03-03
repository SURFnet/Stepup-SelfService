<?php

/**
 * Copyright 2016 SURFnet bv
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Security\Session;

use DateTime as CoreDateTime;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\LogicException;
use Surfnet\StepupSelfService\SelfServiceBundle\Security\Authentication\Session\SessionStorage;
use Surfnet\StepupSelfService\SelfServiceBundle\Value\DateTime;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionStorageTest extends TestCase
{
    /**
     * Ensures that any modifications to the time do not bleed through to other tests
     */
    public function tearDown(): void
    {
        $this->setCurrentTime(null);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_authentication_moment_can_be_logged()
    {
        $sessionStorage = new SessionStorage(new FakeSession());

        $sessionStorage->logAuthenticationMoment();

        $this->assertInstanceOf(SessionStorage::class, $sessionStorage);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_authentication_moment_cannot_be_logged_twice()
    {
        $this->expectException(LogicException::class);

        $sessionStorage = new SessionStorage(new FakeSession());

        $sessionStorage->logAuthenticationMoment();
        $sessionStorage->logAuthenticationMoment();
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function whether_or_not_an_authentication_moment_is_logged_can_be_queried()
    {
        $sessionStorage = new SessionStorage(new FakeSession());

        $this->assertFalse($sessionStorage->isAuthenticationMomentLogged());

        $sessionStorage->logAuthenticationMoment();

        $this->assertTrue($sessionStorage->isAuthenticationMomentLogged());
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function a_logged_authentication_moment_can_be_retrieved()
    {
        $sessionStorage = new SessionStorage(new FakeSession());
        $now = new DateTime(new CoreDateTime('@1000'));
        $this->setCurrentTime($now);

        $sessionStorage->logAuthenticationMoment();

        $authenticationMoment = $sessionStorage->getAuthenticationMoment();

        $this->assertEquals($now, $authenticationMoment);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function attempting_to_retrieve_an_authentication_moment_when_not_yet_logged_causes_an_exception_to_be_thrown()
    {
        $this->expectException(LogicException::class);

        $sessionStorage = new SessionStorage(new FakeSession());

        $sessionStorage->getAuthenticationMoment();
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function an_interaction_can_be_logged()
    {
        $sessionStorage = new SessionStorage(new FakeSession());

        $sessionStorage->updateLastInteractionMoment();

        $this->assertInstanceOf(SessionStorage::class, $sessionStorage);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_moment_of_interaction_can_be_retrieved()
    {
        $sessionStorage = new SessionStorage(new FakeSession());

        $now = new DateTime(new CoreDateTime('@1000'));
        $this->setCurrentTime($now);

        $sessionStorage->updateLastInteractionMoment();

        $interactionMoment = $sessionStorage->getLastInteractionMoment();
        $this->assertEquals($now, $interactionMoment);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function an_interaction_is_logged_when_an_authentication_is_logged()
    {
        $sessionStorage = new SessionStorage(new FakeSession());
        // fixate time, just to be sure when comparing the moments...
        $now = new DateTime(new CoreDateTime('@1000'));
        $this->setCurrentTime($now);

        $sessionStorage->logAuthenticationMoment();

        $authenticationMoment = $sessionStorage->getAuthenticationMoment();
        $interactionMoment    = $sessionStorage->getLastInteractionMoment();

        $this->assertEquals(
            $authenticationMoment,
            $interactionMoment,
            'AuthenticationMoment and InteractionMoment should be the same after an authentication'
        );
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_moment_of_interaction_can_be_updated()
    {
        $sessionStorage = new SessionStorage(new FakeSession());

        $now   = new DateTime(new CoreDateTime('@1000'));
        $later = new DateTime(new CoreDateTime('@2000'));

        $this->setCurrentTime($now);

        $sessionStorage->updateLastInteractionMoment();
        $firstInteraction = $sessionStorage->getLastInteractionMoment();

        $this->setCurrentTime($later);

        $sessionStorage->updateLastInteractionMoment();
        $secondInteraction = $sessionStorage->getLastInteractionMoment();

        $this->assertEquals($now, $firstInteraction);
        $this->assertEquals($later, $secondInteraction);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_existence_of_a_moment_interaction_can_be_queried()
    {
        $sessionStorage = new SessionStorage(new FakeSession());

        $this->assertFalse($sessionStorage->hasSeenInteraction());

        $sessionStorage->updateLastInteractionMoment();

        $this->assertTrue($sessionStorage->hasSeenInteraction());
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_current_uri_can_be_stored_in_the_session()
    {
        $sessionStorage = new SessionStorage(new FakeSession());
        $originalUri = 'https://selfservice.stepup.test/some/path?with=param#hashvalue';

        $sessionStorage->setCurrentRequestUri($originalUri);
        $retrievedUri = $sessionStorage->getCurrentRequestUri();

        $this->assertSame($originalUri, $retrievedUri);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function a_request_id_can_be_stored_in_the_session()
    {
        $sessionStorage    = new SessionStorage(new FakeSession());
        $originalRequestId = '_' . bin2hex(openssl_random_pseudo_bytes(32));

        $sessionStorage->setRequestId($originalRequestId);
        $retrievedRequestId = $sessionStorage->getRequestId();

        $this->assertSame($originalRequestId, $retrievedRequestId);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function the_presence_of_a_request_id_can_be_queried()
    {
        $sessionStorage    = new SessionStorage(new FakeSession());
        $originalRequestId = '_' . bin2hex(openssl_random_pseudo_bytes(32));

        $this->assertFalse($sessionStorage->hasRequestId());

        $sessionStorage->setRequestId($originalRequestId);

        $this->assertTrue($sessionStorage->hasRequestId());
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function a_stored_request_id_can_be_cleared()
    {
        $sessionStorage    = new SessionStorage(new FakeSession());
        $originalRequestId = '_' . bin2hex(openssl_random_pseudo_bytes(32));

        $this->assertFalse($sessionStorage->hasRequestId());

        $sessionStorage->setRequestId($originalRequestId);

        $this->assertTrue($sessionStorage->hasRequestId());

        $sessionStorage->clearRequestId();

        $this->assertFalse($sessionStorage->hasRequestId());
        $this->assertNull($sessionStorage->getRequestId());
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function a_session_can_be_invalidated()
    {
        $session = Mockery::mock(SessionInterface::class)
            ->shouldReceive('invalidate')
            ->once()
            ->getMock();
        $sessionStorage = new SessionStorage($session);

        $sessionStorage->invalidate();

        $this->assertInstanceOf(SessionStorage::class, $sessionStorage);
    }

    /**
     * @test
     * @group security
     * @group session
     */
    public function a_session_can_be_migrated()
    {
        $session = Mockery::mock(SessionInterface::class)
            ->shouldReceive('migrate')
            ->once()
            ->getMock();
        $sessionStorage = new SessionStorage($session);

        $sessionStorage->migrate();

        $this->assertInstanceOf(SessionStorage::class, $sessionStorage);
    }

    /**
     * Enables the control of time. Setting null as value resets the time to default system determined behaviour
     *
     * @param DateTime|null $now
     */
    private function setCurrentTime(DateTime $now = null)
    {
        $nowProperty = new ReflectionProperty(DateTime::class, 'now');
        $nowProperty->setAccessible(true);
        $nowProperty->setValue($now);
    }
}
