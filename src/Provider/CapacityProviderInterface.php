<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

use GlpiPlugin\Dashboardplus\DashboardContext;

interface CapacityProviderInterface
{
   public function isAvailable(): bool;

   public function getSourceLabel(): string;

   public function getDiagnostics(): array;

   public function getCapacityByTechnician(DashboardContext $context, array $technician_ids): array;
}
