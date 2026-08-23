<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

use GlpiPlugin\Dashboardplus\DashboardContext;

class DefaultCapacityProvider implements CapacityProviderInterface
{
   public function isAvailable(): bool
   {
      return true;
   }

   public function getSourceLabel(): string
   {
      return __('Capacidade não determinada', 'dashboardplus');
   }

   public function getDiagnostics(): array
   {
      return [
         'source'    => 'default',
         'available' => true,
      ];
   }

   public function getCapacityByTechnician(DashboardContext $context, array $technician_ids): array
   {
      $capacity = [];
      foreach (array_unique(array_map('intval', $technician_ids)) as $users_id) {
         if ($users_id <= 0) {
            continue;
         }
         $capacity[$users_id] = [
            'source'         => 'default',
            'source_label'   => $this->getSourceLabel(),
            'hours'          => null,
            'label'          => __('Não determinada', 'dashboardplus'),
            'is_scheduled'   => null,
            'unavailable'    => false,
            'unavailable_reason' => '',
         ];
      }

      return $capacity;
   }
}
