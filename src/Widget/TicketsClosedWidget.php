<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Ticket;

class TicketsClosedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_closed';
   }

   public function getTitle(): string
   {
      return __('Chamados fechados', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Fechados no período selecionado', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return Ticket::getStatusClass(Ticket::CLOSED);
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countClosed($context);
   }
}
