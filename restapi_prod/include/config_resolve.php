<?php
/**
 * Resolve runtime config: environment, then local file, then production defaults.
 */

if (!function_exists('config_pick')) {
    function config_pick($key, $env, $local, $default)
    {
        if (array_key_exists($key, $env) && $env[$key] !== false && $env[$key] !== null) {
            return $env[$key];
        }
        if (array_key_exists($key, $local) && $local[$key] !== null) {
            return $local[$key];
        }
        return $default;
    }
}

if (!function_exists('config_env_map')) {
    function config_env_map($keys)
    {
        $env = array();
        foreach ($keys as $key) {
            $val = getenv($key);
            if ($val !== false) {
                $env[$key] = $val;
            }
        }
        return $env;
    }
}

if (!function_exists('load_config_local')) {
    function load_config_local($path)
    {
        if (!is_file($path)) {
            return array();
        }
        $data = include $path;
        return is_array($data) ? $data : array();
    }
}

if (!function_exists('config_defaults')) {
    function config_defaults($profile)
    {
        $shared = array(
            'SQLSRV_DATABASE' => 'trans',
            'SQLSRV_USERNAME' => 'coffi.guy',
            'SQLSRV_PASSWORD' => 'Tyorpan4',
            'MAIL_ENABLED' => '1',
            'MAIL_HOST' => 'smtp.serviciodecorreo.es',
            'MAIL_USERNAME' => 'bot@porta.ad',
            'API_KEY_ADMIN' => 'd8746d4f4cf1b9a1634b19990d7ab6d1',
        );

        if ($profile === 'client_ui') {
            return array_merge($shared, array(
                'DB_USERNAME' => 'api_traldisporta',
                'DB_PASSWORD' => '684e4gfH?',
                'DB_HOST' => 'localhost',
                'DB_NAME' => 'api',
                'SQLSRV_HOST' => 'vhostsql2\\TEST',
                'API_PROTOCOL' => 'http',
                'API_HOST' => '91.187.69.73',
                'API_PORT' => '8080',
                'API_PATH' => 'traldisporta-api/restapi_prod/v1',
            ));
        }

        return array_merge($shared, array(
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'restapi',
            'DB_EXTERNAL_USERNAME' => 'coffi.guy',
            'DB_EXTERNAL_PASSWORD' => 'Tyorpan4',
            'DB_EXTERNAL_HOST' => 'vhostsql1',
            'DB_EXTERNAL_NAME' => 'trans',
            'SQLSRV_HOST' => 'vhostsql2',
            'PDF_STORAGE_DIR' => 'C:\\wamp64\\www\\oms\\restapi_prod\\v1\\pdf',
        ));
    }
}

if (!function_exists('config_keys')) {
    function config_keys($profile = 'api')
    {
        return array_keys(config_defaults($profile));
    }
}

if (!function_exists('resolve_config')) {
    function resolve_config($env, $local, $profile)
    {
        if (!is_array($env)) {
            $env = array();
        }
        if (!is_array($local)) {
            $local = array();
        }

        $defaults = config_defaults($profile);
        $cfg = array();
        foreach ($defaults as $key => $default) {
            $cfg[$key] = config_pick($key, $env, $local, $default);
        }
        return $cfg;
    }
}

if (!function_exists('mail_is_enabled')) {
    function mail_is_enabled($cfg)
    {
        $value = isset($cfg['MAIL_ENABLED']) ? $cfg['MAIL_ENABLED'] : '1';
        $normalized = strtolower(trim((string) $value));
        return !in_array($normalized, array('0', 'false', 'off', 'no', ''), true);
    }
}

if (!function_exists('sqlsrv_connection_params')) {
    function sqlsrv_connection_params($cfg)
    {
        return array(
            'Server' => $cfg['SQLSRV_HOST'],
            'Database' => $cfg['SQLSRV_DATABASE'],
            'UID' => $cfg['SQLSRV_USERNAME'],
            'PWD' => $cfg['SQLSRV_PASSWORD'],
        );
    }
}

