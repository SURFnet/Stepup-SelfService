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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Security\Authentication\Provider;

use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\SamlBundle\SAML2\Response\AssertionAdapter;
use Surfnet\StepupMiddlewareClientBundle\Identity\Dto\Identity;
use Surfnet\StepupMiddlewareClientBundle\Uuid\Uuid;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\MissingRequiredAttributeException;
use Surfnet\StepupSelfService\SelfServiceBundle\Locale\PreferredLocaleProvider;
use Surfnet\StepupSelfService\SelfServiceBundle\Security\Authentication\Token\SamlToken;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\IdentityService;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class SamlProvider implements AuthenticationProviderInterface
{
    /**
     * @var \Surfnet\StepupSelfService\SelfServiceBundle\Service\IdentityService
     */
    private $identityService;

    /**
     * @var \Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary
     */
    private $attributeDictionary;

    /**
     * @var \Surfnet\StepupSelfService\SelfServiceBundle\Locale\PreferredLocaleProvider
     */
    private $preferredLocaleProvider;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        IdentityService $identityService,
        AttributeDictionary $attributeDictionary,
        PreferredLocaleProvider $preferredLocaleProvider,
        LoggerInterface $logger
    ) {
        $this->identityService = $identityService;
        $this->attributeDictionary = $attributeDictionary;
        $this->preferredLocaleProvider = $preferredLocaleProvider;
        $this->logger = $logger;
    }

    /**
     * @param  SamlToken|TokenInterface $token
     * @return TokenInterface|void
     */
    public function authenticate(TokenInterface $token)
    {
        $translatedAssertion = $this->attributeDictionary->translate($token->assertion);

        $nameId         = $translatedAssertion->getNameID();
        $institution    = $this->getSingleStringValue('schacHomeOrganization', $translatedAssertion);
        $email          = $this->getSingleStringValue('mail', $translatedAssertion);
        $commonName     = $this->getSingleStringValue('commonName', $translatedAssertion);

        $identity = $this->identityService->findByNameIdAndInstitution($nameId, $institution);

        if ($identity === null) {
            $identity                  = new Identity();
            $identity->id              = Uuid::generate();
            $identity->nameId          = $nameId;
            $identity->institution     = $institution;
            $identity->email           = $email;
            $identity->commonName      = $commonName;
            $identity->preferredLocale = $this->preferredLocaleProvider->providePreferredLocale();

            $this->identityService->createIdentity($identity);
        } elseif ($identity->email !== $email || $identity->commonName !== $commonName) {
            $identity->email = $email;
            $identity->commonName = $commonName;

            $this->identityService->updateIdentity($identity);
        }

        $authenticatedToken = new SamlToken(['ROLE_USER']);

        $authenticatedToken->setUser($identity);
        $authenticatedToken->setAttribute(SamlToken::ATTRIBUTE_SET, $translatedAssertion->getAttributeSet());

        return $authenticatedToken;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof SamlToken;
    }

    /**
     * @param string           $attribute
     * @param AssertionAdapter $translatedAssertion
     * @return string
     */
    private function getSingleStringValue($attribute, AssertionAdapter $translatedAssertion)
    {
        $values = $translatedAssertion->getAttributeValue($attribute);

        if (empty($values)) {
            throw new MissingRequiredAttributeException(sprintf('Missing value for required attribute "%s"', $attribute));
        }

        // see https://www.pivotaltracker.com/story/show/121296389
        if (count($values) > 1) {
            $this->logger->warning(sprintf(
                'Found "%d" values for attribute "%s", using first value',
                count($values),
                $attribute
            ));
        }

        $value = reset($values);

        if (!is_string($value)) {
            $message = sprintf(
                'First value of attribute "%s" must be a string, "%s" given',
                $attribute,
                is_object($value) ? get_class($value) : gettype($value)
            );

            $this->logger->warning($message);

            throw new MissingRequiredAttributeException($message);
        }

        return $value;
    }
}
