<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class ComputersSaoPauloMapWidget extends AssetBreakdownWidget
{
   public function getKey(): string { return 'asset_computers_sp_map'; }
   public function getTitle(): string { return __('Ativos por cidade - São Paulo', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-map-2'; }
   public function getType(): string { return 'map_sp'; }
   public function getDefaultSize(): array { return ['width' => 12, 'height' => 5]; }
   public function getData(DashboardContext $context): array { return $this->provider()->computersBySaoPauloCity($context); }
}
