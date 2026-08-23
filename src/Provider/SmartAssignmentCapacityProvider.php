<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

use DBConnection;
use GlpiPlugin\Dashboardplus\DashboardContext;
use Plugin;
use Session;

class SmartAssignmentCapacityProvider implements CapacityProviderInterface
{
   private const WORK_SCHEDULES_TABLE = 'glpi_plugin_atribuicaointeligente_work_schedules';
   private const UNAVAILABILITIES_TABLE = 'glpi_plugin_atribuicaointeligente_unavailabilities';

   public function isAvailable(): bool
   {
      $plugin = new Plugin();
      return $plugin->isActivated('atribuicaointeligente')
         && $this->getReadDB()->tableExists(self::WORK_SCHEDULES_TABLE);
   }

   public function getSourceLabel(): string
   {
      return __('Atribuição Inteligente', 'dashboardplus');
   }

   public function getDiagnostics(): array
   {
      $plugin = new Plugin();
      return [
         'source'       => 'atribuicaointeligente',
         'detected'     => $plugin->isActivated('atribuicaointeligente'),
         'available'    => $this->isAvailable(),
         'schedules'    => $this->getReadDB()->tableExists(self::WORK_SCHEDULES_TABLE),
         'unavailability' => $this->getReadDB()->tableExists(self::UNAVAILABILITIES_TABLE),
      ];
   }

   public function getCapacityByTechnician(DashboardContext $context, array $technician_ids): array
   {
      $technician_ids = array_values(array_filter(array_unique(array_map('intval', $technician_ids)), static function(int $id): bool {
         return $id > 0;
      }));

      if ($technician_ids === [] || !$this->isAvailable()) {
         return [];
      }

      $capacity = [];
      foreach ($technician_ids as $users_id) {
         $capacity[$users_id] = [
            'source'         => 'atribuicaointeligente',
            'source_label'   => $this->getSourceLabel(),
            'hours'          => 0.0,
            'label'          => __('Sem escala no período', 'dashboardplus'),
            'is_scheduled'   => false,
            'unavailable'    => false,
            'unavailable_reason' => '',
         ];
      }

      $entity_ids = $this->entityIds($context);
      $entity_sql = $entity_ids === null ? '1 = 1' : 'entities_id IN (' . implode(',', array_map('intval', array_unique(array_merge([0], $entity_ids)))) . ')';
      $users_sql = implode(',', $technician_ids);
      $start = addslashes($context->getStart());
      $end = addslashes($context->getEnd());

      foreach ($this->fetchRows(
         "SELECT users_id, entities_id, weekdays, time_start, time_end, date_start, date_end
          FROM `" . self::WORK_SCHEDULES_TABLE . "`
          WHERE users_id IN ({$users_sql})
            AND is_active = 1
            AND {$entity_sql}
            AND (date_start IS NULL OR date_start <= '{$end}')
            AND (date_end IS NULL OR date_end >= '{$start}')
          ORDER BY entities_id DESC, id ASC"
      ) as $row) {
         $users_id = (int) ($row['users_id'] ?? 0);
         if (!isset($capacity[$users_id])) {
            continue;
         }

         $hours = $this->scheduleHours($row, $context);
         if ($hours <= 0) {
            continue;
         }

         $capacity[$users_id]['hours'] += $hours;
         $capacity[$users_id]['is_scheduled'] = true;
      }

      $unavailable = $this->unavailabilityByTechnician($context, $technician_ids, $entity_ids);
      foreach ($capacity as $users_id => $row) {
         if (isset($unavailable[$users_id])) {
            $capacity[$users_id]['unavailable'] = true;
            $capacity[$users_id]['unavailable_reason'] = $unavailable[$users_id];
         }

         if ((float) $capacity[$users_id]['hours'] > 0) {
            $capacity[$users_id]['label'] = number_format((float) $capacity[$users_id]['hours'], 1, ',', '.') . 'h';
         }
      }

      return $capacity;
   }

