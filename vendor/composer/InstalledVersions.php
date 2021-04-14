<?php

namespace Composer;

use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-master',
    'version' => 'dev-master',
    'aliases' => 
    array (
    ),
    'reference' => 'acd17643c58e0968904d4459f45c4838e93b0312',
    'name' => 'vendidero/woocommerce-germanized',
  ),
  'versions' => 
  array (
    'automattic/jetpack-autoloader' => 
    array (
      'pretty_version' => 'v2.7.1',
      'version' => '2.7.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '5437697a56aefbdf707849b9833e1b36093d7a73',
    ),
    'baltpeter/internetmarke-php' => 
    array (
      'pretty_version' => 'v0.5.0',
      'version' => '0.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '7657ee5a55eb80c77a35e35ce71c465364d73ab4',
    ),
    'composer/installers' => 
    array (
      'pretty_version' => 'v1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1a0357fccad9d1cc1ea0c9a05b8847fbccccb78d',
    ),
    'dvdoug/boxpacker' => 
    array (
      'pretty_version' => '3.9.0',
      'version' => '3.9.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '0cdb4c023044ea4dc681b42c211a90e2e455d199',
    ),
    'myclabs/deep-copy' => 
    array (
      'pretty_version' => '1.10.2',
      'version' => '1.10.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '776f831124e9c62e1a2c601ecc52e776d8bb7220',
      'replaced' => 
      array (
        0 => '1.10.2',
      ),
    ),
    'psr/log' => 
    array (
      'pretty_version' => '1.1.3',
      'version' => '1.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '0f73288fd15629204f9d42b7055f72dacbe811fc',
    ),
    'roundcube/plugin-installer' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'setasign/fpdf' => 
    array (
      'pretty_version' => '1.8.2',
      'version' => '1.8.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd77904018090c17dc9f3ab6e944679a7a47e710a',
    ),
    'setasign/fpdi' => 
    array (
      'pretty_version' => 'v2.3.6',
      'version' => '2.3.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '6231e315f73e4f62d72b73f3d6d78ff0eed93c31',
    ),
    'shama/baton' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'vendidero/woocommerce-germanized' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
      ),
      'reference' => 'acd17643c58e0968904d4459f45c4838e93b0312',
    ),
    'vendidero/woocommerce-germanized-dhl' => 
    array (
      'pretty_version' => 'v1.5.1',
      'version' => '1.5.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '8fc0543078cc37a1bdd3de775f05e0cb70815fe2',
    ),
    'vendidero/woocommerce-germanized-shipments' => 
    array (
      'pretty_version' => 'v1.5.1',
      'version' => '1.5.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cb18324e1d4ae251968a3f8ed9888040aa1c8bb5',
    ),
    'vendidero/woocommerce-trusted-shops' => 
    array (
      'pretty_version' => 'v4.0.9',
      'version' => '4.0.9.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd5dcf1487799eec37a827ffb75af76b0171bda2c',
    ),
    'wsdltophp/wssecurity' => 
    array (
      'pretty_version' => '1.2.2',
      'version' => '1.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '6a450af3cd462cbf73fdb3a09e80322da893af4f',
    ),
  ),
);







public static function getInstalledPackages()
{
return array_keys(self::$installed['versions']);
}









public static function isInstalled($packageName)
{
return isset(self::$installed['versions'][$packageName]);
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

$ranges = array();
if (isset(self::$installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = self::$installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}





public static function getVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['version'])) {
return null;
}

return self::$installed['versions'][$packageName]['version'];
}





public static function getPrettyVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return self::$installed['versions'][$packageName]['pretty_version'];
}





public static function getReference($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['reference'])) {
return null;
}

return self::$installed['versions'][$packageName]['reference'];
}





public static function getRootPackage()
{
return self::$installed['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
}
}
