<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Jun-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

require_once dirname(__FILE__) . '/config_resolve.php';

$local = load_config_local(dirname(__FILE__) . '/config.local.php');
$cfg = resolve_config(config_env_map(config_keys()), $local, 'api');
apply_resolved_config($cfg);
