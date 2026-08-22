<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

abstract class AssetMetricWidget extends AssetWidget
{
   public function getType(): string
   {
      return 'metric';
   }
}
