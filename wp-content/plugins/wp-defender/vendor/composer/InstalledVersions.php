<?php











namespace Composer;

use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' =>
  array (
    'pretty_version' => 'dev-release/2.4.5',
    'version' => 'dev-release/2.4.5',
    'aliases' =>
    array (
    ),
    'reference' => 'aa114f4212fd3b970d54ea0baf483b989cc73bef',
    'name' => 'incsub/wp-defender',
  ),
  'versions' =>
  array (
    'container-interop/container-interop' =>
    array (
      'pretty_version' => '1.2.0',
      'version' => '1.2.0.0',
      'aliases' =>
      array (
      ),
      'reference' => '79cbf1341c22ec75643d841642dd5d6acd83bdb8',
    ),
    'container-interop/container-interop-implementation' =>
    array (
      'provided' =>
      array (
        0 => '^1.0',
      ),
    ),
    'gettext/gettext' =>
    array (
      'pretty_version' => 'v4.8.3',
      'version' => '4.8.3.0',
      'aliases' =>
      array (
      ),
      'reference' => '57ff4fb16647e78e80a5909fe3c190f1c3110321',
    ),
    'gettext/languages' =>
    array (
      'pretty_version' => '2.6.0',
      'version' => '2.6.0.0',
      'aliases' =>
      array (
      ),
      'reference' => '38ea0482f649e0802e475f0ed19fa993bcb7a618',
    ),
    'incsub/wp-defender' =>
    array (
      'pretty_version' => 'dev-release/2.4.5',
      'version' => 'dev-release/2.4.5',
      'aliases' =>
      array (
      ),
      'reference' => 'aa114f4212fd3b970d54ea0baf483b989cc73bef',
    ),
    'mnapoli/php-di' =>
    array (
      'replaced' =>
      array (
        0 => '*',
      ),
    ),
    'php-di/invoker' =>
    array (
      'pretty_version' => '1.3.3',
      'version' => '1.3.3.0',
      'aliases' =>
      array (
      ),
      'reference' => '1f4ca63b9abc66109e53b255e465d0ddb5c2e3f7',
    ),
    'php-di/php-di' =>
    array (
      'pretty_version' => '5.4.6',
      'version' => '5.4.6.0',
      'aliases' =>
      array (
      ),
      'reference' => '3f9255659595f3e289f473778bb6c51aa72abbbd',
    ),
    'php-di/phpdoc-reader' =>
    array (
      'pretty_version' => '2.2.1',
      'version' => '2.2.1.0',
      'aliases' =>
      array (
      ),
      'reference' => '66daff34cbd2627740ffec9469ffbac9f8c8185c',
    ),
    'psr/container' =>
    array (
      'pretty_version' => '1.0.0',
      'version' => '1.0.0.0',
      'aliases' =>
      array (
      ),
      'reference' => 'b7ce3b176482dbbc1245ebf52b181af44c2cf55f',
    ),
    'psr/container-implementation' =>
    array (
      'provided' =>
      array (
        0 => '^1.0',
      ),
    ),
    'vlucas/valitron' =>
    array (
      'pretty_version' => 'v1.4.9',
      'version' => '1.4.9.0',
      'aliases' =>
      array (
      ),
      'reference' => '81515dcc951e1f636a1a18ece2f4154dfa123438',
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