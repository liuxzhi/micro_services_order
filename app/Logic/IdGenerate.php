<?php
declare(strict_types=1);

namespace App\Logic;

use App\Constants\BusinessErrorCode;
use App\Exception\BusinessException;
use Hyperf\Di\Annotation\Inject;
use App\Helper\ApiCurl;
use App\Helper\Log;

trait IdGenerate
{



    /**
     * @Inject
     *
     * @var ApiCurl
     */
    private $curl;

    /**
     * 生成ID
     *
     * @param $itemNum
     * @return int
     */
    protected function generate()
    {
        $hosts =["127.0.0.1:8088", "127.0.0.1:8089"];
        $apiHost = $hosts[array_rand ($hosts, 1)];

        $url = $apiHost . '/generate';
        echo $url."\n";
        $result = $this->curl->request('GET', $url);
        print_r($result);
        if (!isset($result['code']) || $result['code'] != 0 || !isset($result['data']) || !is_numeric($result['data']['id'])) {
            Log::notice('id_generate_error_result', $result);
            throw new BusinessException(BusinessErrorCode::ID_GENERATE_ERROR);
        }

        return (int)$result['data']['id'];
    }
}