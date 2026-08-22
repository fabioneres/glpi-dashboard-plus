<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionDistinctTicketsWidget extends DistributionMetricWidget
{
   public function getKey(): string
   {
      return 'distribution_distinct_tickets';
   }

   public function getTitle(): string
   {
      return __('Chamados distribuídos', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com eventos de distribuição no período', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-route';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->distinctTickets($context);
   }
}
