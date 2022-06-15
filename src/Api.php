<?php declare(strict_types = 1);
namespace MondidoPayments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use MondidoPayments\Exception\ApiError;

class Api
{
    private $client;
    private $auth;

    public function __construct($merchant_id, $password)
    {
        $this->auth = [$merchant_id, $password];
        $this->client = new Client(['base_url' => 'https://api.mondido.com']);
    }

    public function testCredentials()
    {
        try {
            $this->getList('/v1/transactions', ['query' => ['limit' => 1, 'offset' => 1]]);
            return true;
        } catch (\Throwable $error) {
            return false;
        }
    }

    public function getTransactionAdminLink($transaction_id)
    {
        return rtrim('https://admin.mondido.com', '/') . "/en/transactions/$transaction_id";
    }

    public function getTransactionFromReference($reference)
    {
        $transactions = $this->getList('/v1/transactions', [
            'query' => ['filter' => ['payment_ref' => $reference]]
        ]);

        if (empty($transactions)) {
            return null;
        }

        return $transactions[0];
    }

    public function getTransaction($id)
    {
        return $this->send('get', "/v1/transactions/$id", [
            'query' => ['extend' => 'customer']
        ]);
    }

    public function createTransaction($data)
    {
        return $this->send('post', "/v1/transactions", ['json' => $data]);
    }

    public function updateTransaction($id, $data)
    {
        return $this->send('put', "/v2/transactions/$id", ['json' => $data]);
    }

    public function captureTransaction($transaction_id, $amount)
    {
        return $this->send('put', "/v1/transactions/$transaction_id/capture", ['json' => [
            'amount' => number_format($amount, 2, '.', ''),
        ]]);
    }

    public function createRefund($transaction_id, $amount)
    {
        return $this->send('post', "/v1/refunds", ['json' => [
            'transaction_id' => $transaction_id,
            'amount' => number_format($amount, 2, '.', ''),
            'reason' => 'prestashop',
        ]]);
    }

    private function getList($path, $requestData) {
        $responseData = $this->send('get', $path, $requestData);

        if (!is_array($responseData)) {
            throw new ApiError('Invalid response');
        }

        return $responseData;
    }

    private function send($method, $path, $data)
    {
        try {
            return $this->getJson($this->client->$method($path, $data + ['auth' => $this->auth]));
        } catch (BadResponseException $error) {
            $response = $error->getResponse();
            $message = $error->getResponse()->getBody();
            $code = $error->getCode();
            $data = $response->json();
            if ($data) {
                $message = $data['name'];
                $code = $data['code'];
            }

            throw new ApiError($message, $code, $error);
        } catch (\Throwable $error) {
            throw new ApiError($error->getMessage(), $error->getCode(), $error);
        }
    }

    private function getJson($response)
    {
        $data = json_decode((string) $response->getBody());

        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg());
        }

        return $data;
    }
}
