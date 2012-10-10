<?php
/**
 * The global configuration.
 */
require_once 'autoloader.php';

define('E_FATAL',  E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);

function pimfCatchNoUserErrorHandlerFunctionErrors()
{
  $error = error_get_last();
  if ($error && ($error['type'] & E_FATAL)) {
    pimfCustomErrorHandler($error['type'], $error['message'], $error['file'], $error['line'], 'context');
  }
}

function pimfCustomErrorHandler($message, $code, $severity, $filename, $lineno, $previous = null)
{
  // Determine if this error is one of the enabled ones in php config (php.ini, .htaccess, etc)
  $errorIsEnabled = (bool)($lineno & ini_get('error_reporting'));

  if (in_array($lineno, array(E_USER_ERROR, E_RECOVERABLE_ERROR)) && $errorIsEnabled) {
    throw new ErrorException($message, $code, $severity, $filename, $lineno, $previous);
  } else if ($errorIsEnabled) {
    error_log($message, 0);
    return false;
  }
}

register_shutdown_function('pimfCatchNoUserErrorHandlerFunctionErrors');
set_error_handler('pimfCustomErrorHandler');

ini_set('date.timezone', 'Europe/Berlin');
setlocale(LC_ALL, 'de_DE.utf8');

// load the app configuration.
$iniParser = new  Pimf_Util_IniParser(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.ini');

$config = Pimf_Util_Cache::cache('config.cache');

if (!$config) {
  $config = $iniParser->parse();
  Pimf_Util_Cache::cache('config.cache', $config);
}

if ($config->environment == 'testing') {

  error_reporting(E_ALL | E_STRICT);
  ini_set('display_errors', 'on');

  $dbDsn = $config->testing->database->dsn;

} else {

  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', 'off');

  $dbDsn = $config->production->database->dsn;
}

date_default_timezone_set('Europe/Berlin');

// start checking the dependencies.
$problems = array();

// check php-version.
if (version_compare(PHP_VERSION, $config->bootstrap->expected->php_version, '<')) {
  $problems[] = 'You have PHP '.PHP_VERSION
               .' and you need PHP '.$config->bootstrap->expected->php_version.' or higher!';
}

// check expected extensions.
foreach ($config->bootstrap->expected->extensions as $extension) {
  if (!extension_loaded($extension)) {
    $problems[] = 'No ' . $extension . ' extension loaded!';
  }
}

// configure necessary things for the application.
$registry = new Pimf_Registry();

try {

  $db = new Pimf_PDO($dbDsn);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $registry->em = new Pimf_EntityManager($db, $config->app->name);
  $registry->logger = new Pimf_Logger($config->bootstrap->local_temp_directory);
  $registry->logger->init();
  $registry->env = new Pimf_Environment($_SERVER);
  $registry->conf = $config;

} catch (PDOException $e) {
  $problems[] = $e->getMessage();
}

if (!empty($problems)) {
  die(print_r($problems, true));
}

unset($dbDsn, $dbUser, $dbPwd, $db, $extension, $problems, $iniParser, $config);
