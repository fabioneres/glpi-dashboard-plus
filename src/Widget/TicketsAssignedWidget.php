<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Ticket;

class TicketsAssignedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_assigned';
   }

   public function getTitle(): string
   {
      return __('Chamados atribuídos', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Em atendimento com técnico ou grupo atribuído', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return Ticket::getStatusClass(Ticket::ASSIGNED);
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countByStatuses($context, [Ticket::ASSIGNED]);
   }
}
