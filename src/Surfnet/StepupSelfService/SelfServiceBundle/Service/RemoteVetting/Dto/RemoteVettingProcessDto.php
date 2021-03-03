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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto;

use Serializable;
use Surfnet\StepupSelfService\SelfServiceBundle\Assert;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\State\RemoteVettingState;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\State\RemoteVettingStateInitialised;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;

class RemoteVettingProcessDto implements Serializable
{
    /**
     * @var ProcessId
     */
    private $processId;
    /**
     * @var RemoteVettingTokenDto
     */
    private $token;
    /**
     * @var RemoteVettingState|null
     */
    private $state = null;
    /**
     * @var AttributeListDto
     */
    private $attributes;
    /**
     * @var string
     */
    private $identityProviderName;

    /**
     * @param ProcessId $processId
     * @param RemoteVettingTokenDto $token
     * @param string $identityProviderName
     * @return RemoteVettingProcessDto
     * @throws \Assert\AssertionFailedException
     */
    public static function create(ProcessId $processId, RemoteVettingTokenDto $token, $identityProviderName)
    {
        return new self($processId, $token, new RemoteVettingStateInitialised(), AttributeListDto::notSet(), $identityProviderName);
    }

    /**
     * @param string $serialized
     * @return RemoteVettingProcessDto
     */
    public static function deserialize($serialized)
    {
        $instance = new self(
            ProcessId::notSet(),
            RemoteVettingTokenDto::notSet(),
            new RemoteVettingStateInitialised(),
            AttributeListDto::notSet(),
            ''
        );
        $instance->unserialize($serialized);
        return $instance;
    }

    /**
     * @param RemoteVettingProcessDto $process
     * @param RemoteVettingState $state
     * @return RemoteVettingProcessDto
     */
    public static function updateState(RemoteVettingProcessDto $process, RemoteVettingState $state)
    {
        return new self($process->getProcessId(), $process->getToken(), $state, $process->getAttributes(), $process->getIdentityProviderName());
    }

    /**
     * @param ProcessId $processId
     * @param RemoteVettingTokenDto $token
     * @param RemoteVettingState $state
     * @param AttributeListDto $attributes
     * @param string $identityProviderName
     * @throws \Assert\AssertionFailedException
     */
    private function __construct(
        ProcessId $processId,
        RemoteVettingTokenDto $token,
        RemoteVettingState $state,
        AttributeListDto $attributes,
        $identityProviderName
    ) {
        Assert::string($identityProviderName, 'The $identityProviderName in an RemoteVettingProcessDto must be a string value');

        $this->processId = $processId;
        $this->token = $token;
        $this->state = $state;
        $this->attributes = $attributes;
        $this->identityProviderName = $identityProviderName;
    }

    /**
     * @return ProcessId
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @return RemoteVettingTokenDto
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return RemoteVettingState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return AttributeListDto
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param AttributeListDto $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return string
     */
    public function getIdentityProviderName()
    {
        return $this->identityProviderName;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        $stateClass = !is_null($this->state) ? get_class($this->state) : null;

        $data = [
            'processId' => json_encode($this->processId->getProcessId()),
            'token' => $this->token->serialize(),
            'state' => json_encode($stateClass),
            'attributes' => $this->attributes->serialize(),
            'identityProvider' => json_encode($this->identityProviderName),
        ];

        $params = [];
        foreach ($data as $key => $value) {
            $params[] = json_encode($key).":{$value}";
        }

        return '{'.implode(',', $params).'}';
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        $stateClass = !is_null($data['state']) ? new $data['state']() : null;

        $this->processId = ProcessId::create($data['processId']);
        $this->token = RemoteVettingTokenDto::deserialize(json_encode($data['token']));
        $this->state = $stateClass;
        $this->attributes = AttributeListDto::deserialize(json_encode($data['attributes']));
        $this->identityProviderName = $data['identityProvider'];
    }
}
