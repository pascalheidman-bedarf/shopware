<?php
/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Recovery\Update;

use Slim\Http\Request;

class Utils
{
    public static function check($file)
    {
        if (file_exists($file)) {
            if (!is_writeable($file)) {
                return $file;
            } else {
                return true;
            }
        }

        return self::check(dirname($file));
    }

    /**
     * @param string $xmlPath
     *
     * @return array
     */
    public static function getPaths($xmlPath)
    {
        $paths = array();
        $xml = simplexml_load_file($xmlPath);

        foreach ($xml->files->file as $entry) {
            $paths[] = (string) $entry->name;
        }

        return $paths;
    }

    /**
     * @param array  $paths
     * @param string $basePath
     *
     * @return array
     */
    public static function checkPaths($paths, $basePath)
    {
        $results = array();
        foreach ($paths as $path) {
            $name   = $basePath . '/' . $path;
            $result = file_exists($name) && is_readable($name) && is_writeable($name);
            $results[] = array(
                'name'   => $path,
                'result' => $result,
            );
        }

        return $results;
    }

    /**
     * @return mixed
     */
    public static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * @param      $dir
     * @param bool $includeDir
     */
    public static function deleteDir($dir, $includeDir = false)
    {
        $dir = rtrim($dir, '/') . '/';
        if (!is_dir($dir)) {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var $path \SplFileInfo */
            foreach ($iterator as $path) {
                if ($path->getFilename() == '.gitkeep') {
                    continue;
                }

                $path->isFile() ? @unlink($path->getPathname()) : @rmdir($path->getPathname());
            }
        } catch (\Exception $e) {
            // todo: add error handling
            // empty catch intendded.
        };

        if ($includeDir) {
            @rmdir($dir);
        }
    }

    /**
     * @param string $clientIp
     *
     * @return bool
     */
    public static function isAllowed($clientIp)
    {
        $allowed = trim(file_get_contents(UPDATE_PATH . '/' . 'allowed_ip.txt'));
        $allowed = explode("\n", $allowed);
        $allowed = array_map('trim', $allowed);

        return in_array($clientIp, $allowed);
    }

    /**
     * @param \Slim\Http\Request $request
     * @param string $lang
     * @return string
     */
    public static function getLanguage(Request $request, $lang = null)
    {
        $allowedLanguages = array("de", "en");
        $selectedLanguage = "de";

        if ($lang && in_array($lang, $allowedLanguages)) {
            return $lang;
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $selectedLanguage = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $selectedLanguage = substr($selectedLanguage[0], 0, 2);
        }

        if (empty($selectedLanguage) || !in_array($selectedLanguage, $allowedLanguages)) {
            $selectedLanguage = "de";
        }

        if (isset($_POST["language"]) && in_array($_POST["language"], $allowedLanguages)) {
            $selectedLanguage     = $_POST["language"];
            $_SESSION["language"] = $selectedLanguage;
        } elseif (isset($_SESSION["language"]) && in_array($_SESSION["language"], $allowedLanguages)) {
            $selectedLanguage = $_SESSION["language"];
        } else {
            $_SESSION["language"] = $selectedLanguage;
        }

        return $selectedLanguage;
    }

    /**
     * @param array $config Shopware Configuration
     *
     * @return \PDO
     */
    public static function getConnection(array $config = array())
    {
        $dbConfig = $config['db'];
        if (!isset($dbConfig['host'])) {
            $dbConfig['host'] = 'localhost';
        }

        $dsn = array();
        $dsn[] = 'host=' . $dbConfig['host'];
        $dsn[] = 'dbname=' . $dbConfig['dbname'];

        if (isset($dbConfig['port'])) {
            $dsn[] = 'port=' . $dbConfig['port'];
        }
        if (isset($dbConfig['unix_socket'])) {
            $dsn[] = 'unix_socket=' . $dbConfig['unix_socket'];
        }

        $dsn = 'mysql:' . implode(';', $dsn);

        try {
            $conn = new \PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'")
            );
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            echo 'ERROR: ' . $e->getMessage();
            exit(1);
        }

        return $conn;
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    public static function cleanPath($dir)
    {
        $errorFiles = array();

        if (is_file($dir)) {
            try {
                unlink($dir);
            } catch (\ErrorException $e) {
                $errorFiles[$dir] = true;
            }
        } else {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var \SplFileInfo $path */
            foreach ($iterator as $path) {
                try {
                    if ($path->isDir()) {
                        rmdir($path->__toString());
                    } else {
                        unlink($path->__toString());
                    }
                } catch (\ErrorException $e) {
                    $errorFiles[$dir] = true;
                }
            }

            try {
                rmdir($dir);
            } catch (\ErrorException $e) {
                $errorFiles[$dir] = true;
            }
        }

        return array_keys($errorFiles);
    }
}
