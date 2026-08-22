<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionAverageByMonthWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_average_by_month';
   }

   public function getTitle(): string
   {
      return __('Média de satisfação por mês', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-line';
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
      return $this->provider()->satisfactionAverageByMonth($context);
   }
}
