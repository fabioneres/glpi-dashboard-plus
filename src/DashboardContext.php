<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use Session;

class DashboardContext
{
   private $start;
   private $end;
   private $entities_id;
   private $is_recursive;
   private $groups_id;
   private $users_id;
   private $itilcategories_id;
   private $type;
   private $priority;
   private $settings;
   private $configured_entities;
   private $period_days;
   private $satisfaction_start;
   private $satisfaction_end;

   public function __construct(
      string $start,
      string $end,
      ?int $entities_id,
      bool $is_recursive,
      ?int $groups_id,
      ?int $users_id,
      ?int $itilcategories_id,
      ?int $type,
      ?int $priority,
      array $settings,
      array $configured_entities = [],
      int $period_days = 30,
      ?string $satisfaction_start = null,
      ?string $satisfaction_end = null
   )
   {
      $this->start = $start;
      $this->end = $end;
      $this->entities_id = $entities_id;
      $this->is_recursive = $is_recursive;
      $this->groups_id = $groups_id;
      $this->users_id = $users_id;
      $this->itilcategories_id = $itilcategories_id;
      $this->type = $type;
      $this->priority = $priority;
      $this->settings = $settings;
      $this->configured_entities = $configured_entities;
      $this->period_days = $period_days;
      $this->satisfaction_start = $satisfaction_start ?: $start;
      $this->satisfaction_end = $satisfaction_end ?: $end;
   }

   public static function fromRequest(array $request, array $settings): self
   {
      $period_value = (string) ($request['period_days'] ?? $settings['default_period_days'] ?? 30);
      $period_days = $period_value === '0' || $period_value === 'all'
         ? 0
         : max(1, min(3660, (int) $period_value));
      $fallback_start = $period_days === 0
         ? '1970-01-01'
         : date('Y-m-d', strtotime('-' . ($period_days - 1) . ' days'));

      $start = self::normalizeDate(
         (string) ($request['start'] ?? ''),
         $fallback_start
      );
      $end = self::normalizeDate((string) ($request['end'] ?? ''), date('Y-m-d'));

      if ($start > $end) {
         [$start, $end] = [$end, $start];
      }

      $satisfaction_start = self::normalizeDate(
         (string) ($request['satisfaction_start'] ?? ''),
         $start
      );
      $satisfaction_end = self::normalizeDate(
         (string) ($request['satisfaction_end'] ?? ''),
         $end
      );

      if ($satisfaction_start > $satisfaction_end) {
         [$satisfaction_start, $satisfaction_end] = [$satisfaction_end, $satisfaction_start];
      }

      $configured_entities = Config::getConfiguredEntityRows();
      $configured_ids = array_map(static function(array $row): int {
         return (int) $row['entities_id'];
      }, $configured_entities);

      $entities_id = null;
      if (isset($request['entities_id']) && $request['entities_id'] !== '') {
         $candidate = (int) $request['entities_id'];
         $allowed_by_config = !count($configured_ids) || in_array($candidate, $configured_ids, true);
         if ($candidate >= 0 && $allowed_by_config && Session::haveAccessToEntity($candidate, true)) {
            $entities_id = $candidate;
         }
      }

      $is_recursive = isset($request['is_recursive'])
         ? ((int) $request['is_recursive'] === 1)
         : (bool) ($_SESSION['glpiactive_entity_recursive'] ?? false);

      $type = self::normalizeTicketType($request['type'] ?? null);
      $priority = self::normalizePriority($request['priority'] ?? null);

      return new self(
         $start,
         $end,
         $entities_id,
         $is_recursive,
         self::normalizePositiveId($request['groups_id'] ?? null),
         self::normalizePositiveId($request['users_id'] ?? null),
         self::normalizePositiveId($request['itilcategories_id'] ?? null),
         $type,
         $priority,
         $settings,
         $configured_entities,
         $period_days,
         $satisfaction_start,
         $satisfaction_end
      );
   }

   public function getStart(): string
   {
      return $this->start;
   }

   public function getEnd(): string
   {
      return $this->end;
   }

   public function getStartDateTime(): string
   {
      return $this->start . ' 00:00:00';
   }

   public function getEndDateTime(): string
   {
      return $this->end . ' 23:59:59';
   }

   public function getEntitiesId(): ?int
   {
      return $this->entities_id;
   }

   public function isRecursive(): bool
   {
      return $this->is_recursive;
   }

   public function getGroupsId(): ?int
   {
      return $this->groups_id;
   }

   public function getUsersId(): ?int
   {
      return $this->users_id;
   }

