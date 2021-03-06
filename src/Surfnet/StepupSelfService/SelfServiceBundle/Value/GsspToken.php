<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Value;

use Surfnet\StepupSelfService\SamlStepupProviderBundle\Provider\ViewConfig;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidArgumentException;

class GsspToken implements AvailableTokenInterface
{
    /**
     * @var ViewConfig
     */
    private $viewConfig;

    /**
     * @var string
     */
    private $type;

    /**
     * @param ViewConfig $viewConfig
     * @param $type
     * @return GsspToken
     */
    public static function fromViewConfig(ViewConfig $viewConfig, $type)
    {
        if (!is_string($type) || empty($type)) {
            throw InvalidArgumentException::invalidType('a non empty string', 'type', $type);
        }

        return new self($viewConfig, $type);
    }

    /**
     * GsspToken constructor.
     * @param ViewConfig $viewConfig
     * @param string $type
     */
    private function __construct(ViewConfig $viewConfig, $type)
    {
        $this->viewConfig = $viewConfig;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return 'ss_registration_gssf_initiate';
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getLoaLevel()
    {
        return (int) $this->viewConfig->getLoa();
    }

    /**
     * @return boolean
     */
    public function isGssp()
    {
        return true;
    }

    public function getRouteParams()
    {
        return [
            'provider' => $this->type,
        ];
    }

    public function getViewConfig()
    {
        return $this->viewConfig;
    }
}
