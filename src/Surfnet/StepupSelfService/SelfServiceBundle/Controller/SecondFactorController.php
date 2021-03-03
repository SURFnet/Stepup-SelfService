<?php

/**
 * Copyright 2014 SURFnet bv
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

use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\StepupSelfService\SelfServiceBundle\Command\RevokeCommand;
use Surfnet\StepupSelfService\SelfServiceBundle\Form\Type\RevokeSecondFactorType;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\SecondFactorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SecondFactorController extends Controller
{
    /**
     * @Template
     */
    public function listAction()
    {
        $identity = $this->getIdentity();
        $institution = $this->getIdentity()->institution;
        $institutionConfigurationOptions = $this->get('self_service.service.institution_configuration_options')
            ->getInstitutionConfigurationOptionsFor($institution);
        /** @var SecondFactorService $service */
        $service = $this->get('surfnet_stepup_self_service_self_service.service.second_factor');
        // Get all available second factors from the config.
        $allSecondFactors = $this->getParameter('ss.enabled_second_factors');

        $expirationHelper = $this->get('surfnet_stepup.registration_expiration_helper');

        $secondFactors = $service->getSecondFactorsForIdentity(
            $identity,
            $allSecondFactors,
            $institutionConfigurationOptions->allowedSecondFactors,
            $institutionConfigurationOptions->numberOfTokensPerIdentity
        );

        return [
            'email' => $identity->email,
            'maxNumberOfTokens' => $secondFactors->getMaximumNumberOfRegistrations(),
            'registrationsLeft' => $secondFactors->getRegistrationsLeft(),
            'unverifiedSecondFactors' => $secondFactors->unverified,
            'verifiedSecondFactors' => $secondFactors->verified,
            'vettedSecondFactors' => $secondFactors->vetted,
            'availableSecondFactors' => $secondFactors->available,
            'expirationHelper' => $expirationHelper,
        ];
    }

    /**
     * @Template
     * @param Request $request
     * @param string $state
     * @param string $secondFactorId
     * @return array|Response
     */
    public function revokeAction(Request $request, $state, $secondFactorId)
    {
        $identity = $this->getIdentity();

        /** @var SecondFactorService $service */
        $service = $this->get('surfnet_stepup_self_service_self_service.service.second_factor');
        if (!$service->identityHasSecondFactorOfStateWithId($identity->id, $state, $secondFactorId)) {
            $this->get('logger')->error(sprintf(
                'Identity "%s" tried to revoke "%s" second factor "%s", but does not own that second factor',
                $identity->id,
                $state,
                $secondFactorId
            ));
            throw new NotFoundHttpException();
        }

        switch ($state) {
            case 'unverified':
                $secondFactor = $service->findOneUnverified($secondFactorId);
                break;
            case 'verified':
                $secondFactor = $service->findOneVerified($secondFactorId);
                break;
            case 'vetted':
                $secondFactor = $service->findOneVetted($secondFactorId);
                break;
            default:
                throw new LogicException('There are no other types of second factor.');
        }

        if ($secondFactor === null) {
            throw new NotFoundHttpException(
                sprintf("No %s second factor with id '%s' exists.", $state, $secondFactorId)
            );
        }

        $command = new RevokeCommand();
        $command->identity = $identity;
        $command->secondFactor = $secondFactor;

        $form = $this->createForm(RevokeSecondFactorType::class, $command)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $this->get('session')->getFlashBag();

            if ($service->revoke($command)) {
                $flashBag->add('success', 'ss.second_factor.revoke.alert.revocation_successful');
            } else {
                $flashBag->add('error', 'ss.second_factor.revoke.alert.revocation_failed');
            }

            return $this->redirectToRoute('ss_second_factor_list');
        }

        return [
            'form'         => $form->createView(),
            'secondFactor' => $secondFactor,
        ];
    }
}
