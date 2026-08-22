<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Ticket;

class TicketsPendingWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_pending';
   }

   public function getTitle(): string
   {
      return __('Chamados pendentes', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados aguardando ação', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return Ticket::getStatusClass(Ticket::WAITING);
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByStatuses($context, [Ticket::WAITING]);
   }
}
