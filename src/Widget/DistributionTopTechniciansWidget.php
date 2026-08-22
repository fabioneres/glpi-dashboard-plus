<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionTopTechniciansWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_top_technicians';
   }

   public function getTitle(): string
   {
      return __('Top técnicos distribuídos', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-user-check';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->topTechnicians($context);
   }
}
