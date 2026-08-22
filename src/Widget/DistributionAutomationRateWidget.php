<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DistributionAutomationRateWidget extends DistributionMetricWidget
{
   public function getKey(): string
   {
      return 'distribution_automation_rate';
   }

   public function getTitle(): string
   {
      return __('Taxa de automação', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Percentual de chamados distribuídos com automação', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-robot';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->automationRate($context);
   }
}
