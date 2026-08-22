<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class ComputersByManufacturerWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_computers_by_manufacturer'; }
   public function getTitle(): string { return __('Computadores por fabricante', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-building-factory-2'; }
   public function getData(DashboardContext $context): array { return $this->provider()->computersByManufacturer($context); }
}
