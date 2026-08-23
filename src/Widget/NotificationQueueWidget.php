<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Widget;

use GlpiPlugin\Dashboardplus\DashboardContext;

class NotificationQueueWidget extends TicketMetricWidget
{
   public function getKey(): string
   {
      return 'notification_queue';
   }

   public function getTitle(): string
   {
      return __('Fila de notificações', 'dashboardplus');
   }

   public function getDescription(): string
   {
      return __('Notificações pendentes na fila nativa do GLPI', 'dashboardplus');
   }

   public function getIcon(): string
   {
      return 'ti ti-bell';
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
      return $this->provider()->countNotificationQueue($context);
   }
}
