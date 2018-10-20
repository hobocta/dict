<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Cache\Simple\FilesystemCache;

require __DIR__ . '/../../vendor/autoload.php';

$requestRaw = file_get_contents('php://input');

function echo_json(array $result)
{
    echo json_encode($result);
}

try {
    $result = array();

    $config = require __DIR__ . '/../../config/config.php';
    if (empty($config)) {
        throw new \Exception('Empty config');
    }

    if (empty($requestRaw)) {
        throw new \Exception('Empty request');
    }

    $request = json_decode($requestRaw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Incorrect request');
    }

    if (empty($request['word'])) {
        throw new \Exception('Empty word');
    }

    if (!preg_match('/^[A-Za-z]+$/', $request['word'])) {
        throw new \Exception('Incorrect word');
    }

    /**
     * @param $word
     * @param array $params
     * @return mixed
     */
    function oxforddictionaries_entries($word, array $params)
    {
        try {

            if (empty($params['app_id'])) {
                throw new \Exception('Empty app_id');
            }
            if (empty($params['app_key'])) {
                throw new \Exception('Empty app_key');
            }

            $url = sprintf(
                'https://od-api.oxforddictionaries.com/api/v1/entries/en/%s/regions=us',
                $word
            );
            $headers = array(
                'app_id: ' . $params['app_id'],
                'app_key: ' . $params['app_key']
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $gatewayResponseRaw = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }
            $info = curl_getinfo($ch);
            if ($info['http_code'] === 404) {
                throw new \Exception('Word not found');
            }
            if ($info['http_code'] !== 200) {
                throw new \Exception('Http code is not 200');
            }
            curl_close($ch);

            $gatewayResponse = json_decode($gatewayResponseRaw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Incorrect gateway response');
            }

            if (empty($gatewayResponse['results'])) {
                throw new \Exception('Empty gateway results');
            }

            $logger = new Logger('oxforddictionaries_entries');
            $logger->pushHandler(
                new RotatingFileHandler(__DIR__ . '/../../logs/oxforddictionaries_entries.log')
            );
            $logger->debug('', ['word' => $word, '$params' => $params, 'response' => $gatewayResponse]);
        } catch (\Exception $e) {
            $logger = new Logger('oxforddictionaries_entries_error');
            $logger->pushHandler(
                new RotatingFileHandler(__DIR__ . '/../../logs/oxforddictionaries_entries_error.log')
            );
            $logger->error('', ['word' => $word, '$params' => $params, 'exception' => $e]);

            throw $e;
        }

        return $gatewayResponse['results'];
    }

    function oxforddictionaries_entries_cached($word, array $params)
    {
        $cache = new FilesystemCache();

        $cacheKey = sprintf('oxforddictionaries.entries.%s', $word);

        if (!$cache->has($cacheKey)) {
            $cache->set($cacheKey, oxforddictionaries_entries($word, $params));
        }

        return $cache->get($cacheKey);
    }

    $request['word'] = strtolower($request['word']);
    $result['results'] = oxforddictionaries_entries_cached($request['word'], $config['oxforddictionaries']);

    echo_json($result);
} catch (\Exception $e) {
    global $requestRaw;

    $logger = new Logger('api_word_error');
    $logger->pushHandler(new RotatingFileHandler(__DIR__ . '/../../logs/api_word_error.log'));
    $logger->error('', ['request' => $requestRaw, 'exception' => $e]);

    echo_json(array('error' => $e->getMessage()));
}
