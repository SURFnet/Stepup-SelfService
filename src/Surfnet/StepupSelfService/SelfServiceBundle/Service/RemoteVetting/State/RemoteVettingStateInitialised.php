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

use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingProcessDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;

class RemoteVettingStateInitialised extends AbstractRemoteVettingState implements RemoteVettingState
{
    public function handleInitialise(RemoteVettingContext $context, $identityProviderName, RemoteVettingTokenDto $token)
    {
        return RemoteVettingProcessDto::create(ProcessId::notSet(), $token, $identityProviderName);
    }

    public function handleValidating(RemoteVettingContext $context, RemoteVettingProcessDto $process, ProcessId $id)
    {
        // set new process id on validation start
        $context->setState(new RemoteVettingStateValidating());

        return RemoteVettingProcessDto::create($id, $process->getToken(), $process->getIdentityProviderName());
    }
}
