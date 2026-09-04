<?php

/**
 * This function encrypts to your value from your CIPHER algo and KEY.
 * @param mixed $data : Your data.
 * @return string|false : Your hash or failure.
 */
function encrypt($data) {
  return base64_encode(openssl_encrypt($data, ENCRYPTION_CIPHER, ENCRYPTION_KEY, 0, ENCRYPTION_KEY));
}

/**
 * This function trying to get your value from your hash.
 * @param mixed $hash : Your hash.
 * @return string|false : Your data or failure.
 */
function decrypt($hash) {
  return openssl_decrypt(base64_decode($hash), ENCRYPTION_CIPHER, ENCRYPTION_KEY, 0, ENCRYPTION_KEY);
}