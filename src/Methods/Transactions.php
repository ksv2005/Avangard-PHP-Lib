<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Methods;

use Avangard\ApiClient;
use Avangard\Lib\Convertor;
use Avangard\Lib\ArrayToXml;

/**
 * Trait Transactions
 * @package Avangard\Methods
 */
trait Transactions
{
    /**
     * Get all opers by order number
     *
     * @param $order_number
     * @return array|mixed
     * @throws \DOMException
     */
    public function getOpersByOrderNumber($order_number)
    {
        $request = array_merge($this->getOrderAccess(), ['order_number' => $order_number]);

        $xml = ArrayToXml::convert($request, 'get_opers_list', false, "UTF-8");

        $url = 'https://pay.techsitea.ru/iacq/h2h/get_opers_list';

        $result = $this->client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "getOpersByOrderNumber: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1); 
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL);

        if(!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "getOpersByOrderNumber: error in xml data"
            );
        }

        if($status == 200 && $resultObject['response_code'] == 0) {
            return (!empty($resultObject['oper_info']) ? (!empty($resultObject['oper_info'][0]) ? $resultObject['oper_info'] : [$resultObject['oper_info']]) : []);
        }

        throw new \InvalidArgumentException(
            "getOpersByOrderNumber: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }

    /**
     * Get all opers behind one day
     *
     * @param $date
     * @return array|mixed
     * @throws \DOMException
     */
    public function getOpersByDate($date)
    {
        $date = date("d.m.Y", strtotime($date));

        $request = array_merge($this->getOrderAccess(), ['date' => $date]);

        $xml = ArrayToXml::convert($request, 'get_opers_by_date', false, "UTF-8");

        $url = 'https://pay.techsitea.ru/iacq/h2h/get_opers_by_date';

        $result = $this->client->request('POST', $url, ['body' => 'xml=' . $xml, 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8']]);

        $status = $result->getStatusCode();

        if($status != 200) {
            throw new \InvalidArgumentException(
                "getOpersByDate: incorrect http code: " . $status, $status
            );
        }

        $response = $result->getBody()->getContents();

        error_reporting(1); 
        $resultObject = Convertor::covertToArray($response);
        error_reporting(E_ALL); 

        if(!isset($resultObject['response_code'])) {
            throw new \InvalidArgumentException(
                "getOpersByDate: error in xml data"
            );
        }

        if($status == 200 && $resultObject['response_code'] == 0) {
            return (!empty($resultObject['oper_info']) ? (!empty($resultObject['oper_info'][0]) ? $resultObject['oper_info'] : [$resultObject['oper_info']]) : []);
        }

        throw new \InvalidArgumentException(
            "getOpersByDate: error in PS: " . $resultObject['response_message'], $resultObject['response_code']
        );
    }
}
