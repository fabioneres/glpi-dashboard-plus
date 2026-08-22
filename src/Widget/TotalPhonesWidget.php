<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use Phone;

class TotalPhonesWidget extends AssetMetricWidget
{
   public function getKey(): string { return 'asset_total_phones'; }
   public function getTitle(): string { return __('Total telefones', 'dashboardplus'); }
   public function getIcon(): string { return 'ti ti-phone'; }
   public function getData(DashboardContext $context): array { return $this->provider()->totalPhones($context); }
   protected function getAssetClass(): string { return Phone::class; }
}
