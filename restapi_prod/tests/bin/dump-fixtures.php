<?php
/**
 * Dump JSON snapshots of Odoo-critical endpoints.
 * Uses API_BASE_URL / API_KEY from the environment (see tests/bootstrap.php).
 */
$root = dirname(__DIR__);
require $root . '/bootstrap.php';

$base = getenv('API_BASE_URL');
$key = getenv('API_KEY');
$dir = $root . '/fixtures/php74';
if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create $dir\n");
    exit(1);
}

$calls = array(
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

foreach ($calls as $file => $pair) {
    list($path, $body) = $pair;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: ' . $key,
        ),
        CURLOPT_POSTFIELDS => json_encode($body),
    ));
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno !== 0) {
        fwrite(STDERR, "curl error dumping $file\n");
        exit(1);
    }
    $json = json_decode($raw, true);
    if ($json === null) {
        fwrite(STDERR, "Not JSON: $file HTTP $code\n");
        exit(1);
    }
    $pretty = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($dir . '/' . $file, $pretty . "\n");
    echo $file . " HTTP " . $code . "\n";
}
