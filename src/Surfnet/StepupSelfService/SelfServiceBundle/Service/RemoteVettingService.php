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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\AttributeMapper;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption\IdentityData;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption\IdentityEncrypterInterface;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\RemoteVettingContext;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollectionAggregate;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatchCollection;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\FeedbackCollection;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoteVettingService
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RemoteVettingContext
     */
    private $remoteVettingContext;
    /**
     * @var IdentityEncrypterInterface
     */
    private $identityEncrypter;
    /**
     * @var AttributeMapper
     */
    private $attributeMapper;
    /**
     * @var ApplicationHelper
     */
    private $applicationHelper;


    public function __construct(
        RemoteVettingContext $remoteVettingContext,
        AttributeMapper $attributeMapper,
        IdentityEncrypterInterface $identityEncrypter,
        ApplicationHelper $applicationHelper,
        LoggerInterface $logger
    ) {
        $this->remoteVettingContext = $remoteVettingContext;
        $this->logger = $logger;
        $this->identityEncrypter = $identityEncrypter;
        $this->attributeMapper = $attributeMapper;
        $this->applicationHelper = $applicationHelper;
    }

    /**
     * @param string $identityProviderSlug
     * @param RemoteVettingTokenDto $remoteVettingToken
     */
    public function start($identityProviderSlug, RemoteVettingTokenDto $remoteVettingToken)
    {
        $this->logger->notice('Starting a remote vetting process', [
            'second-factor' => $remoteVettingToken->getSecondFactorId(),
            'identity' => $remoteVettingToken->getIdentityId(),
            'provider' => $identityProviderSlug,
        ]);

        $this->remoteVettingContext->initialize($identityProviderSlug, $remoteVettingToken);
    }

    /**
     * @param ProcessId $processId
     */
    public function startValidation(ProcessId $processId)
    {
        $this->logger->notice('Starting a remote vetting authentication', [
            'second-factor' => $this->remoteVettingContext->getTokenId(),
            'process' => $processId->getProcessId(),
        ]);

        $this->remoteVettingContext->validating($processId);
    }

    /**
     * @param ProcessId $processId
     * @param AttributeListDto $externalAttributes
     */
    public function finishValidation(ProcessId $processId, AttributeListDto $externalAttributes)
    {
        $this->logger->notice('Finishing a remote vetting authentication', [
            'second-factor' => $this->remoteVettingContext->getTokenId(),
            'process' => $processId->getProcessId(),
        ]);

        $this->remoteVettingContext->validated($processId, $externalAttributes);
    }

    /**
     * @param ProcessId $processId
     * @param Identity $identity
     * @param AttributeListDto $localAttributes
     * @param AttributeMatchCollection $attributeMatches
     * @param FeedbackCollection $feedback
     * @return RemoteVettingTokenDto
     */
    public function done(
        ProcessId $processId,
        Identity $identity,
        AttributeListDto $localAttributes,
        AttributeMatchCollection $attributeMatches,
        FeedbackCollection $feedback
    ) {
        $this->remoteVettingContext->done($processId);
        $this->logger->notice('Saving the encrypted match data to the filesystem', [
            'second-factor' => $this->remoteVettingContext->getTokenId(),
            'process' => $processId->getProcessId(),
        ]);

        $identityData = $this->aggregateIdentityData($identity, $localAttributes, $attributeMatches, $feedback);
        $this->identityEncrypter->encrypt($identityData->serialize());

        $this->logger->notice('Finished the remote vetting process', [
            'second-factor' => $this->remoteVettingContext->getTokenId(),
            'process' => $processId->getProcessId(),
        ]);

        return $this->remoteVettingContext->getValidatedToken();
    }

    /**
     * @param AttributeListDto $localAttributes
     * @return AttributeMatchCollection
     */
    public function getAttributeMatchCollection(AttributeListDto $localAttributes)
    {
        $externalAttributes = $this->remoteVettingContext->getAttributes();
        $identityProviderSlug = $this->remoteVettingContext->getIdentityProviderSlug();

        return $this->attributeMapper->map($identityProviderSlug, $localAttributes, $externalAttributes);
    }

    /**
     * @return string
     */
    public function getActiveIdentityProviderSlug()
    {
        return $this->remoteVettingContext->getIdentityProviderSlug();
    }

    /**
     * @param Identity $identity
     * @param AttributeListDto $localAttributes
     * @param AttributeMatchCollection $attributeMatches
     * @param FeedbackCollection $feedback
     * @return IdentityData
     */
    private function aggregateIdentityData(
        Identity $identity,
        AttributeListDto $localAttributes,
        AttributeMatchCollection $attributeMatches,
        FeedbackCollection $feedback
    ) {
        $nameId = $identity->nameId;
        $institution = $identity->institution;
        $version = $this->applicationHelper->getApplicationVersion();
        $remoteVettingSource = $this->remoteVettingContext->getIdentityProviderSlug();

        $attributeCollectionAggregate = new AttributeCollectionAggregate();
        $attributeCollectionAggregate->add('local-attributes', $localAttributes);
        $attributeCollectionAggregate->add('remote-attributes', $this->remoteVettingContext->getAttributes());
        $attributeCollectionAggregate->add('matching-results', $attributeMatches);
        $attributeCollectionAggregate->add('feedback', $feedback);

        return new IdentityData(
            $attributeCollectionAggregate,
            $nameId,
            $version,
            $institution,
            $remoteVettingSource
        );
    }
}
