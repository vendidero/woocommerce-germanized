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
    'reference' => '1bc689398a55189f713ac4f6be07dde8909e2f5a',
    'name' => 'vendidero/woocommerce-germanized',
  ),
  'versions' => 
  array (
    'automattic/jetpack-autoloader' => 
    array (
      'pretty_version' => '2.10.1',
      'version' => '2.10.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '20393c4677765c3e737dcb5aee7a3f7b90dce4b3',
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
      'pretty_version' => 'v1.12.0',
      'version' => '1.12.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
    ),
    'dvdoug/boxpacker' => 
    array (
      'pretty_version' => '3.9.2',
      'version' => '3.9.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '175f8ab63961975ff1fe52df1379b2c28742a9cd',
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
      'pretty_version' => '1.1.4',
      'version' => '1.1.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11',
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
      'pretty_version' => '1.8.4',
      'version' => '1.8.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b0ddd9c5b98ced8230ef38534f6f3c17308a7974',
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
    'vendidero/one-stop-shop-woocommerce' => 
    array (
      'pretty_version' => 'v1.1.5',
      'version' => '1.1.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '9919101058bf57c324eb36fd36b5d6326c5fb827',
    ),
    'vendidero/woocommerce-germanized' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
      ),
      'reference' => '1bc689398a55189f713ac4f6be07dde8909e2f5a',
    ),
    'vendidero/woocommerce-germanized-dhl' => 
    array (
      'pretty_version' => 'v1.6.1',
      'version' => '1.6.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '70efa64dbf4cf54fb0d2864792df59ee776a1321',
    ),
    'vendidero/woocommerce-germanized-shipments' => 
    array (
      'pretty_version' => 'v1.6.1',
      'version' => '1.6.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '14f36456fa29a88683736b0fa65892d0d38811ca',
    ),
    'vendidero/woocommerce-trusted-shops' => 
    array (
      'pretty_version' => 'v4.0.12',
      'version' => '4.0.12.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c31be4a528bcb13b5d8cd842ec20efa469e65662',
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
