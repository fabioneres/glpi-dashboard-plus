<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SlaComplianceWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'sla_compliance';
   }

   public function getTitle(): string
   {
      return __('SLA cumprido x violado', 'dashboardplus');
   }

   public function getType(): string
   {
      return 'ratio';
   }

   public function getIcon(): string
   {
      return 'ti ti-clock-check';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 6,
         'height' => 3,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->slaCompliance($context);
   }
}
