<?php

use PHPUnit\Framework\TestCase;

class OdooApiSnapshotTest extends TestCase
{
    private function post($path, array $body)
    {
        $url = getenv('API_BASE_URL') . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . getenv('API_KEY'),
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        $this->assertSame(0, $errno);
        $decoded = json_decode($raw, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), substr((string) $raw, 0, 400));
        return $decoded;
    }

    private function fixture($name)
    {
        $path = dirname(__DIR__) . '/fixtures/php74/' . $name;
        $this->assertFileExists($path);
        $decoded = json_decode(file_get_contents($path), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), $name);
        return $decoded;
    }

    public function testSnapshotsMatchPhp74Baseline()
    {
        $cases = array(
            'companies.json' => array('/companies', array()),
            'centers.json' => array('/centers', array()),
            'categories.json' => array('/categories', array()),
            'sections.json' => array('/sections', array()),
            'sectors.json' => array('/sectors', array()),
            'agenda_cus.json' => array('/agenda', array('type' => 'CUS')),
            'monthly_movements_2024_06.json' => array('/getMonthlyMovements', array('year' => 2024, 'month' => 6)),
            'sale_invoices_2024_06.json' => array(
                '/getSaleInvoices',
                array('dateInit' => '2024-06-01', 'dateEnd' => '2024-06-30'),
            ),
            'detail_invoice_100.json' => array(
                '/getDetailInvoice',
                array('ImpFraNum' => 100, 'ImpFraCtr' => 8, 'ImpFraSer' => 1),
            ),
            'all_movements_2024_06.json' => array(
                '/getAllMovements',
                array('dateInit' => '2024-06-01', 'dateEnd' => '2024-06-30'),
            ),
            'expeditions_8_2024_06_15.json' => array(
                '/getExpeditionsData',
                array(
                    'year' => 2024,
                    'month' => 6,
                    'centerCode' => 8,
                    'startDate' => '2024-06-15',
                    'endDate' => '2024-06-21',
                ),
            ),
        );

        foreach ($cases as $file => $pair) {
            list($path, $body) = $pair;
            $this->assertSame(
                $this->fixture($file),
                $this->post($path, $body),
                $file
            );
        }
    }
}
