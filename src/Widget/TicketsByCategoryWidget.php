<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsByCategoryWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_by_category';
   }

   public function getTitle(): string
   {
      return __('Chamados por categoria', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-category';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->breakdownByCategory($context);
   }
}
