<?php
$requestRaw = file_get_contents('php://input');

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
    function gateway($word, array $params)
    {
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

        return $gatewayResponse['results'];
    }

    $result['results'] = gateway($request['word'], $config['oxforddictionaries']);

    echo json_encode($result);
} catch (\Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
