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

use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingProcessDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;

interface RemoteVettingState
{
    /**
     * The entity can not contain state to be able to (de-)serialize session data
     */
    public function __construct();

    /**
     * @param string $identityProviderName
     * @param RemoteVettingContext $context
     * @param RemoteVettingTokenDto $token
     * @return RemoteVettingProcessDto
     */
    public function handleInitialise(RemoteVettingContext $context, $identityProviderName, RemoteVettingTokenDto $token);

    /**
     * @param RemoteVettingContext $context
     * @param RemoteVettingProcessDto $process
     * @param ProcessId $id
     * @return RemoteVettingProcessDto
     */
    public function handleValidating(RemoteVettingContext $context, RemoteVettingProcessDto $process, ProcessId $id);

    /**
     * @param RemoteVettingContext $context
     * @param RemoteVettingProcessDto $process
     * @param ProcessId $id
     * @param AttributeListDto $externalAttributes
     * @return RemoteVettingProcessDto
     */
    public function handleValidated(
        RemoteVettingContext $context,
        RemoteVettingProcessDto $process,
        ProcessId $id,
        AttributeListDto $externalAttributes
    );

    /**
     * @param RemoteVettingContext $context
     * @param RemoteVettingProcessDto $process
     * @return RemoteVettingProcessDto
     */
    public function handleDone(RemoteVettingContext $context, RemoteVettingProcessDto $process);

    /**
     * @param RemoteVettingProcessDto $process
     * @return RemoteVettingTokenDto
     */
    public function getValidatedToken(RemoteVettingProcessDto $process);

    /**
     * @param RemoteVettingProcessDto $process
     * @return AttributeListDto
     */
    public function getAttributes(RemoteVettingProcessDto $process);
}
