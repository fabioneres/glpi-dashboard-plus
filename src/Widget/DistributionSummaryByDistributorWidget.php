<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionSummaryByDistributorWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_summary_by_distributor';
   }

   public function getTitle(): string
   {
      return __('Resumo por distribuidor', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-users-group';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->summaryByDistributor($context);
   }
}
