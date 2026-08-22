<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\Provider\TicketMetricsProvider;

abstract class TicketBreakdownWidget extends AbstractWidget
{
   public function getType(): string
   {
      return 'breakdown';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 6,
         'height' => 4,
      ];
   }

   protected function provider(): TicketMetricsProvider
   {
      return new TicketMetricsProvider();
   }
}
