<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SlaResponseComplianceWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'sla_response_compliance';
   }

   public function getTitle(): string
   {
      return __('SLA de atendimento', 'dashboardplus');
   }

   public function getType(): string
   {
      return 'ratio';
   }

   public function getIcon(): string
   {
      return 'ti ti-clock-exclamation';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->slaResponseCompliance($context);
   }
}
