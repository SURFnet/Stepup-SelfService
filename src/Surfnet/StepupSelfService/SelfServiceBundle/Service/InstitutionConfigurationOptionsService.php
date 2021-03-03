<?php

/**
 * Copyright 2016 SURFnet B.V.
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

use Surfnet\StepupMiddlewareClientBundle\Configuration\Dto\InstitutionConfigurationOptions;
use Surfnet\StepupMiddlewareClientBundle\Configuration\Service\InstitutionConfigurationOptionsService as ApiInstitutionConfigurationOptionsService;

class InstitutionConfigurationOptionsService
{
    /**
     * @var ApiInstitutionConfigurationOptionsService
     */
    private $apiInstitutionConfigurationOptionsService;

    public function __construct(ApiInstitutionConfigurationOptionsService $apiInstitutionConfigurationOptionsService)
    {
        $this->apiInstitutionConfigurationOptionsService = $apiInstitutionConfigurationOptionsService;
    }

    /**
     * @param string $institution
     * @return null|InstitutionConfigurationOptions
     */
    public function getInstitutionConfigurationOptionsFor($institution)
    {
        return $this->apiInstitutionConfigurationOptionsService->getInstitutionConfigurationOptionsFor($institution);
    }
}
