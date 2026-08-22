<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;
use GlpiPlugin\Dashboardplus\Provider\TicketMetricsProvider;

abstract class TicketMetricWidget extends AbstractWidget
{
   public function getType(): string
   {
      return 'metric';
   }

   protected function provider(): TicketMetricsProvider
   {
      return new TicketMetricsProvider();
   }

   abstract public function getData(DashboardContext $context): array;
}
