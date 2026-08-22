<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Monitor;

class TotalMonitorsWidget extends AssetMetricWidget
{
   public function getKey(): string { return 'asset_total_monitors'; }
   public function getTitle(): string { return __('Total monitores', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-device-tv'; }
   public function getData(DashboardContext $context): array { return $this->provider()->totalMonitors($context); }
   protected function getAssetClass(): string { return Monitor::class; }
}