   private function scheduleHours(array $row, DashboardContext $context): float
   {
      $start = max(strtotime($context->getStart()), strtotime((string) ($row['date_start'] ?? '')) ?: strtotime($context->getStart()));
      $end = min(strtotime($context->getEnd()), strtotime((string) ($row['date_end'] ?? '')) ?: strtotime($context->getEnd()));
      if ($start === false || $end === false || $start > $end) {
         return 0.0;
      }

      $days = min(366, (int) floor(($end - $start) / 86400) + 1);
      $weekdays = $this->normalizeWeekdays((string) ($row['weekdays'] ?? ''));
      if ($weekdays === []) {
         return 0.0;
      }

      $daily_hours = $this->hoursBetween((string) ($row['time_start'] ?? ''), (string) ($row['time_end'] ?? ''));
      if ($daily_hours <= 0) {
         return 0.0;
      }

      $hours = 0.0;
      for ($i = 0; $i < $days; $i++) {
         $timestamp = strtotime('+' . $i . ' days', $start);
         if ($timestamp !== false && in_array((int) date('w', $timestamp), $weekdays, true)) {
            $hours += $daily_hours;
         }
      }

      return $hours;
   }

   private function unavailabilityByTechnician(DashboardContext $context, array $technician_ids, ?array $entity_ids): array
   {
      if (!$this->getReadDB()->tableExists(self::UNAVAILABILITIES_TABLE)) {
         return [];
      }

      $users_sql = implode(',', $technician_ids);
      $entity_sql = $entity_ids === null ? '1 = 1' : 'entities_id IN (' . implode(',', array_map('intval', array_unique(array_merge([0], $entity_ids)))) . ')';
      $start = addslashes($context->getStartDateTime());
      $end = addslashes($context->getEndDateTime());
      $weekday = (int) date('w');
      $unavailable = [];

      foreach ($this->fetchRows(
         "SELECT users_id, type, date_start, date_end, weekday, comment
          FROM `" . self::UNAVAILABILITIES_TABLE . "`
          WHERE users_id IN ({$users_sql})
            AND is_active = 1
            AND {$entity_sql}
            AND (
               (type IN ('vacation', 'temporary', 'specific_date') AND (date_start IS NULL OR date_start <= '{$end}') AND (date_end IS NULL OR date_end >= '{$start}'))
               OR (type = 'weekly' AND weekday = {$weekday})
            )
          ORDER BY date_start ASC, id ASC"
      ) as $row) {
         $users_id = (int) ($row['users_id'] ?? 0);
         if ($users_id <= 0 || isset($unavailable[$users_id])) {
            continue;
         }
         $label = (string) ($row['type'] ?? '');
         if (!empty($row['comment'])) {
            $label .= ' | ' . (string) $row['comment'];
         }
         $unavailable[$users_id] = $label;
      }

      return $unavailable;
   }

   private function normalizeWeekdays(string $value): array
   {
      $items = explode(',', $value);
      $weekdays = [];
      foreach ($items as $item) {
         $weekday = (int) $item;
         if ($weekday >= 0 && $weekday <= 6) {
            $weekdays[] = $weekday;
         }
      }
      return array_values(array_unique($weekdays));
   }

   private function hoursBetween(string $start, string $end): float
   {
      $start = $start !== '' ? substr($start, 0, 8) : '00:00:00';
      $end = $end !== '' ? substr($end, 0, 8) : '23:59:59';
      $start_seconds = strtotime('1970-01-01 ' . $start);
      $end_seconds = strtotime('1970-01-01 ' . $end);
      if ($start_seconds === false || $end_seconds === false) {
         return 0.0;
      }
      if ($end_seconds < $start_seconds) {
         $end_seconds += 86400;
      }

      return max(0, ($end_seconds - $start_seconds) / 3600);
   }

   private function entityIds(DashboardContext $context): ?array
   {
      if ($context->getEntitiesId() !== null) {
         return $this->expandEntity((int) $context->getEntitiesId(), $context->isRecursive());
      }

      if (Session::canViewAllEntities()) {
         return null;
      }

      $ids = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
      $ids[] = 0;
      return array_values(array_unique($ids));
   }

   private function expandEntity(int $entities_id, bool $recursive): array
   {
      $ids = [$entities_id];
      if ($recursive && function_exists('getSonsOf')) {
         $ids = array_merge($ids, array_map('intval', getSonsOf('glpi_entities', $entities_id)));
      }
      return array_values(array_unique(array_filter($ids, static function(int $id): bool {
         return $id >= 0;
      })));
   }

   private function fetchRows(string $sql): array
   {
      $result = $this->getReadDB()->doQuery($sql);
      if (!$result) {
         return [];
      }

      $rows = [];
      while ($row = $result->fetch_assoc()) {
         $rows[] = $row;
      }

      return $rows;
   }

   private function getReadDB()
   {
      if (class_exists(DBConnection::class)) {
         return DBConnection::getReadConnection();
      }

      global $DB;
      return $DB;
   }
}
