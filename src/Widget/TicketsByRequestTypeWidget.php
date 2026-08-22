<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByRequestTypeWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_request_type';
   }

   public function getTitle(): string
   {
      return __('Chamados por origem', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-inbox';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByRequestType($context);
   }
}
