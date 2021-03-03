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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\State;

use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingStateException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingProcessDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;

abstract class AbstractRemoteVettingState
{
    /**
     * Do not set state to be able to (de-)serialize state
     * The vetting state is only used to change behaviour and therefore state is not wanted.
     */
    public function __construct()
    {
    }

    public function handleInitialise(RemoteVettingContext $context, $identityProviderName, RemoteVettingTokenDto $token)
    {
        throw new InvalidRemoteVettingStateException('Unable to initialise the validation of a token');
    }

    public function handleValidating(RemoteVettingContext $context, RemoteVettingProcessDto $process, ProcessId $id)
    {
        throw new InvalidRemoteVettingStateException('Unable to start the validation of a token');
    }

    public function handleValidated(RemoteVettingContext $context, RemoteVettingProcessDto $process, ProcessId $id, AttributeListDto $attributeLogDto)
    {
        throw new InvalidRemoteVettingStateException('Unable to finish validation of a token');
    }

    public function handleDone(RemoteVettingContext $context, RemoteVettingProcessDto $process)
    {
        throw new InvalidRemoteVettingStateException('Unable to end the validation of a token');
    }

    public function getValidatedToken(RemoteVettingProcessDto $process)
    {
        throw new InvalidRemoteVettingStateException('Unable to find a validated token');
    }

    public function getAttributes(RemoteVettingProcessDto $process)
    {
        throw new InvalidRemoteVettingStateException('Unable to find attributes');
    }
}
