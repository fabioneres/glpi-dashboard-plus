<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByEntityWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_entity';
   }

   public function getTitle(): string
   {
      return __('Chamados por entidade', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-building';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByEntity($context);
   }
}
