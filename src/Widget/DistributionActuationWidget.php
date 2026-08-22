<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionActuationWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_actuation';
   }

   public function getTitle(): string
   {
      return __('Atuação na distribuição', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-donut';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->actuation($context);
   }
}
