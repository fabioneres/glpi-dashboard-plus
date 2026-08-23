<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Dashboardplus\Cache\DashboardCache;
use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Dashboard;
use GlpiPlugin\Dashboardplus\DashboardContext;
use GlpiPlugin\Dashboardplus\Logger;
use GlpiPlugin\Dashboardplus\Widget\WidgetRegistry;
use GlpiPlugin\Dashboardplus\Widget\WidgetRenderer;

include('../../../inc/includes.php');
require_once __DIR__ . '/../bootstrap.php';

Session::checkLoginUser();

header('Content-Type: application/json; charset=UTF-8');

if (!Config::canView()) {
   http_response_code(403);
   echo json_encode([
      'ok'   => false,
      'html' => WidgetRenderer::renderError(__('Acesso negado', 'dashboardplus')),
   ]);
   exit;
}

$key = preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['widget'] ?? ''));
$context = DashboardContext::fromRequest($_GET, Config::getSettings());

if (!Config::canUseDashboardInEntity($context->getEntitiesId(), $context->isRecursive())) {
   http_response_code(403);
   echo json_encode([
      'ok'   => false,
      'html' => WidgetRenderer::renderError(__('Dashboard Plus indisponível para esta entidade', 'dashboardplus')),
   ]);
   exit;
}

$widget = WidgetRegistry::get($key);

if (!$widget || !$widget->canView()) {
   http_response_code(404);
   echo json_encode([
      'ok'   => false,
      'html' => WidgetRenderer::renderError(__('Widget indisponível', 'dashboardplus')),
   ]);
   exit;
}

$dashboard_key = Dashboard::getWidgetDashboardKey($key);
if (!Config::canUseDashboardTabInEntity($dashboard_key, $context->getEntitiesId(), $context->isRecursive())) {
   http_response_code(403);
   echo json_encode([
      'ok'   => false,
      'html' => WidgetRenderer::renderError(__('Acesso negado a esta aba', 'dashboardplus')),
   ]);
   exit;
}

$config = WidgetRegistry::getWidgetConfig($key);
if ((int) ($config['is_enabled'] ?? 0) !== 1) {
   http_response_code(403);
   echo json_encode([
      'ok'   => false,
      'html' => WidgetRenderer::renderError(__('Widget desabilitado', 'dashboardplus')),
   ]);
   exit;
}

try {
   $data = DashboardCache::get($key, $context);
   if ($data === null) {
      $data = $widget->getData($context);
      DashboardCache::set($key, $context, $data);
   }

   echo json_encode([
      'ok'   => true,
      'html' => WidgetRenderer::render($widget, $data, WidgetRegistry::getWidgetOptions($config)),
   ]);
} catch (Throwable $e) {
   Logger::exception($e, 'Falha ao renderizar widget');
   echo json_encode([
      'ok'   => false,
      'html' => WidgetRenderer::renderError(__('Este widget não pôde ser carregado.', 'dashboardplus')),
   ]);
}
