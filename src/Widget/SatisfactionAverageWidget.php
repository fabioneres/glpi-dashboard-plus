<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionAverageWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'satisfaction_average';
   }

   public function getTitle(): string
   {
      return __('Nota média de satisfação', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Média das respostas de satisfação no período', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-star';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionAverage($context);
   }
}
