<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class ComputersByProcessorWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_computers_by_cpu'; }
   public function getTitle(): string { return __('Dispositivos por CPU', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-cpu'; }
   public function getData(DashboardContext $context): array { return $this->provider()->computersByProcessor($context); }
}
