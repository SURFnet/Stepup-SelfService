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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Controller;

use Psr\Log\LoggerInterface;
use SAML2\Response\Exception\PreconditionNotMetException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\StepupBundle\DateTime\RegistrationExpirationHelper;
use Surfnet\StepupSelfService\SelfServiceBundle\Command\RemoteVetCommand;
use Surfnet\StepupSelfService\SelfServiceBundle\Command\RemoteVetValidationCommand;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingContextException;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidRemoteVettingStateException;
use Surfnet\StepupSelfService\SelfServiceBundle\Form\Type\RemoteVetSecondFactorType;
use Surfnet\StepupSelfService\SelfServiceBundle\Form\Type\RemoteVetValidationType;
use Surfnet\StepupSelfService\SelfServiceBundle\Security\Authentication\Token\SamlToken;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\RemoteVettingTokenDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\SamlCalloutHelper;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\ProcessId;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVettingService;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\SecondFactorService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) Too much coupling dus to glue code nature of this controller.
 *                                                 Could be refactored later on
 */
class RemoteVettingController extends Controller
{
    /**
     * @var RemoteVettingService
     */
    private $remoteVettingService;
    /**
     * @var SamlCalloutHelper
     */
    private $samlCalloutHelper;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RegistrationExpirationHelper
     */
    private $expirationHelper;
    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    public function __construct(
        RemoteVettingService $remoteVettingService,
        SecondFactorService $secondFactorService,
        SamlCalloutHelper $samlCalloutHelper,
        RegistrationExpirationHelper $expirationHelper,
        LoggerInterface $logger
    ) {
        $this->secondFactorService = $secondFactorService;
        $this->remoteVettingService = $remoteVettingService;
        $this->samlCalloutHelper = $samlCalloutHelper;
        $this->expirationHelper = $expirationHelper;
        $this->logger = $logger;
    }

    /**
     * @Template
     * @param Request $request
     * @param string $secondFactorId
     * @param string $identityProviderSlug
     * @return array|Response
     */
    public function remoteVetAction(Request $request, $secondFactorId, $identityProviderSlug)
    {
        $identity = $this->getIdentity();

        $secondFactor = $this->secondFactorService->findOneVerified($secondFactorId);
        if ($secondFactor === null ||
            $secondFactor->identityId != $identity->id ||
            $this->expirationHelper->hasExpired($secondFactor->registrationRequestedAt)
        ) {
            throw new NotFoundHttpException(
                sprintf("No %s second factor with id '%s' exists.", 'verified', $secondFactorId)
            );
        }

        $command = new RemoteVetCommand();
        $command->identity = $identity;
        $command->secondFactor = $secondFactor;

        $form = $this->createForm(RemoteVetSecondFactorType::class, $command)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = RemoteVettingTokenDto::create(
                $command->identity->id,
                $command->secondFactor->id
            );

            $this->remoteVettingService->start($identityProviderSlug, $token);

            return new RedirectResponse($this->samlCalloutHelper->createAuthnRequest($identityProviderSlug));
        }

        return [
            'form' => $form->createView(),
            'identity' => $identity,
            'secondFactor' => $secondFactor,
        ];
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acsAction(Request $request)
    {
        $this->logger->info('Receiving response from the remote IdP');

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session')->getFlashBag();

        $this->logger->info('Load the attributes from the saml response');

        try {
            $processId = $this->samlCalloutHelper->handleResponse($request, $this->remoteVettingService->getActiveIdentityProviderSlug());
        } catch (InvalidRemoteVettingStateException $e) {
            $this->logger->error($e->getMessage());
            $flashBag->add('error', 'ss.second_factor.revoke.alert.remote_vetting_failed');
            return $this->redirectToRoute('ss_second_factor_list');
        } catch (PreconditionNotMetException $e) {
            $this->logger->error($e->getMessage());
            $flashBag->add('error', 'ss.second_factor.revoke.alert.remote_vetting_failed');
            return $this->redirectToRoute('ss_second_factor_list');
        } catch (InvalidRemoteVettingContextException $e) {
            $this->logger->error($e->getMessage());
            return $this->redirectToRoute('ss_second_factor_list');
        }

        return $this->redirectToRoute('ss_second_factor_remote_vet_match', [
            'processId' => $processId->getProcessId(),
        ]);

        return $this->redirectToRoute('ss_second_factor_list');
    }

    /**
     * @param Request $request
     * @param $processId
     * @return Response
     */
    public function remoteVetMatchAction(Request $request, $processId)
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->get('session')->getFlashBag();

        /** @var SamlToken $samlToken */
        $samlToken = $this->container->get('security.token_storage')->getToken();

        $localAttributes = AttributeListDto::fromAttributeSet($samlToken->getAttribute(SamlToken::ATTRIBUTE_SET));

        $command = new RemoteVetValidationCommand();

        try {
            $command->matches = $this->remoteVettingService->getAttributeMatchCollection($localAttributes);

            $form = $this->createForm(RemoteVetValidationType::class, $command)->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                /** @var RemoteVetValidationCommand $command */$command = $form->getData();

                $token =  $this->remoteVettingService->done(
                    ProcessId::create($processId),
                    $this->getIdentity(),
                    $localAttributes,
                    $command->matches,
                    (string)$command->remarks
                );

                $command = new RemoteVetCommand();
                $command->identity = $token->getIdentityId();
                $command->secondFactor = $token->getSecondFactorId();

                if ($this->secondFactorService->remoteVet($command)) {
                    $flashBag->add('success', 'ss.second_factor.revoke.alert.remote_vetting_successful');
                } else {
                    $flashBag->add('error', 'ss.second_factor.revoke.alert.remote_vetting_failed');
                }

                return $this->redirectToRoute('ss_second_factor_list');
            }

            return $this->render('SurfnetStepupSelfServiceSelfServiceBundle:remote_vetting:validation.html.twig', [
                'form' => $form->createView(),
            ]);
        } catch (InvalidRemoteVettingStateException $e) {
            $this->logger->error($e->getMessage());
            $flashBag->add('error', 'ss.second_factor.revoke.alert.remote_vetting_failed');
            return $this->redirectToRoute('ss_second_factor_list');
        } catch (InvalidRemoteVettingContextException $e) {
            $this->logger->error($e->getMessage());
            return $this->redirectToRoute('ss_second_factor_list');
        }
    }
}