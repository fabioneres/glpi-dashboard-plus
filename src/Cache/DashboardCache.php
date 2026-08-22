<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Cache;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DashboardCache
{
   public static function get(string $widget_key, DashboardContext $context)
   {
      global $GLPI_CACHE;

      if (!$context->useCache() || !isset($GLPI_CACHE)) {
         return null;
      }

      $value = $GLPI_CACHE->get(self::getKey($widget_key, $context));
      return is_array($value) ? $value : null;
   }

   public static function set(string $widget_key, DashboardContext $context, array $data): void
   {
      global $GLPI_CACHE;

      if (!$context->useCache() || !isset($GLPI_CACHE)) {
         return;
      }

      $GLPI_CACHE->set(self::getKey($widget_key, $context), $data, $context->getCacheTtl());
   }

   private static function getKey(string $widget_key, DashboardContext $context): string
   {
      return 'dashboardplus_' . $widget_key . '_' . $context->cacheKeySuffix();
   }
}
