<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Dashboard;

include('../../../inc/includes.php');
require_once __DIR__ . '/../bootstrap.php';

Config::checkView();

Html::header(Config::getTypeName(), $_SERVER['PHP_SELF'], 'plugins', 'dashboardplus');
Dashboard::show();
Html::footer();
