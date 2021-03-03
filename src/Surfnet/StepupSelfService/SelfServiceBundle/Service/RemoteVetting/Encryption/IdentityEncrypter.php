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

use RobRichards\XMLSecLibs\XMLSecurityKey;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\InvalidArgumentException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Configuration\RemoteVettingConfiguration;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeCollectionInterface;

class IdentityEncrypter implements IdentityEncrypterInterface
{
    /**
     * @var RemoteVettingConfiguration $configuration
     */
    private $configuration;

    /**
     * @var IdentityWriterInterface
     */
    private $writer;

    public function __construct(RemoteVettingConfiguration $configuration, IdentityWriterInterface $writer)
    {
        $this->configuration = $configuration;
        $this->writer = $writer;
    }

    public function encrypt($data)
    {
        $rsaPublicKey = $this->configuration->getPublicKey();

        if (!is_string($data) || !is_string($rsaPublicKey)) {
            // Invalid argument
            throw new InvalidArgumentException('Invalid input was provided to the encrypt method');
        }

        // Use AES-256 in GCM
        $symmetricAlgorithm = 'aes-256-gcm';

        // Generate initialisation vector for the symmetric encryption algorithm
        $ivLength = openssl_cipher_iv_length($symmetricAlgorithm);
        if (false === $ivLength) {
            // Error generating key
            throw new InvalidArgumentException(
                'Unable to generate an initialization vector (iv) based on the selected symmetric encryption algorithm'
            );
        }

        $iv = openssl_random_pseudo_bytes($ivLength);
        if (false === $iv) {
            // Error generating key
            throw new InvalidArgumentException('Unable to generate a correct initialization vector (iv)');
        }

        // Generate a 256 bits AES key
        $secretKey = openssl_random_pseudo_bytes(256 / 8);
        if (false === $secretKey) {
            // Error generating key
            throw new InvalidArgumentException('Unable to generate the secret key');
        }

        // Encrypt the data
        $tag = '';
        $ciphertext = openssl_encrypt($data, $symmetricAlgorithm, $secretKey, 0, $iv, $tag);
        if (false === $ciphertext) {
            // Encryption failed
            throw new InvalidArgumentException(
                sprintf('Unable to encrypt the data, ssl error: "%s"', openssl_error_string())
            );
        }

        // Encrypt symmetric key
        $rsaPublicKeyHandle = openssl_pkey_get_public($rsaPublicKey);
        if (false === $rsaPublicKeyHandle) {
            // Reading RSA public key failed
            throw new InvalidArgumentException('Reading RSA public key failed');
        }
        $encryptedKey = '';

        $res = openssl_public_encrypt($secretKey, $encryptedKey, $rsaPublicKeyHandle, OPENSSL_PKCS1_OAEP_PADDING);
        if (false === $res) {
            // Key encryption failed
            openssl_pkey_free($rsaPublicKeyHandle);
            throw new InvalidArgumentException('Key encryption failed');
        }

        openssl_pkey_free($rsaPublicKeyHandle);
        $output = json_encode(
            [
                'algorithm' => $symmetricAlgorithm,
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'ciphertext' => base64_encode($ciphertext),
                'encrypted_key' => base64_encode($encryptedKey),
            ]
        );

        $this->writer->write($output);
    }
}
