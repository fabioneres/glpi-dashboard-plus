<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionTransfersByEntityWidget extends DistributionBreakdownWidget
{
   public function getKey(): string
   {
      return 'distribution_transfers_by_entity';
   }

   public function getTitle(): string
   {
      return __('Transferências por entidade', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-building-community';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->transfersByEntity($context);
   }
}
