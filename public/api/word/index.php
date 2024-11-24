<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

require __DIR__ . '/../../../vendor/autoload.php';

$requestRaw = file_get_contents('php://input');

try {
    $config = require __DIR__ . '/../../../config/config.php';

    if (empty($config)) {
        throw new Exception('Empty config');
    }

    if (empty($requestRaw)) {
        throw new Exception('Empty request');
    }

    $request = json_decode($requestRaw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Incorrect request');
    }

    if (empty($request['word'])) {
        throw new Exception('Empty word');
    }

    if (!preg_match('/^[a-z]+$/', $request['word'])) {
        throw new Exception('Incorrect word');
    }

    /**
     * @param string $word
     * @param array $params
     *
     * @return mixed
     */
    function oxforddictionaries_entries(string $word, array $params): array
    {
        try {
            if (empty($params['app_id'])) {
                throw new Exception('Empty app_id');
            }
            if (empty($params['app_key'])) {
                throw new Exception('Empty app_key');
            }

            $url = sprintf(
                'https://od-api-sandbox.oxforddictionaries.com/api/v2/entries/%s/%s',
                $params['source_lang'],
                $word
            );
            $headers = [
                'Accept: application/json',
                'app_id: ' . $params['app_id'],
                'app_key: ' . $params['app_key'],
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $gatewayResponseRaw = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            $info = curl_getinfo($ch);

            if ($info['http_code'] === 404) {
                throw new Exception('Word not found');
            }
            if ($info['http_code'] !== 200) {
                throw new Exception('Http code is ' . $info['http_code']);
            }
            curl_close($ch);

            $gatewayResponse = json_decode($gatewayResponseRaw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Incorrect gateway response');
            }

            if (empty($gatewayResponse['results'])) {
                throw new Exception('Empty gateway results');
            }

            if (!is_array($gatewayResponse['results'])) {
                throw new Exception('Gateway results is not array');
            }

            $logger = new Logger('oxforddictionaries_entries');
            $logger->pushHandler(
                new RotatingFileHandler(__DIR__ . '/../../../var/logs/oxforddictionaries_entries.log')
            );
            $logger->debug('', ['word' => $word, '$params' => $params, 'response' => $gatewayResponse]);

            return $gatewayResponse['results'];
        } catch (Exception $e) {
            $logger = new Logger('oxforddictionaries_entries_error');
            $logger->pushHandler(
                new RotatingFileHandler(__DIR__ . '/../../../var/logs/oxforddictionaries_entries_error.log')
            );
            $logger->error('',
                [
                    'word' => $word,
                    'params' => $params,
                    'exception' => $e,
                    'response' => $gatewayResponse ?? null,
                    'info' => $info ?? null,
                ]
            );

            throw $e;
        }
    }

    /**
     * @param string $word
     * @param array $params
     *
     * @return array
     */
    function oxforddictionaries_entries_cached(string $word, array $params): array
    {
        $cache = new FilesystemAdapter(
            '',
            31536000, // 1 year
            __DIR__ . '/../../../var/cache'
        );

        $cacheKey = sprintf('oxforddictionaries.entries.%s', md5($word));

        return $cache->get($cacheKey, function () use ($word, $params) {
            return oxforddictionaries_entries($word, $params);
        });
    }

    $response = ['results' => oxforddictionaries_entries_cached(
        strtolower($request['word']),
        $config['oxforddictionaries']
    )];
} catch (Exception $e) {
    $logger = new Logger('api_word_error');
    $logger->pushHandler(new RotatingFileHandler(__DIR__ . '/../../../var/logs/api_word_error.log'));
    $logger->error('', ['request' => $requestRaw, 'exception' => $e]);

    $response = ['error' => $e->getMessage()];
}

try {
    echo json_encode($response, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    echo '["error": "JsonException"]';
}
