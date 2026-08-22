<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionTopDistributorsWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_top_distributors';
   }

   public function getTitle(): string
   {
      return __('Top distribuidores', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-medal';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->topDistributors($context);
   }
}
