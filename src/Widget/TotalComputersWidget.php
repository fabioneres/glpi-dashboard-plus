<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TotalComputersWidget extends AssetMetricWidget
{
   public function getKey(): string { return 'asset_total_computers'; }
   public function getTitle(): string { return __('Total computadores', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-device-desktop'; }
   public function getData(DashboardContext $context): array { return $this->provider()->totalComputers($context); }
}
