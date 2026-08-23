<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SlaByCategoryWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'sla_by_category';
   }

   public function getTitle(): string
   {
      return __('SLA por categoria', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-category-2';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 12,
         'height' => 5,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->slaComplianceByCategory($context);
   }
}
