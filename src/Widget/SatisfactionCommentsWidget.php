<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class SatisfactionCommentsWidget extends TicketBreakdownWidget
{
   public function getKey(): string
   {
      return 'satisfaction_comments';
   }

   public function getTitle(): string
   {
      return __('Pesquisa satisfação x comentário', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-message-2';
   }

   public function getDefaultSize(): array
   {
      return [
         'width'  => 12,
         'height' => 4,
      ];
   }

   public function getData(DashboardContext $context): array
   {
      return $this->provider()->satisfactionComments($context);
   }
}
