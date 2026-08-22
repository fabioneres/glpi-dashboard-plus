<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class ComputersByTypeWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_computers_by_type'; }
   public function getTitle(): string { return __('Tipo de computador', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-devices-pc'; }
   public function getData(DashboardContext $context): array { return $this->provider()->computersByType($context); }
}
