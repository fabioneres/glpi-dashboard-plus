<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

abstract class AssetBreakdownWidget extends AssetWidget
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
}
