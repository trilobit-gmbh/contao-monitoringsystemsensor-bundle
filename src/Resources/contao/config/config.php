<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-monitoringsystemsensor-bundle
 */

use Trilobit\MonitoringsystemsensorBundle\Controller\MonitoringSystem;

$GLOBALS['TL_HOOKS']['monitoringClientDataRead']['MonitoringClientSensorSystem'] = [MonitoringSystem::class, 'readData'];
