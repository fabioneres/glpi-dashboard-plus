<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Dashboardplus\AboutPage;
use GlpiPlugin\Dashboardplus\Config;

include('../../../inc/includes.php');
require_once __DIR__ . '/../bootstrap.php';

Config::checkView();

Html::header(Config::getTypeName(), $_SERVER['PHP_SELF'], 'plugins', 'dashboardplus');
AboutPage::show();
Html::footer();
