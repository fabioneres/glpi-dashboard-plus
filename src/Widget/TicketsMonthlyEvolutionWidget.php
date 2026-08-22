<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsMonthlyEvolutionWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_monthly_evolution';
   }

   public function getTitle(): string
   {
      return __('Evolução de chamados por mês/ano', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-bar';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 12,
         'height' => 4,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->monthlyEvolution($context);
   }
}
