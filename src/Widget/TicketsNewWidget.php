<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Ticket;

class TicketsNewWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_new';
   }

   public function getTitle(): string
   {
      return __('Chamados novos', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Status novo no período selecionado', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return Ticket::getStatusClass(Ticket::INCOMING);
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByStatuses($context, Ticket::getNewStatusArray());
   }
}
