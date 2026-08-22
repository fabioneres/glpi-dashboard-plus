<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionEvolutionWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_evolution';
   }

   public function getTitle(): string
   {
      return __('Evolução das distribuições', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-histogram';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->evolution($context);
   }
}
