<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsUnassignedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_unassigned';
   }

   public function getTitle(): string
   {
      return __('Tickets não atribuídos', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados não solucionados sem técnico e sem grupo atribuído', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-user-question';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countUnassigned($context);
   }
}
