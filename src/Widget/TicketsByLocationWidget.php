<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByLocationWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_location';
   }

   public function getTitle(): string
   {
      return __('Tickets por localização', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-map-pin';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByLocation($context);
   }
}
