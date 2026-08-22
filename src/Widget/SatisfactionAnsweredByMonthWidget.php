<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionAnsweredByMonthWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_answered_by_month';
   }

   public function getTitle(): string
   {
      return __('Pesquisas respondidas por mês', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-calendar-stats';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 6,
         'height' => 4,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionAnsweredByMonth($context);
   }
}
