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

use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity;
use Surfnet\StepupSelfService\SelfServiceBundle\Security\Authentication\Token\SamlToken;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as FrameworkController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class Controller extends FrameworkController
{
    /**
     * @return Identity
     * @throws AccessDeniedException When the registrant isn't registered using a SAML token.
     */
    protected function getIdentity()
    {
        /** @var SecurityContextInterface $tokenStorage */
        $tokenStorage = $this->get('security.context');
        $token = $tokenStorage->getToken();

        if (!$token instanceof SamlToken) {
            throw new AccessDeniedException('Registrant must be authenticated using a SAML token.');
        }

        $user = $token->getUser();

        if (!$user instanceof Identity) {
            $actualType = is_object($token) ? get_class($token) : gettype($token);

            throw new \RuntimeException(
                sprintf(
                    "SAML token did not contain user of type '%s', but one of type '%s'",
                    'Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity',
                    $actualType
                )
            );
        }

        return $user;
    }
}