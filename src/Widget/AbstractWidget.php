<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\Config;
use Ticket;

abstract class AbstractWidget implements WidgetInterface
{
   public function getDescription(): string
   {
      return '';
   }

   public function getIcon(): string
   {
      return 'ti ti-chart-bar';
   }

   public function getRequiredRight(): string
   {
      return Config::RIGHT_VIEW;
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => $this->getType() === 'metric' ? 3 : 6,
         'height' => $this->getType() === 'metric' ? 2 : 4,
      ];
   }

   public function canView(): bool
   {
      if (!Config::canView()) {
         return false;
      }

      if ($this->getRequiredRight() === Config::RIGHT_GLOBAL) {
         return Config::canViewGlobalIndicators();
      }

      if (class_exists(Ticket::class) && !Ticket::canView()) {
         return false;
      }

      return true;
   }
}
