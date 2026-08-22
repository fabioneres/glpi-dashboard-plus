<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class ComputersByOperatingSystemWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_computers_by_os'; }
   public function getTitle(): string { return __('Sistema operacional', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-brand-windows'; }
   public function getData(DashboardContext $context): array { return $this->provider()->computersByOperatingSystem($context); }
}
