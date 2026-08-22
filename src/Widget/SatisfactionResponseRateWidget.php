<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionResponseRateWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'satisfaction_response_rate';
   }

   public function getTitle(): string
   {
      return __('% pesquisa respondida', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Respondidas sobre chamados do período', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-percentage';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionResponseRate($context);
   }
}
