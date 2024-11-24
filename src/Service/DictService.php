<?php

namespace App\Service;

use App\Exception\AppException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class DictService
 * @package App\Service
 */
class DictService
{
    /**
     * @var string
     */
    private string $logDir;

    /**
     * @var string
     */
    private string $cacheDir;

    private string $sourceLang;
    private string $appId;
    private string $appKey;

    /**
     * DictService constructor.
     *
     * @param KernelInterface $kernel
     * @param ParameterBagInterface $params
     */
    public function __construct(KernelInterface $kernel, private ParameterBagInterface $params)
    {
        $this->logDir = $kernel->getLogDir();
        $this->cacheDir = $kernel->getCacheDir();

        $this->sourceLang = (string)$this->params->get('dict.sourceLang');
        $this->appId = (string)$this->params->get('dict.appId');
        $this->appKey = (string)$this->params->get('dict.appKey');
    }

    /**
     * @param string $word
     *
     * @return array
     */
    public function getEntriesCached(string $word): array
    {
        $word = mb_strtolower($word);

        // @todo use cache from container
        $cache = new FilesystemAdapter(
            '',
            31536000, // 1 year
            $this->cacheDir . '/appDict'
        );

        $cacheKey = sprintf('oxforddictionaries.entries.%s', md5($word));

        return $cache->get($cacheKey, function () use ($word) {
            return $this->getEntries($word);
        });
    }

    /**
     * @param string $word
     *
     * @return array
     * @throws AppException
     */
    public function getEntries(string $word): array
    {
        try {
            // @todo move to client calss
            $url = sprintf(
                'https://od-api-sandbox.oxforddictionaries.com/api/v2/entries/%s/%s',
                $this->sourceLang,
                $word
            );
            $headers = [
                'Accept: application/json',
                'app_id: ' . $this->appId,
                'app_key: ' . $this->appKey,
            ];

            // @todo use guzzle
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
                throw new AppException(curl_error($ch));
            }
            $info = curl_getinfo($ch);

            if ($info['http_code'] === 404) {
                throw new AppException('Word not found');
            }
            if ($info['http_code'] !== 200) {
                throw new AppException('Http code is ' . $info['http_code']);
            }
            curl_close($ch);

            $gatewayResponse = json_decode($gatewayResponseRaw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new AppException('Incorrect gateway response');
            }

            if (empty($gatewayResponse['results'])) {
                throw new AppException('Empty gateway results');
            }

            if (!is_array($gatewayResponse['results'])) {
                throw new AppException('Gateway results is not array');
            }

            $this->getLogger()->debug('', ['word' => $word, 'response' => $gatewayResponse]);

            return ['results' => $gatewayResponse['results']];
        } catch (AppException $e) {
            $this->getLogger()->error($e->getMessage(),
                [
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'word' => $word,
                    'response' => $gatewayResponse ?? null,
                    'info' => $info ?? null,
                ]
            );

            throw $e;
        }
    }

    /**
     * @return Logger
     */
    private function getLogger(): Logger
    {
        return (new Logger('oxforddictionaries_entries'))
            ->pushHandler(new RotatingFileHandler($this->logDir . '/dict.log', 30));
    }
}
