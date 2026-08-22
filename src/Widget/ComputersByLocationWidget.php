<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class ComputersByLocationWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_computers_by_location'; }
   public function getTitle(): string { return __('Computadores por localização', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-map-pin'; }
   public function getData(DashboardContext $context): array { return $this->provider()->computersByLocation($context); }
}
