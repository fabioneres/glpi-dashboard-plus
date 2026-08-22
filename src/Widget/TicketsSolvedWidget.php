<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Ticket;

class TicketsSolvedWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'tickets_solved';
   }

   public function getTitle(): string
   {
      return __('Chamados solucionados', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Solucionados no período selecionado', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return Ticket::getStatusClass(Ticket::SOLVED);
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->countSolved($context);
   }
}
