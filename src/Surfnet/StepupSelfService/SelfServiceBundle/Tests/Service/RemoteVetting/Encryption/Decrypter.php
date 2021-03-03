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

class Decrypter
{
    public static function decrypt($ciphertext, $privateKey) {
        if (!is_array(json_decode($ciphertext, true)) || !is_string($privateKey)) {
            // Invalid argument
            return false;
        }

        $ciphertext = json_decode($ciphertext, true);
        if ( !isset($ciphertext['algorithm'], $ciphertext['iv'], $ciphertext['tag'], $ciphertext['ciphertext'], $ciphertext['encrypted_key']) ) {
            // Invalid argument
            return false;
        }

        // Use AES-256 in GCM
        if ( $ciphertext['algorithm'] != 'aes-256-gcm' ) {
            // Unsupported algorithm
            return false;
        }

        $algorithm = $ciphertext['algorithm'];
        $iv = base64_decode( $ciphertext['iv'], true );
        $tag = base64_decode( $ciphertext['tag'], true );
        $encryptedKey = base64_decode( $ciphertext['encrypted_key'], true );
        $decoded_ciphertext = base64_decode( $ciphertext['ciphertext'], true );

        $rsaPrivateKeyHandle = openssl_pkey_get_private($privateKey);
        if (false === $rsaPrivateKeyHandle) {
            // Error loading private key
            return false;
        }

        $secretKey = '';
        $res=openssl_private_decrypt($encryptedKey, $secretKey, $rsaPrivateKeyHandle,OPENSSL_PKCS1_OAEP_PADDING);
        if (false === $res) {
            openssl_pkey_free($rsaPrivateKeyHandle);
            return false;
        }

        openssl_pkey_free($rsaPrivateKeyHandle);

        $plaintext = openssl_decrypt($decoded_ciphertext, $algorithm, $secretKey, 0, $iv, $tag);
        if (false === $plaintext) {
            // Decryption failed
            return false;
        }
        return $plaintext;
    }
}
