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

use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingContextException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingProcessDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;

class RemoteVettingStateValidating extends AbstractRemoteVettingState implements RemoteVettingState
{

    public function handleValidated(
        RemoteVettingContext $context,
        RemoteVettingProcessDto $process,
        ProcessId $id,
        AttributeListDto $externalAttributes
    ) {
        if (!$process->getProcessId()->isValid($id)) {
            throw new InvalidRemoteVettingContextException('Invalid remote vetting context found');
        }

        $process->setAttributes($externalAttributes);

        $context->setState(new RemoteVettingStateValidated());

        return $process;
    }
}