if (!function_exists('api_upstream_url')) {
    function api_upstream_url($cfg)
    {
        $url = $cfg['API_PROTOCOL'] . '://' . $cfg['API_HOST'];
        if (isset($cfg['API_PORT']) && $cfg['API_PORT'] !== '') {
            $url .= ':' . $cfg['API_PORT'];
        }
        $url .= '/' . $cfg['API_PATH'];
        return $url;
    }
}

if (!function_exists('apply_resolved_config')) {
    function apply_resolved_config($cfg)
    {
        foreach ($cfg as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}

if (!class_exists('TraldisportaNullMailer')) {
    class TraldisportaNullMailer
    {
        public $ErrorInfo = '';
        public $Subject = '';

        public function __call($name, $args)
        {
            return $this;
        }

        public function __set($name, $value)
        {
        }

        public function send()
        {
            return true;
        }
    }
}

if (!function_exists('mail_should_send')) {
    function mail_should_send()
    {
        $enabled = defined('MAIL_ENABLED') ? MAIL_ENABLED : '1';
        return mail_is_enabled(array('MAIL_ENABLED' => $enabled));
    }
}

if (!function_exists('create_smtp_mailer_from_config')) {
    function create_smtp_mailer_from_config($cfg)
    {
        if (!mail_is_enabled($cfg)) {
            return new TraldisportaNullMailer();
        }
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->IsSMTP();
        $mail->Host = isset($cfg['MAIL_HOST']) ? $cfg['MAIL_HOST'] : 'smtp.serviciodecorreo.es';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->SMTPDebug = 2;
        $mail->SMTPAuth = true;
        $mail->Username = isset($cfg['MAIL_USERNAME']) ? $cfg['MAIL_USERNAME'] : 'bot@porta.ad';
        $mail->Password = 'Vityaro2';
        $mail->SetFrom($mail->Username);
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        return $mail;
    }
}

if (!function_exists('create_smtp_mailer')) {
    function create_smtp_mailer()
    {
        return create_smtp_mailer_from_config(array(
            'MAIL_ENABLED' => defined('MAIL_ENABLED') ? MAIL_ENABLED : '1',
            'MAIL_HOST' => defined('MAIL_HOST') ? MAIL_HOST : 'smtp.serviciodecorreo.es',
            'MAIL_USERNAME' => defined('MAIL_USERNAME') ? MAIL_USERNAME : 'bot@porta.ad',
        ));
    }
}

if (!function_exists('pdf_storage_path')) {
    function pdf_storage_path($centerCode, $expeditionCode)
    {
        $dir = defined('PDF_STORAGE_DIR') ? PDF_STORAGE_DIR : 'C:\\wamp64\\www\\oms\\restapi_prod\\v1\\pdf';
        $dir = rtrim($dir, "/\\");
        return $dir . DIRECTORY_SEPARATOR . $centerCode . $expeditionCode . '.pdf';
    }
}

if (!function_exists('pdf_storage_dir')) {
    function pdf_storage_dir()
    {
        return defined('PDF_STORAGE_DIR') ? PDF_STORAGE_DIR : 'C:\\wamp64\\www\\oms\\restapi_prod\\v1\\pdf';
    }
}

if (!function_exists('public_config_summary')) {
    function public_config_summary($cfg)
    {
        $summary = array(
            'DB_HOST' => $cfg['DB_HOST'],
            'DB_NAME' => $cfg['DB_NAME'],
            'SQLSRV_HOST' => $cfg['SQLSRV_HOST'],
            'MAIL_ENABLED' => $cfg['MAIL_ENABLED'],
        );
        if (isset($cfg['DB_EXTERNAL_HOST'])) {
            $summary['DB_EXTERNAL_HOST'] = $cfg['DB_EXTERNAL_HOST'];
        }
        if (isset($cfg['PDF_STORAGE_DIR'])) {
            $summary['PDF_STORAGE_DIR'] = $cfg['PDF_STORAGE_DIR'];
        }
        if (isset($cfg['API_HOST'])) {
            $summary['API_HOST'] = $cfg['API_HOST'];
            $summary['API_PORT'] = $cfg['API_PORT'];
            $summary['API_PATH'] = $cfg['API_PATH'];
            $summary['upstream'] = api_upstream_url($cfg);
        }
        return $summary;
    }
}
