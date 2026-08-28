<?php namespace App\Libraries;

use Config\Services;

class ApiBangunanService
{
    protected $clientAPI;
    protected $baseUrl;
    protected $accessKeyBangunanAbadi;

    public function __construct()
    {
        $this->clientAPI = Services::curlrequest();
        $this->baseUrl = 'http://acc.bangunanabadi.online';
        $this->accessKeyBangunanAbadi = '#BangunanAbadi#'; 
    }

    public function getData($endpoint, $params = [])
    {
        // Gabungkan dengan AccessKey dan default
        $query = array_merge([
            'AccessKey' => $this->accessKeyBangunanAbadi,
        ], $params);

        try {
            $response = $this->clientAPI->post("{$this->baseUrl}/{$endpoint}", [
                'query' => $query,
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return [
                'IsError' => true,
                'ErrToUser' => $e->getMessage()
            ];
        }
    }
}
