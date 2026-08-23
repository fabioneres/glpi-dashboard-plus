<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class MonitorsByManufacturerWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_monitors_by_manufacturer'; }
   public function getTitle(): string { return __('Monitor por fabricante', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-device-desktop'; }
   public function getAssetClass(): string { return \Monitor::class; }
   public function getData(DashboardContext $context): array { return $this->provider()->monitorsByManufacturer($context); }
}
