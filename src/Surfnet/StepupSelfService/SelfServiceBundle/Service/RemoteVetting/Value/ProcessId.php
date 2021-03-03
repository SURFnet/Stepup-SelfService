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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value;

class ProcessId
{
    private $processId;

    /**
     * @param $processId
     * @return ProcessId
     */
    public static function create($processId)
    {
        return new self($processId);
    }

    /**
     * @param $processId
     * @return ProcessId
     */
    public static function notSet()
    {
        return new self('');
    }

    /**
     * @param $processId
     */
    private function __construct($processId)
    {
        $this->processId = $processId;
    }

    /**
     * @return mixed
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    public function isValid(ProcessId $id)
    {
        if (empty($this->processId)) {
            return false;
        }
        return $this->processId == $id->getProcessId();
    }
}
