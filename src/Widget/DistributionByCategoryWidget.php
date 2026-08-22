<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionByCategoryWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_by_category';
   }

   public function getTitle(): string
   {
      return __('Distribuições por categoria', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-category';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->byCategory($context);
   }
}
