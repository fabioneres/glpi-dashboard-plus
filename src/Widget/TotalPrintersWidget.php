<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Printer;

class TotalPrintersWidget extends AssetMetricWidget
{
   public function getKey(): string { return 'asset_total_printers'; }
   public function getTitle(): string { return __('Total impressoras', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-printer'; }
   public function getData(DashboardContext $context): array { return $this->provider()->totalPrinters($context); }
   protected function getAssetClass(): string { return Printer::class; }
}
