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

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidArgumentException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption\IdentityEncrypter;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Encryption\IdentityWriterInterface;

class IdentityEncrypterTest extends TestCase
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

        $this->config = m::mock(RemoteVettingConfiguration::class);
        $this->writer = m::mock(IdentityWriterInterface::class);
        $this->encrypter = new IdentityEncrypter($this->config, $this->writer);
    }

    /**
     * @test
     */
    public function happy_flow_should_succeed()
    {
        $this->config
            ->shouldReceive('getPublicKey')
            ->andReturn($this->publicKey);

        $this->writer
            ->shouldReceive('write')
            ->withArgs(function ($data) use (&$encryptedData ){
                $encryptedData = $data;
                return true;
            });

        $nameId = 'a-random-nameid@something.else';

        $data = new AttributeListDto(['email' => ['johndoe@example.com'], 'firstName' => ['John']], $nameId);
        $this->encrypter->encrypt($data->serialize());

        // Assert result
        $decryptedData = Decrypter::decrypt($encryptedData, $this->privateKey);
        $this->assertSame($data->serialize(), $decryptedData);
    }

    /**
     * @test
     */
    public function a_large_chunk_should_succeed()
    {
        $this->config
            ->shouldReceive('getPublicKey')
            ->andReturn($this->publicKey);

        $this->writer
            ->shouldReceive('write')
            ->withArgs(function ($data) use (&$encryptedData ){
                $encryptedData = $data;
                return true;
            });

        $nameId = 'a-random-nameid@something.else';

        $data = new AttributeListDto(['email' => ['johndoe@example.com'], 'firstName' => ['John']], $nameId);
        $this->encrypter->encrypt($data->serialize());

        // Assert result
        $decryptedData = Decrypter::decrypt($encryptedData, $this->privateKey);
        $this->assertSame($data->serialize(), $decryptedData);
    }

    /**
     * @test
     */
    public function an_invalid_key_should_fail_non_string()
    {
        $this->config
            ->shouldReceive('getPublicKey')
            ->andReturn(8373292782);

        $nameId = 'a-random-nameid@something.else';
        $data = new AttributeListDto(['email' => ['johndoe@example.com'], 'firstName' => ['John']], $nameId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input was provided to the encrypt method');
        $this->encrypter->encrypt($data->serialize());
    }

    /**
     * @test
     */
    public function an_invalid_key_should_fail_bad_key_format()
    {
        $this->config
            ->shouldReceive('getPublicKey')
            ->andReturn('invalid key');

        $nameId = 'a-random-nameid@something.else';

        $data = new AttributeListDto(['email' => ['johndoe@example.com'], 'firstName' => ['John'],'a-lot-of-data' => [$this->generateRandomString(5000)]], $nameId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reading RSA public key failed');
        $this->encrypter->encrypt($data->serialize());
    }

    private function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"\\}\'';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
