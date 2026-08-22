<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

interface WidgetInterface
{
   public function getKey(): string;

   public function getTitle(): string;

   public function getDescription(): string;

   public function getIcon(): string;

   public function getType(): string;

   public function getRequiredRight(): string;

   public function getDefaultSize(): array;

   public function canView(): bool;

   public function getData(DashboardContext $context): array;
}
