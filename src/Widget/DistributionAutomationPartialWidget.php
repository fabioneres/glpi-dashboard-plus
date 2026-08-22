<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionAutomationPartialWidget extends DistributionMetricWidget
{
   public function getKey(): string
   {
      return 'distribution_automation_partial';
   }

   public function getTitle(): string
   {
      return __('Automação parcial', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados com automação e intervenção manual', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-adjustments';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->automationPartialTickets($context);
   }
}
