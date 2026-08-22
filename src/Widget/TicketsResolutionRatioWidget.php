<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class TicketsResolutionRatioWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'tickets_resolution_ratio';
   }

   public function getTitle(): string
   {
      return __('Não solucionados x solucionados/fechados', 'dashboardplus');
   }

   public function getType(): string
   {
      return 'ratio';
   }

   public function getIcon(): string
   {
      return 'ti ti-scale';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 6,
         'height' => 3,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->resolutionRatio($context);
   }
}
