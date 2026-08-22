<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

abstract class DistributionMetricWidget extends DistributionWidget
{
   public function getType(): string
   {
      return 'metric';
   }
}
