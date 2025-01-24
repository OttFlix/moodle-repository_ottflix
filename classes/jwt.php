<?php
/**
 * User: Eduardo Kraus
 * Date: 18/01/2024
 * Time: 21:59
 */

namespace repository_ottflix;


use \Exception;

class jwt {

    private static $supported_algs = [
        'HS256' => ['hash_hmac', 'SHA256'],
        'HS512' => ['hash_hmac', 'SHA512'],
        'HS384' => ['hash_hmac', 'SHA384'],
    ];

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload PHP object or array
     * @param string $alg The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512'
     * @param array $head An array with header elements to attach
     *
     * @return string A signed JWT
     */
    public static function encode($tokenexterno, $payload, $expires = 300, $alg = 'HS256', $head = null) {
        $key = $tokenexterno;

        $header = ['typ' => 'JWT', 'alg' => $alg];
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }

        if ($expires) {
            $payload['iat'] = time();
            $payload['nbf'] = time();
            $payload['exp'] = time() + $expires;
        }

        $segments = [];
        $segments[] = self::urlsafeB64Encode(json_encode($header));
        $segments[] = self::urlsafeB64Encode(json_encode($payload));
        $signing_input = implode('.', $segments);

        try {
            $signature = self::sign($signing_input, $key, $alg);
        } catch (Exception $e) {
            return "";
        }
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg The message to sign
     * @param string|resource $key The secret key
     * @param string $alg The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512'
     *
     * @return string An encrypted message
     *
     * @throws Exception Unsupported algorithm was specified
     */
    private static function sign($msg, $key, $alg = 'HS256') {
        if (empty(self::$supported_algs[$alg])) {
            throw new Exception('Algoritmo não suportado');
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
        }

        return "";
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    private static function urlsafeB64Encode($input) {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
}