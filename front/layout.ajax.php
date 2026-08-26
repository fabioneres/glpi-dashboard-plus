<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Logger;
use GlpiPlugin\Dashboardplus\Widget\WidgetRegistry;

include('../../../inc/includes.php');
require_once __DIR__ . '/../bootstrap.php';

Session::checkLoginUser();

header('Content-Type: application/json; charset=UTF-8');

if (!Config::canConfigureWidgets()) {
   http_response_code(403);
   echo json_encode([
      'ok'      => false,
      'message' => __('Acesso negado', 'dashboardplus'),
   ]);
   exit;
}

$layout_raw = (string) ($_POST['layout'] ?? '');
$layout = json_decode($layout_raw, true);

if (!is_array($layout) && isset($_POST['layout_b64'])) {
   $decoded = base64_decode((string) $_POST['layout_b64'], true);
   if (is_string($decoded)) {
      $layout = json_decode($decoded, true);
   }
}

if (!is_array($layout)) {
   $raw_post = file_get_contents('php://input');
   if (is_string($raw_post) && $raw_post !== '') {
      $parsed = [];
      parse_str($raw_post, $parsed);
      if (isset($parsed['layout'])) {
         $layout = json_decode((string) $parsed['layout'], true);
      }
      if (!is_array($layout) && isset($parsed['layout_b64'])) {
         $decoded = base64_decode((string) $parsed['layout_b64'], true);
         if (is_string($decoded)) {
            $layout = json_decode($decoded, true);
         }
      }
   }
}

if (!is_array($layout)) {
   http_response_code(400);
   echo json_encode([
      'ok'      => false,
      'message' => __('Layout inválido', 'dashboardplus'),
   ]);
   exit;
}

try {
   WidgetRegistry::saveLayout($layout);
   echo json_encode([
      'ok'      => true,
      'message' => __('Layout salvo', 'dashboardplus'),
      'token'   => Session::getNewCSRFToken(),
   ]);
} catch (Throwable $e) {
   Logger::exception($e, 'Falha ao salvar layout dos widgets');
   http_response_code(500);
   echo json_encode([
      'ok'      => false,
      'message' => __('Não foi possível salvar o layout.', 'dashboardplus'),
   ]);
}
