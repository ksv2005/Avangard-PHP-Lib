<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\ApiClient;
use Avangard\Lib\Convertor;
use Avangard\Lib\ArrayToXml;

/**
 * Trait Refunds
 * @package Avangard\Methods
 */
trait Refunds
{
    /**
     * Send refund by ticket
     *
     * @param $ticket
     * @param null $amount
     * @return array
     * @throws \DOMException
     */
    public function orderRefund($ticket, $amount = null)
    {
        $params = ['ticket' => $ticket];
        if(!empty($amount)) {
            $params['amount'] = $amount;
        }
        $request = array_merge($this->getOrderAccess(), $params);

        $xml = ArrayToXml::convert($request, 'reverse_order', false, "UTF-8");

        $url = 'https://pay.techsitea.ru/iacq/h2h/reverse_order';

        $result = $this->client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "orderRefund: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1); 
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if(!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "orderRefund: error in xml data"
            );
        }

        if (isset($resultObject['rev_id'])) {
            try {
                $maxRequestsCount = 8;
                $secondsBetweenRequests = 5;

                for ($i = 0; $i < $maxRequestsCount; $i++) {
                    sleep($secondsBetweenRequests);

                    if ($this->getRefundStatus($resultObject['rev_id']))
                        break;
                }
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        if($status == 200 && $resultObject['response_code'] == 0) {
            return ['transaction_id' => $resultObject['id']];
        }

        throw new \InvalidArgumentException(
            "orderRefund: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Send cancel of order by ticket
     *
     * @param $ticket
     * @return bool
     * @throws \DOMException
     */
    public function orderCancel($ticket)
    {
        $request = array_merge($this->getOrderAccess(), ['ticket' => $ticket]);

        $xml = ArrayToXml::convert($request, 'cancel_order', false, "UTF-8");

        $url = 'https://pay.techsitea.ru/iacq/h2h/cancel_order';

        $result = $this->client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "orderCancel: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1);
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if(!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "orderCancel: error in xml data"
            );
        }

        if($status == 200 && $resultObject['response_code'] == 0) {
            return true;
        }

        throw new \InvalidArgumentException(
            "orderCancel: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Checks the status of an earlier refund request
     *
     * @param int $rev_id
     * @return bool
     * @throws \DOMException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRefundStatus(int $rev_id): bool
    {
        $params = compact('rev_id');

        $request = array_merge($this->getOrderAccess(), $params);

        $xml = ArrayToXml::convert($request, 'reverse_status', false, "UTF-8");

        $url = 'https://pay.techsitea.ru/iacq/h2h/reverse_status';

        $result = $this->client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "getRefundStatus: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1);
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if(!isset($resultObject['status_id'])) {
            throw new \InvalidArgumentException(
                "getRefundStatus: error in xml data"
            );
        }

        if ($status == 200) {
            switch ($resultObject['status_id']) {
                case 0:
                    return false;
                case 1:
                    return true;
            }
        }

        throw new \InvalidArgumentException(
            "getRefundStatus: error in PS: " . $resultObject['status_desc'], $resultObject['status_id']
        );
    }
}