   public function getItilcategoriesId(): ?int
   {
      return $this->itilcategories_id;
   }

   public function getType(): ?int
   {
      return $this->type;
   }

   public function getPriority(): ?int
   {
      return $this->priority;
   }

   public function getPeriodDays(): int
   {
      return $this->period_days;
   }

   public function getSatisfactionStart(): string
   {
      return $this->satisfaction_start;
   }

   public function getSatisfactionEnd(): string
   {
      return $this->satisfaction_end;
   }

   public function getSatisfactionStartDateTime(): string
   {
      return $this->satisfaction_start . ' 00:00:00';
   }

   public function getSatisfactionEndDateTime(): string
   {
      return $this->satisfaction_end . ' 23:59:59';
   }

   public function hasCustomSatisfactionPeriod(): bool
   {
      return $this->satisfaction_start !== $this->start || $this->satisfaction_end !== $this->end;
   }

   public function isAllHistory(): bool
   {
      return $this->period_days === 0 && $this->start === '1970-01-01';
   }

   public function hasConfiguredEntityScope(): bool
   {
      return count($this->configured_entities) > 0;
   }

   public function useCache(): bool
   {
      return (int) ($this->settings['use_cache'] ?? 1) === 1;
   }

   public function getCacheTtl(): int
   {
      return max(30, (int) ($this->settings['cache_ttl'] ?? 120));
   }

   public function getEntityCriteria(string $table = 'glpi_tickets', string $field = 'entities_id'): array
   {
      if ($this->entities_id !== null) {
         return getEntitiesRestrictCriteria($table, $field, $this->entities_id, $this->is_recursive);
      }

      if (count($this->configured_entities)) {
         $or = [];
         foreach ($this->configured_entities as $row) {
            $entities_id = (int) $row['entities_id'];
            $recursive = (int) ($row['is_recursive'] ?? 1) === 1;
            if (!Session::haveAccessToEntity($entities_id, $recursive)) {
               continue;
            }
            $or[] = getEntitiesRestrictCriteria($table, $field, $entities_id, $recursive);
         }

         if (!count($or)) {
            return ["$table.id" => 0];
         }

         return [['OR' => $or]];
      }

      return getEntitiesRestrictCriteria($table);
   }

   public function toQueryParams(): array
   {
      return [
         'start'        => $this->start,
         'end'          => $this->end,
         'entities_id'  => $this->entities_id,
         'is_recursive' => $this->is_recursive ? 1 : 0,
         'groups_id'    => $this->groups_id,
         'users_id'     => $this->users_id,
         'itilcategories_id' => $this->itilcategories_id,
         'type'         => $this->type,
         'priority'     => $this->priority,
         'period_days'  => $this->period_days,
         'satisfaction_start' => $this->satisfaction_start,
         'satisfaction_end'   => $this->satisfaction_end,
      ];
   }

   public function cacheKeySuffix(): string
   {
      return sha1(json_encode([
         'user'         => Session::getLoginUserID(),
         'profile'      => $_SESSION['glpiactiveprofile']['id'] ?? 0,
         'start'        => $this->start,
         'end'          => $this->end,
         'entities_id'  => $this->entities_id,
         'is_recursive' => $this->is_recursive,
         'groups_id'    => $this->groups_id,
         'users_id'     => $this->users_id,
         'itilcategories_id' => $this->itilcategories_id,
         'type'         => $this->type,
         'priority'     => $this->priority,
         'period_days'  => $this->period_days,
         'satisfaction_start' => $this->satisfaction_start,
         'satisfaction_end'   => $this->satisfaction_end,
         'scope'        => $this->configured_entities,
         'entities'     => $_SESSION['glpiactiveentities_string'] ?? '',
      ]));
   }

   private static function normalizePositiveId($value): ?int
   {
      $id = (int) ($value ?? 0);
      return $id > 0 ? $id : null;
   }

   private static function normalizeTicketType($value): ?int
   {
      $type = (int) ($value ?? 0);
      if (class_exists(\Ticket::class) && in_array($type, [\Ticket::INCIDENT_TYPE, \Ticket::DEMAND_TYPE], true)) {
         return $type;
      }

      return null;
   }

   private static function normalizePriority($value): ?int
   {
      $priority = (int) ($value ?? 0);
      return $priority >= 1 && $priority <= 6 ? $priority : null;
   }

   private static function normalizeDate(string $value, string $fallback): string
   {
      if ($value === '') {
         return $fallback;
      }

      $timestamp = strtotime($value);
      if ($timestamp === false) {
         return $fallback;
      }

      return date('Y-m-d', $timestamp);
   }
}
