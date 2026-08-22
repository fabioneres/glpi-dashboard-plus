<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionBreakdownWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_breakdown';
   }

   public function getTitle(): string
   {
      return __('Satisfação por nota', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-mood-smile';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionBreakdown($context);
   }
}
