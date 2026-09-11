<?php

use PHPUnit\Framework\TestCase;

class OdooApiContractTest extends TestCase
{
    private function post($path, array $body, $apiKey = null)
    {
        $url = getenv('API_BASE_URL') . $path;
        $headers = array(
            'Content-Type: application/json',
        );
        if ($apiKey !== null) {
            $headers[] = 'Authorization: ' . $apiKey;
        }
        $payload = json_encode($body);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $this->assertSame(0, $errno, $error);
        $bodyText = substr($raw, $headerSize);
        $decoded = json_decode($bodyText, true);
        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'Response is not JSON. Body: ' . substr($bodyText, 0, 500)
        );

        return array($status, $decoded, $bodyText);
    }

    private function key()
    {
        return getenv('API_KEY');
    }

    public function testMissingApiKeyReturnsBadRequestJson()
    {
        list($status, $json) = $this->post('/companies', array());
        $this->assertSame(400, $status);
        $this->assertTrue($json['error']);
    }

    public function testInvalidApiKeyKeepsLegacyCreatedStatus()
    {
        list($status, $json) = $this->post('/companies', array(), 'invalid-key');
        $this->assertSame(201, $status);
        $this->assertTrue($json['error']);
    }

    public function testCompaniesReturnsOnlyHoldingZeroEmpOneAndTwo()
    {
        list($status, $json) = $this->post('/companies', array(), $this->key());
        $this->assertSame(200, $status);
        $this->assertFalse($json['error']);
        $ids = array();
        foreach ($json['data'] as $row) {
            $ids[] = (string) $row['id'];
            $this->assertArrayHasKey('nameFiscal', $row);
            $this->assertArrayHasKey('cif', $row);
            $this->assertArrayHasKey('countryId', $row);
            $this->assertArrayHasKey('zip', $row);
        }
        sort($ids);
        $this->assertSame(array('1', '2'), $ids);
    }

    public function testCentersJoinPostal()
    {
        list($status, $json) = $this->post('/centers', array(), $this->key());
        $this->assertSame(200, $status);
        $this->assertFalse($json['error']);
        $this->assertGreaterThanOrEqual(3, count($json['data']));
        $ids = array();
        foreach ($json['data'] as $row) {
            $ids[] = (int) $row['id'];
            $this->assertSame('BARCELONA', trim($row['city_name']));
        }
        $this->assertContains(8, $ids);
        $this->assertContains(25, $ids);
        $this->assertContains(80, $ids);
    }

    public function testCategoriesSectionsSectors()
    {
        foreach (array('/categories', '/sections', '/sectors') as $path) {
            list($status, $json) = $this->post($path, array(), $this->key());
            $this->assertSame(200, $status, $path);
            $this->assertFalse($json['error']);
            $this->assertNotEmpty($json['data'], $path);
            $this->assertArrayHasKey('id', $json['data'][0]);
            $this->assertArrayHasKey('name', $json['data'][0]);
        }
    }

    public function testAgendaCustomersSkipsEmptyName()
    {
        list($status, $json) = $this->post('/agenda', array('type' => 'CUS'), $this->key());
        $this->assertSame(200, $status);
        $this->assertFalse($json['error']);
        $ids = array();
        foreach ($json['data'] as $row) {
            $ids[] = (int) $row['id'];
            $this->assertNotSame('', trim($row['name']));
            $this->assertArrayHasKey('iban_customer', $row);
            $this->assertNotNull(
                $row['iban_customer'],
                'NULL IBAN becomes JSON null and Odoo does len(None)'
            );
            $this->assertIsString($row['iban_customer']);
            $this->assertArrayHasKey('logistic_country_iso', $row);
            $this->assertNotNull($row['logistic_country_iso']);
            $this->assertIsString($row['logistic_country_iso']);
            foreach (array(
                'logistic_picking1',
                'logistic_picking2',
                'logistic_delivery1',
                'logistic_delivery2',
            ) as $field) {
                $this->assertArrayHasKey($field, $row);
                $this->assertNotNull($row[$field], $field);
                $this->assertIsString($row[$field], $field);
            }
        }
        $this->assertContains(1001, $ids);
        $this->assertNotContains(1002, $ids);
    }

    public function testMonthlyMovementsKpiShape()
    {
        list($status, $json) = $this->post(
            '/getMonthlyMovements',
            array('year' => 2024, 'month' => 6),
            $this->key()
        );
        $this->assertSame(200, $status);
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        $row = $json[0];
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('amount', $row);
        $this->assertArrayHasKey('account', $row);
        $this->assertArrayHasKey('company', $row);
        $this->assertArrayHasKey('last_two_digits', $row);
        $this->assertStringStartsWith('6', trim($row['account']));
        $this->assertSame(10, strlen(trim($row['account'])));
        $this->assertSame(
            '10',
            trim((string) $row['last_two_digits']),
            '10-digit CtaCod like production (6280000010) so RIGHT(CtaCod,2) is Grupatge'
        );
    }

    public function testSaleInvoicesJune2024()
    {
        list($status, $json) = $this->post(
            '/getSaleInvoices',
            array('dateInit' => '2024-06-01', 'dateEnd' => '2024-06-30'),
            $this->key()
        );
        $this->assertSame(200, $status);
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        $nums = array();
        foreach ($json as $row) {
            $nums[] = (int) $row['RegNum'];
            $this->assertSame('V', trim($row['RegTip']));
        }
        $this->assertContains(100, $nums);
        $this->assertNotContains(200, $nums);
    }

    public function testDetailInvoice()
    {
        list($status, $json) = $this->post(
            '/getDetailInvoice',
            array('ImpFraNum' => 100, 'ImpFraCtr' => 8, 'ImpFraSer' => 108),
            $this->key()
        );
        $this->assertSame(200, $status);
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        $this->assertArrayHasKey('ImpNeto', $json[0]);
        $this->assertArrayHasKey('TIva', $json[0]);
        $this->assertSame(
            '4',
            trim((string) $json[0]['TIva']),
            'TIva 4 maps to IVA 21% in oms.mtrans.invoice.tax.map'
        );
    }

    public function testAllMovementsExcludesInvoiceAsientos()
    {
        list($status, $json) = $this->post(
            '/getAllMovements',
            array('dateInit' => '2024-06-01', 'dateEnd' => '2024-06-30'),
            $this->key()
        );
        $this->assertSame(200, $status);
        $codes = array();
        foreach ($json as $row) {
            $codes[] = (int) $row['MvtCod'];
        }
        $this->assertContains(9100, $codes);
        $this->assertNotContains(500, $codes);
    }

    public function testExpeditionsDataForCenter8()
    {
        list($status, $json) = $this->post(
            '/getExpeditionsData',
            array(
                'year' => 2024,
                'month' => 6,
                'centerCode' => 8,
                'startDate' => '2024-06-15',
                'endDate' => '2024-06-21',
            ),
            $this->key()
        );
        $this->assertSame(200, $status);
        $this->assertFalse($json['error']);
        $this->assertNotEmpty($json['data']);
        $this->assertArrayHasKey(40001, $json['data']);
        $this->assertArrayNotHasKey(40002, $json['data']);
        foreach ($json['data'] as $key => $row) {
            $this->assertIsArray($row, 'data[' . $key . '] must be an expedition dict, not an empty list');
            $this->assertArrayHasKey('ExpCod', $row, 'data[' . $key . ']');
        }
    }
}
