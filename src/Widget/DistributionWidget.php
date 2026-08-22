<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\Provider\DistributionMetricsProvider;

abstract class DistributionWidget extends AbstractWidget
{
   public function canView(): bool
   {
      return parent::canView() && $this->provider()->isAvailable();
   }

   protected function provider(): DistributionMetricsProvider
   {
      return new DistributionMetricsProvider();
   }
}
