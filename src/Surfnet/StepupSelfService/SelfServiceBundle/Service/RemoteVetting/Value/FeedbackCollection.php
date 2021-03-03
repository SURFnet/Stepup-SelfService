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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value;

use ArrayIterator;
use ArrayAccess;
use IteratorAggregate;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;

class FeedbackCollection implements IteratorAggregate, ArrayAccess, AttributeCollectionInterface
{
    /**
     * @var array[]
     */
    private $feedback = [];

    public function getIterator()
    {
        return new ArrayIterator($this->feedback);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->feedback[] = $value;
        } else {
            $this->feedback[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->feedback[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->feedback[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->feedback[$offset]) ? $this->feedback[$offset] : null;
    }

    public function getAttributes()
    {
        return $this->feedback;
    }
}
