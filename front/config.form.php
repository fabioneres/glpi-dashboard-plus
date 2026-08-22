<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\ConfigPage;

include('../../../inc/includes.php');
require_once __DIR__ . '/../bootstrap.php';

Config::checkAdmin();
ConfigPage::handleSubmit($_POST);

Html::header(Config::getTypeName(), $_SERVER['PHP_SELF'], 'plugins', 'dashboardplus');
ConfigPage::show();
Html::footer();
