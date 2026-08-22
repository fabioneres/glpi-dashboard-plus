<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionAnsweredCountWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'satisfaction_answered_count';
   }

   public function getTitle(): string
   {
      return __('Pesquisas respondidas', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com nota de satisfação informada', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-message-check';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionAnsweredCount($context);
   }
}
