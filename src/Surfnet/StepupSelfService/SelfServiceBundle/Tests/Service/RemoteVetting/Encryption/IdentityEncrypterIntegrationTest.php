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

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service\RemoteVetting\Encryption;

use PHPUnit\Framework\TestCase;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption\IdentityEncrypter;

/**
 * By using a fake IdentityWriter we are able to intercept the encrypted data (that would otherwise
 * be stored to a backend) and perform some sanity checks on it.
 */
class IdentityEncrypterIntegrationTest extends TestCase
{
    private $encrypter;

    private $config;

    private $writer;

    private $publicKey = '';

    private $privateKey = '';

    protected function setUp(): void
    {
        $testKeyPath = realpath(__DIR__ . '/../../../Resources/');

        $this->publicKey = file_get_contents($testKeyPath. '/encryption.crt');
        $this->privateKey = file_get_contents($testKeyPath . '/encryption.key');

        $rvPrivateKey = realpath($testKeyPath . '/test.key');
        $config = [
            'encryption_public_key' => $this->publicKey,
            'storage_location' => '/tmp',
        ];
        $this->config = new RemoteVettingConfiguration($rvPrivateKey, $config, []);
        $this->writer = new FakeIdentityWriter();
        $this->encrypter = new IdentityEncrypter($this->config, $this->writer);
    }

    /**
     * Create a simple identity DTO, encrypt and write it. The decrypted data should match that
     * of the data set on the DTO.
     */
    public function test_happy_flow()
    {
        $nameId = 'a-random-nameid@something.else';

        $data = new AttributeListDto(['email' => ['johndoe@example.com'], 'firstName' => ['John']], $nameId);
        $this->encrypter->encrypt($data->serialize());

        $writtenData = $this->writer->getData();

        // Now decrypt the data with the private key to prove the data is actually retrievable
        $result = Decrypter::decrypt($writtenData, $this->privateKey);

        $serialized = json_decode($result, true);

        $this->assertEquals(['johndoe@example.com'], $serialized['attributes']['email']);
        $this->assertEquals(['John'], $serialized['attributes']['firstName']);
    }
}
