<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsOpenWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_open';
   }

   public function getTitle(): string
   {
      return __('Chamados abertos', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Criados no período selecionado', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-inbox';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countCreated($context);
   }
}
