<?php

/**
 * Copyright 2020 SURFnet B.V.
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption;

use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;

class IdentityFilesystemWriter implements IdentityWriterInterface
{
    /**
     * @var RemoteVettingConfiguration
     */
    private $configuration;

    public function __construct(RemoteVettingConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Writes identity data to a data store. The data should be passed as a string.
     *
     * @param string $data
     */
    public function write($data)
    {
        $fileName = $this->createFileName($this->configuration->getLocation());
        file_put_contents($fileName, $data."\n");
    }

    private function createFileName($location)
    {
        $fileName = uniqid('identity_', true);
        return "{$location}/{$fileName}";
    }
}
