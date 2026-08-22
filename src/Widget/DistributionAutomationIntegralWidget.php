<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionAutomationIntegralWidget extends DistributionMetricWidget
{
   public function getKey(): string
   {
      return 'distribution_automation_integral';
   }

   public function getTitle(): string
   {
      return __('Automação integral', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Chamados distribuídos apenas pelo plugin', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-bolt';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->automationIntegralTickets($context);
   }
}
