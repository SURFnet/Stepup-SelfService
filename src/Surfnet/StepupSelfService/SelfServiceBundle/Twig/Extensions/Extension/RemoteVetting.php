<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Twig\Extensions\Extension;

use Symfony\Component\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class RemoteVetting extends AbstractExtension
{
    private const TRANSLATION_TYPE_SCHACHOME = 'schachome';
    private const TRANSLATION_TYPE_ATTRIBUTES = 'attributes';

    /**
     * @var string
     */
    private $translationFile;
    /**
     * @var array|null
     */
    private $translations = null;

    public function __construct(string $translationFile)
    {
        $this->translationFile = $translationFile;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('trans_rv_attribute', [$this, 'translateAttributeKey']),
            new TwigFilter('trans_rv_schachome', [$this, 'translateSchachome']),
        ];
    }

    public function translateAttributeKey($attributeName, $locale)
    {
        return $this->getTranslation(self::TRANSLATION_TYPE_ATTRIBUTES, $locale, $attributeName);
    }


    public function translateSchachome($schachome, $locale)
    {
        return $this->getTranslation(self::TRANSLATION_TYPE_SCHACHOME, $locale, $schachome);
    }

    private function getTranslation(string $type, string $locale, string $key)
    {
        $this->loadTranslationCache();

        if (!array_key_exists($type, $this->translations) ||
            !array_key_exists($key, $this->translations[$type]) ||
            !array_key_exists($locale, $this->translations[$type][$key])
        ) {
            return $key;
        }
        return $this->translations[$type][$key][$locale];
    }

    private function loadTranslationCache()
    {
        if ($this->translations === null) {
            $content = file_get_contents($this->translationFile);
            $this->translations = json_decode($content, true);
        }
    }
}
