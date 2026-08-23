<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class CapacityTeamSummaryWidget extends CapacityWidget
{
   public function getKey(): string
   {
      return 'capacity_team_summary';
   }

   public function getTitle(): string
   {
      return __('Resumo da capacidade da equipe', 'dashboardplus');
   }

   public function getType(): string
   {
      return 'breakdown';
   }

   public function getDefaultSize(): array
   {
      return ['width' => 6, 'height' => 3];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->teamSummary($context);
   }
}
