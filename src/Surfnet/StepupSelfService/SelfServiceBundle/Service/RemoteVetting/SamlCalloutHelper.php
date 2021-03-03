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

use Psr\Log\LoggerInterface;
use SAML2\Constants;
use SAML2\XML\saml\SubjectConfirmation;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVettingService;
use Symfony\Component\HttpFoundation\Request;

class SamlCalloutHelper
{
    /**
     * @var IdentityProviderFactory
     */
    private $identityProviderFactory;
    /**
     * @var PostBinding
     */
    private $postBinding;
    /**
     * @var RemoteVettingService
     */
    private $remoteVettingService;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ServiceProvider
     */
    private $serviceProvider;

    public function __construct(
        IdentityProviderFactory $identityProviderFactory,
        ServiceProviderFactory $serviceProviderFactory,
        PostBinding $postBinding,
        RemoteVettingService $remoteVettingService,
        LoggerInterface $logger
    ) {
        $this->identityProviderFactory = $identityProviderFactory;
        $this->serviceProvider = $serviceProviderFactory->create();
        $this->postBinding = $postBinding;
        $this->remoteVettingService = $remoteVettingService;
        $this->logger = $logger;
    }

    /**
     * @param string $identityProviderSlug
     * @return string
     */
    public function createAuthnRequest($identityProviderSlug)
    {
        $this->logger->info(sprintf('Creating a SAML2 AuthnRequest to send to the %s remote vetting IdP', $identityProviderSlug));

        $identityProvider = $this->identityProviderFactory->create($identityProviderSlug);
        $authnRequest = AuthnRequestFactory::createNewRequest($this->serviceProvider, $identityProvider);

        // Set NameId
        $authnRequest->setSubject('', Constants::NAMEID_UNSPECIFIED);

        // Set AuthnContextClassRef
        $authnRequest->setAuthenticationContextClassRef(Constants::AC_UNSPECIFIED);

        // Handle validating state
        $this->remoteVettingService->startValidation(ProcessId::create($authnRequest->getRequestId()));

        // Create redirect response.
        $query = $authnRequest->buildRequestQuery();

        return sprintf(
            '%s?%s',
            $identityProvider->getSsoUrl(),
            $query
        );
    }

    /**
     * @param Request $request
     * @param string $identityProviderSlug
     * @return ProcessId
     */
    public function handleResponse(Request $request, $identityProviderSlug)
    {
        $identityProvider = $this->identityProviderFactory->create($identityProviderSlug);

        $this->logger->info(sprintf('Process the SAML Respons received from the %s remote vetting IdP', $identityProviderSlug));

        $assertion = $this->postBinding->processResponse(
            $request,
            $identityProvider,
            $this->serviceProvider
        );

        /** @var SubjectConfirmation $subjectConfirmation */
        $subjectConfirmation = $assertion->getSubjectConfirmation()[0];
        $requestId = $subjectConfirmation->SubjectConfirmationData->InResponseTo;

        // Create log DTO in order to store
        $attributeLogDto = new AttributeListDto(
            $assertion->getAttributes(),
            (string)$subjectConfirmation->NameID
        );

        // Handle validated state
        $processId = ProcessId::create($requestId);
        $this->remoteVettingService->finishValidation($processId, $attributeLogDto);
        return $processId;
    }
}
