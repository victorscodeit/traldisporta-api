<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/include/config_resolve.php';

class ConfigResolveTest extends TestCase
{
    public function testApiProfileProductionDefaults()
    {
        $cfg = resolve_config(array(), array(), 'api');

        $this->assertSame('vhostsql1', $cfg['DB_EXTERNAL_HOST']);
        $this->assertSame('vhostsql2', $cfg['SQLSRV_HOST']);
        $this->assertSame('C:\\wamp64\\www\\oms\\restapi_prod\\v1\\pdf', $cfg['PDF_STORAGE_DIR']);
        $this->assertTrue(mail_is_enabled($cfg));
        $this->assertSame('localhost', $cfg['DB_HOST']);
        $this->assertSame('restapi', $cfg['DB_NAME']);
        $this->assertNotSame('', $cfg['DB_EXTERNAL_PASSWORD']);
        $this->assertSame('smtp.serviciodecorreo.es', $cfg['MAIL_HOST']);
    }

    public function testClientUiProfileProductionDefaults()
    {
        $cfg = resolve_config(array(), array(), 'client_ui');

        $this->assertSame('vhostsql2\\TEST', $cfg['SQLSRV_HOST']);
        $this->assertSame('http', $cfg['API_PROTOCOL']);
        $this->assertSame('91.187.69.73', $cfg['API_HOST']);
        $this->assertSame('8080', $cfg['API_PORT']);
        $this->assertSame('traldisporta-api/restapi_prod/v1', $cfg['API_PATH']);
        $this->assertSame(
            'http://91.187.69.73:8080/traldisporta-api/restapi_prod/v1',
            api_upstream_url($cfg)
        );
        $this->assertSame('localhost', $cfg['DB_HOST']);
        $this->assertSame('api', $cfg['DB_NAME']);
        $this->assertTrue(mail_is_enabled($cfg));
    }

    public function testEnvironmentOverridesLocalFile()
    {
        $local = array('DB_EXTERNAL_HOST' => 'from-file');
        $env = array('DB_EXTERNAL_HOST' => 'mssql');
        $cfg = resolve_config($env, $local, 'api');

        $this->assertSame('mssql', $cfg['DB_EXTERNAL_HOST']);
    }

    public function testLocalFileOverridesDefaults()
    {
        $local = array('SQLSRV_HOST' => 'vhostsql2\\TEST', 'MAIL_ENABLED' => '0');
        $cfg = resolve_config(array(), $local, 'api');

        $this->assertSame('vhostsql2\\TEST', $cfg['SQLSRV_HOST']);
        $this->assertFalse(mail_is_enabled($cfg));
    }

    public function testExampleFileAsLocalUsesDockerHosts()
    {
        $example = include dirname(__DIR__, 2) . '/include/config.local.php.example';
        $this->assertIsArray($example);

        $cfg = resolve_config(array(), $example, 'api');

        $this->assertSame('mysql', $cfg['DB_HOST']);
        $this->assertSame('mssql', $cfg['DB_EXTERNAL_HOST']);
        $this->assertSame('mssql', $cfg['SQLSRV_HOST']);
        $this->assertFalse(mail_is_enabled($cfg));
        $this->assertNotSame('vhostsql1', $cfg['DB_EXTERNAL_HOST']);
        $this->assertNotSame('91.187.69.73', isset($cfg['API_HOST']) ? $cfg['API_HOST'] : '');
    }

    public function testComposeEnvWinsOverExampleFile()
    {
        $example = include dirname(__DIR__, 2) . '/include/config.local.php.example';
        $env = array(
            'DB_HOST' => 'mysql-from-compose',
            'DB_EXTERNAL_HOST' => 'mssql-from-compose',
        );
        $cfg = resolve_config($env, $example, 'api');

        $this->assertSame('mysql-from-compose', $cfg['DB_HOST']);
        $this->assertSame('mssql-from-compose', $cfg['DB_EXTERNAL_HOST']);
    }

    public function testSqlsrvConnectionParamsFromConfig()
    {
        $cfg = resolve_config(array(), array(), 'api');
        $info = sqlsrv_connection_params($cfg);

        $this->assertSame('vhostsql2', $info['Server']);
        $this->assertSame('trans', $info['Database']);
        $this->assertSame('coffi.guy', $info['UID']);
        $this->assertArrayHasKey('PWD', $info);
    }

    public function testClientUiSqlsrvConnectionParams()
    {
        $cfg = resolve_config(array(), array(), 'client_ui');
        $info = sqlsrv_connection_params($cfg);

        $this->assertSame('vhostsql2\\TEST', $info['Server']);
    }

    public function testApiUpstreamUrlWithTestHost()
    {
        $cfg = resolve_config(
            array('API_HOST' => 'api.test.local', 'API_PORT' => '8080', 'API_PATH' => 'restapi_prod/v1'),
            array(),
            'client_ui'
        );

        $this->assertSame('http://api.test.local:8080/restapi_prod/v1', api_upstream_url($cfg));
    }

    public function testDisabledMailerDoesNotUsePhpMailer()
    {
        $cfg = resolve_config(array('MAIL_ENABLED' => '0'), array(), 'api');
        $mailer = create_smtp_mailer_from_config($cfg);

        $this->assertInstanceOf('TraldisportaNullMailer', $mailer);
        $this->assertTrue($mailer->send());
    }

    public function testPdfStoragePathUsesConfigDir()
    {
        $this->assertSame(
            'C:\\wamp64\\www\\oms\\restapi_prod\\v1\\pdf' . DIRECTORY_SEPARATOR . '8ABC.pdf',
            rtrim(config_defaults('api')['PDF_STORAGE_DIR'], "/\\") . DIRECTORY_SEPARATOR . '8ABC.pdf'
        );
    }

    public function testPublicSummaryOmitsSecrets()
    {
        $summary = public_config_summary(resolve_config(array(), array(), 'api'));
        $this->assertArrayHasKey('SQLSRV_HOST', $summary);
        $this->assertArrayNotHasKey('SQLSRV_PASSWORD', $summary);
        $this->assertArrayNotHasKey('DB_PASSWORD', $summary);
    }
}
