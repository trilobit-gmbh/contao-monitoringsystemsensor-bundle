<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\MonitoringsystemsensorBundle\Controller;

use Contao\Backend;
use Contao\Config;
use Contao\System;
use Doctrine\DBAL\Connection;

/**
 * Class MonitoringSystem.
 */
class MonitoringSystem extends Backend
{
    /**
     * MonitoringSystem constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $data
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function readData($data): array
    {
        $container = System::getContainer();
        $projectDir = $container->getParameter('kernel.project_dir');
        $homeDir = '~';

        /** @var Connection $connection */
        $connection = $container->get('database_connection');

        $phpInfo = $this->getPhpInfo();
        $sqlInfo = $connection->executeQuery('SELECT @@version as version')->fetchAssociative();

        $data['contao.version'] = VERSION.'.'.BUILD;
        $data['contao.maintenance'] = Config::get('maintenanceMode') ? 'true' : 'false';

        $data['php.version'] = \PHP_VERSION;
        $data['php.memory_limit'] = $phpInfo['Core']['memory_limit'];
        $data['php.max_execution_time'] = $phpInfo['Core']['max_execution_time'];
        $data['php.post_max_size'] = $phpInfo['Core']['post_max_size'];
        $data['php.upload_max_filesize'] = $phpInfo['Core']['upload_max_filesize'];

        $data['server.os'] = php_uname();
        $data['server.software'] = $phpInfo['PHP Variables'][current(preg_grep('/(.)*_SERVER\\[.SERVER_SOFTWARE.\\]/', array_keys($phpInfo['PHP Variables'])))];

        $data['sql.version'] = $sqlInfo['version'];

        $data['disk.total'] = (string) disk_total_space($projectDir);
        $data['disk.free'] = (string) disk_free_space($projectDir);
        $data['disk.usage'] = (string) ($data['disk.total'] - $data['disk.free']);

        $qdu = (string) preg_replace('/(\d*)\t.*/', '$1', exec('du '.$homeDir));
        if ('' === $qdu) {
            $qdu = (string) preg_replace('/(\d*)\t.*/', '$1', exec('du '.$projectDir));
        }
        $data['quota.usage'] = '' !== $qdu ? $qdu.'000' : '';

        return $data;
    }

    /**
     * @return array
     */
    protected function getPhpInfo()
    {
        ob_start();
        phpinfo(-1);

        $data = preg_replace(
            ['#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',  "#\r?\n#", '#</(h1|h2|h3|tr)>#', '# +<#',
                "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
                .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                '# +#', '#<tr>#', '#</tr>#', ],
            ['$1', '', '', '', '</$1>'."\n", '<', ' ', ' ', ' ', '', ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>'."\n".
                '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%', ],
            ob_get_clean()
        );

        $sections = explode('<h2>', strip_tags($data, '<h2><th><td>'));
        unset($sections[0]);

        $data = [];
        foreach ($sections as $section) {
            $n = substr($section, 0, strpos($section, '</h2>'));
            preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $askapache, \PREG_SET_ORDER);
            foreach ($askapache as $m) {
                $data[$n][$m[1]] = (!isset($m[3]) || $m[2] === $m[3]) ? $m[2] : \array_slice($m, 2);
            }
        }

        return $data;
    }
}
