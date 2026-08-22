<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByTypeWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_type';
   }

   public function getTitle(): string
   {
      return __('Incidentes x requisições', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-arrows-split';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByType($context);
   }
}
