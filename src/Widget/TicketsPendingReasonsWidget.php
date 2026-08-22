<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsPendingReasonsWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_pending_reasons';
   }

   public function getTitle(): string
   {
      return __('Motivos de pendência', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-hourglass';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->pendingReasons($context);
   }
}
