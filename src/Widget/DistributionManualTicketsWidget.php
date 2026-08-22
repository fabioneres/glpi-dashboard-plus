<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionManualTicketsWidget extends DistributionMetricWidget
{
   public function getKey(): string
   {
      return 'distribution_manual_tickets';
   }

   public function getTitle(): string
   {
      return __('Atuação manual', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados distribuídos manualmente', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-hand-click';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->manualTickets($context);
   }
}
