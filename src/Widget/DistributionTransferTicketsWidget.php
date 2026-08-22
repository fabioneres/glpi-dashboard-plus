<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionTransferTicketsWidget extends DistributionMetricWidget
{
   public function getKey(): string
   {
      return 'distribution_transfer_tickets';
   }

   public function getTitle(): string
   {
      return __('Transferências de entidade', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados transferidos entre entidades', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-arrows-transfer-up';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->transferTickets($context);
   }
}
