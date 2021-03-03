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

use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingContextException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingProcessDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\State\RemoteVettingState;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\State\RemoteVettingStateInitialised;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RemoteVettingContext
{
    const SESSION_KEY = 'remote-vetting-process';

    /**
     * @var RemoteVettingState
     */
    private $state;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
        $this->state = new RemoteVettingStateInitialised();
    }

    /**
     * Do not use this method directly, this method is used to control state in the RemoteVettingState implementations.
     * This is done in order to comply with the state machine design pattern.
     *
     * @param RemoteVettingState $newState
     */
    public function setState(RemoteVettingState $newState)
    {
        $this->state = $newState;
    }

    /**
     * @param string $identityProviderName
     * @param RemoteVettingTokenDto $token
     */
    public function initialize($identityProviderName, RemoteVettingTokenDto $token)
    {
        $process = $this->state->handleInitialise($this, $identityProviderName, $token);
        $this->saveProcess($process);
    }

    /**
     * @param ProcessId $processId
     */
    public function validating(ProcessId $processId)
    {
        $process = $this->loadProcess();
        $process = $this->state->handleValidating($this, $process, $processId);
        $this->saveProcess($process);
    }

    /**
     * @param ProcessId $processId
     * @param AttributeListDto $xexternalAttributes
     */
    public function validated(ProcessId $processId, AttributeListDto $xexternalAttributes)
    {
        $process = $this->loadProcess();
        $process = $this->state->handleValidated($this, $process, $processId, $xexternalAttributes);
        $this->saveProcess($process);
    }

    /**
     * @param ProcessId $processId
     * @return RemoteVettingProcessDto
     */
    public function done(ProcessId $processId)
    {
        $process = $this->loadProcess();
        $token = $this->state->handleDone($this, $process, $processId);
        $this->saveProcess($process);
        return $token;
    }

    /**
     * @return RemoteVettingTokenDto
     */
    public function getValidatedToken()
    {
        $process = $this->loadProcess();
        return $this->state->getValidatedToken($process);
    }

    /**
     * @return AttributeListDto
     */
    public function getAttributes()
    {
        $process = $this->loadProcess();
        return $this->state->getAttributes($process);
    }

    /**
     * @return string
     */
    public function getIdentityProviderSlug()
    {
        $process = $this->loadProcess();
        return $process->getIdentityProviderName();
    }

    /**
     * @return string
     */
    public function getTokenId()
    {
        $process = $this->loadProcess();
        return $process->getToken()->getSecondFactorId();
    }

    /**
     * @return RemoteVettingProcessDto
     */
    private function loadProcess()
    {
        // get active process
        $serialized = $this->session->get(self::SESSION_KEY, null);
        if ($serialized == null) {
            throw new InvalidRemoteVettingContextException('No remote vetting process found');
        }

        $process = RemoteVettingProcessDto::deserialize($serialized);

        // update state from session
        $this->state = $process->getState();

        return $process;
    }

    /**
     * @param RemoteVettingProcessDto $process
     * @return void
     */
    private function saveProcess(RemoteVettingProcessDto $process)
    {
        // save state in session
        $process = RemoteVettingProcessDto::updateState($process, $this->state);
        $this->session->set(self::SESSION_KEY, $process->serialize());
    }
}
