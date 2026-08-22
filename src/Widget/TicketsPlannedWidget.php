<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Ticket;

class TicketsPlannedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_planned';
   }

   public function getTitle(): string
   {
      return __('Chamados planejados', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Em atendimento com planejamento definido', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return Ticket::getStatusClass(Ticket::PLANNED);
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByStatuses($context, [Ticket::PLANNED]);
   }
}
