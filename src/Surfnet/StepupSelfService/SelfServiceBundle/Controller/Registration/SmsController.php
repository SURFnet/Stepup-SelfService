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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Controller\Registration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\StepupSelfService\SelfServiceBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupSelfService\SelfServiceBundle\Command\VerifySmsChallengeCommand;
use Surfnet\StepupSelfService\SelfServiceBundle\Controller\Controller;
use Surfnet\StepupSelfService\SelfServiceBundle\Form\Type\SendSmsChallengeType;
use Surfnet\StepupSelfService\SelfServiceBundle\Form\Type\VerifySmsChallengeType;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\SmsSecondFactorService;
use Symfony\Component\HttpFoundation\Request;

class SmsController extends Controller
{
    /**
     * @Template
     */
    public function sendChallengeAction(Request $request)
    {
        $this->assertSecondFactorEnabled('sms');

        $identity = $this->getIdentity();

        $command = new SendSmsChallengeCommand();
        $form = $this->createForm(SendSmsChallengeType::class, $command)->handleRequest($request);

        /** @var SmsSecondFactorService $service */
        $service = $this->get('surfnet_stepup_self_service_self_service.service.sms_second_factor');
        $otpRequestsRemaining = $service->getOtpRequestsRemainingCount();
        $maximumOtpRequests = $service->getMaximumOtpRequestsCount();
        $viewVariables = ['otpRequestsRemaining' => $otpRequestsRemaining, 'maximumOtpRequests' => $maximumOtpRequests];

        if ($form->isSubmitted() && $form->isValid()) {
            $command->identity = $identity->id;
            $command->institution = $identity->institution;

            if ($otpRequestsRemaining === 0) {
                $this->addFlash('error', 'ss.prove_phone_possession.challenge_request_limit_reached');
                return array_merge(['form' => $form->createView()], $viewVariables);
            }

            if ($service->sendChallenge($command)) {
                return $this->redirect($this->generateUrl('ss_registration_sms_prove_possession'));
            } else {
                $this->addFlash('error', 'ss.prove_phone_possession.send_sms_challenge_failed');
            }
        }

        return array_merge(
            [
                'form' => $form->createView(),
                'verifyEmail' => $this->emailVerificationIsRequired(),
            ],
            $viewVariables
        );
    }

    /**
     * @Template
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function provePossessionAction(Request $request)
    {
        $this->assertSecondFactorEnabled('sms');

        /** @var SmsSecondFactorService $service */
        $service = $this->get('surfnet_stepup_self_service_self_service.service.sms_second_factor');

        if (!$service->hasSmsVerificationState()) {
            $this->get('session')->getFlashBag()->add('notice', 'ss.registration.sms.alert.no_verification_state');

            return $this->redirectToRoute('ss_registration_sms_send_challenge');
        }

        $identity = $this->getIdentity();

        $command = new VerifySmsChallengeCommand();
        $command->identity = $identity->id;

        $form = $this->createForm(VerifySmsChallengeType::class, $command)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $service->provePossession($command);

            if ($result->isSuccessful()) {
                $service->clearSmsVerificationState();

                if ($this->emailVerificationIsRequired()) {
                    return $this->redirectToRoute(
                        'ss_registration_email_verification_email_sent',
                        ['secondFactorId' => $result->getSecondFactorId()]
                    );
                } else {
                    return $this->redirectToRoute(
                        'ss_second_factor_remote_vetting_types',
                        ['secondFactorId' => $result->getSecondFactorId()]
                    );
                }
            } elseif ($result->wasIncorrectChallengeResponseGiven()) {
                $this->addFlash('error', 'ss.prove_phone_possession.incorrect_challenge_response');
            } elseif ($result->hasChallengeExpired()) {
                $this->addFlash('error', 'ss.prove_phone_possession.challenge_expired');
            } elseif ($result->wereTooManyAttemptsMade()) {
                $this->addFlash('error', 'ss.prove_phone_possession.too_many_attempts');
            } else {
                $this->addFlash('error', 'ss.prove_phone_possession.proof_of_possession_failed');
            }
        }

        return [
            'form' => $form->createView(),
            'verifyEmail' => $this->emailVerificationIsRequired(),
        ];
    }
}
