<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use Computer;
use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\Provider\AssetMetricsProvider;

abstract class AssetWidget extends AbstractWidget
{
   public function canView(): bool
   {
      if (!Config::canView()) {
         return false;
      }

      $asset_class = $this->getAssetClass();
      return class_exists($asset_class) && $asset_class::canView();
   }

   protected function provider(): AssetMetricsProvider
   {
      return new AssetMetricsProvider();
   }

   protected function getAssetClass(): string
   {
      return Computer::class;
   }
}
