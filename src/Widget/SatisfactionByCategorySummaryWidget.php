<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionByCategorySummaryWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_by_category_summary';
   }

   public function getTitle(): string
   {
      return __('Categoria x pesquisa de satisfação', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-category';
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionByCategorySummary($context);
   }
}
