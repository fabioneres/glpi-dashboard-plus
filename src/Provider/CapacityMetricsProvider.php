<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

use CommonITILActor;
use CommonITILObject;
use DBConnection;
use Dropdown;
use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\DashboardContext;
use Session;
use Ticket;

class CapacityMetricsProvider
{
   public function technicianLoad(DashboardContext $context, int $limit = 20): array
   {
      if (!$this->isEnabled()) {
         return $this->wrapRows([]);
      }

      $rows = $this->loadRows($context);
      $provider = CapacityProviderResolver::resolve();
      $capacity = $provider->getCapacityByTechnician($context, array_column($rows, 'users_id'));
      $settings = Config::getSettings();
      $config = $settings['capacity_config'] ?? Config::getDefaultCapacityConfig();
      $standard_hours = $this->referenceHours($context, (int) ($config['standard_weekly_hours'] ?? 40));

      $result = [];
      foreach ($rows as $row) {
         $users_id = (int) ($row['users_id'] ?? 0);
         $cap = $capacity[$users_id] ?? null;
         $weighted = (int) ($row['weighted_load'] ?? 0);
         $capacity_hours = $cap ? $cap['hours'] : null;
         $factor = is_numeric($capacity_hours) && $standard_hours > 0
            ? max(0.1, (float) $capacity_hours / $standard_hours)
            : null;
         $index = $factor !== null ? (int) round($weighted / $factor) : $weighted;
         $classification = $this->classify($index, $config['load_thresholds'] ?? []);

         $result[] = [
            'label'  => getUserName($users_id),
            'number' => $index,
            'value'  => $classification,
            'color'  => $this->classificationColor($classification),
            'users_id' => $users_id,
            'active_tickets' => (int) ($row['active_tickets'] ?? 0),
            'critical_tickets' => (int) ($row['critical_tickets'] ?? 0),
            'sla_risk_tickets' => (int) ($row['sla_risk_tickets'] ?? 0),
            'weighted_load' => $weighted,
            'capacity_label' => $cap['label'] ?? __('Não determinada', 'dashboardplus'),
            'capacity_hours' => $capacity_hours,
            'is_scheduled' => $cap['is_scheduled'] ?? null,
            'unavailable' => $cap['unavailable'] ?? false,
            'unavailable_reason' => $cap['unavailable_reason'] ?? '',
         ];
      }

      usort($result, static function(array $left, array $right): int {
         return ((int) ($right['number'] ?? 0)) <=> ((int) ($left['number'] ?? 0));
      });

      return [
         'rows' => array_slice($result, 0, max(1, min(50, $limit))),
         'total' => array_sum(array_map(static function(array $row): int {
            return (int) ($row['number'] ?? 0);
         }, $result)),
         'source' => $provider->getSourceLabel(),
      ];
   }

   public function technicianLoadTable(DashboardContext $context): array
   {
      $data = $this->technicianLoad($context, 50);
      $rows = [];
      foreach ($data['rows'] as $row) {
         $rows[] = [
            __('Técnico', 'dashboardplus') => $row['label'],
            __('Escala', 'dashboardplus') => $row['capacity_label'],
            __('Chamados ativos', 'dashboardplus') => (string) $row['active_tickets'],
            __('Críticos', 'dashboardplus') => (string) $row['critical_tickets'],
            __('SLA em risco', 'dashboardplus') => (string) $row['sla_risk_tickets'],
            __('Carga ponderada', 'dashboardplus') => (string) $row['weighted_load'],
            __('Índice de carga', 'dashboardplus') => (string) $row['number'],
            __('Classificação', 'dashboardplus') => $row['value'],
         ];
      }

      return [
         'columns' => [
            __('Técnico', 'dashboardplus'),
            __('Escala', 'dashboardplus'),
            __('Chamados ativos', 'dashboardplus'),
            __('Críticos', 'dashboardplus'),
            __('SLA em risco', 'dashboardplus'),
            __('Carga ponderada', 'dashboardplus'),
            __('Índice de carga', 'dashboardplus'),
            __('Classificação', 'dashboardplus'),
         ],
         'rows' => $rows,
      ];
   }

   public function teamSummary(DashboardContext $context): array
   {
      $data = $this->technicianLoad($context, 50);
      $rows = $data['rows'];
      $critical = 0;
      $active = 0;
      $sla_risk = 0;
      $scheduled = 0;

      foreach ($rows as $row) {
         $active += (int) ($row['active_tickets'] ?? 0);
         $sla_risk += (int) ($row['sla_risk_tickets'] ?? 0);
         if (($row['value'] ?? '') === __('Crítica', 'dashboardplus')) {
            $critical++;
         }
         if (($row['is_scheduled'] ?? null) === true) {
            $scheduled++;
         }
      }

      return [
         'rows' => [
            ['label' => __('Técnicos com chamados', 'dashboardplus'), 'number' => count($rows), 'color' => '#2563eb'],
            ['label' => __('Técnicos com escala', 'dashboardplus'), 'number' => $scheduled, 'color' => '#16a34a'],
            ['label' => __('Chamados ativos', 'dashboardplus'), 'number' => $active, 'color' => '#f59e0b'],
            ['label' => __('Técnicos em carga crítica', 'dashboardplus'), 'number' => $critical, 'color' => '#dc2626'],
            ['label' => __('Chamados em risco de SLA', 'dashboardplus'), 'number' => $sla_risk, 'color' => '#7c3aed'],
         ],
      ];
   }

   public function alerts(DashboardContext $context): array
   {
      $data = $this->technicianLoad($context, 50);
      $rows = [];
      foreach ($data['rows'] as $row) {
         if (($row['is_scheduled'] ?? null) === false && (int) ($row['active_tickets'] ?? 0) > 0) {
            $rows[] = [
               __('Alerta', 'dashboardplus') => __('Técnico sem escala com chamados ativos', 'dashboardplus'),
               __('Técnico', 'dashboardplus') => $row['label'],
               __('Detalhe', 'dashboardplus') => $row['active_tickets'] . ' ' . __('chamados ativos', 'dashboardplus'),
            ];
         }
         if (($row['unavailable'] ?? false) && (int) ($row['active_tickets'] ?? 0) > 0) {
            $rows[] = [
               __('Alerta', 'dashboardplus') => __('Técnico indisponível com chamados ativos', 'dashboardplus'),
               __('Técnico', 'dashboardplus') => $row['label'],
               __('Detalhe', 'dashboardplus') => (string) ($row['unavailable_reason'] ?? ''),
            ];
         }
         if (($row['value'] ?? '') === __('Crítica', 'dashboardplus')) {
            $rows[] = [
               __('Alerta', 'dashboardplus') => __('Carga crítica', 'dashboardplus'),
               __('Técnico', 'dashboardplus') => $row['label'],
               __('Detalhe', 'dashboardplus') => __('Índice', 'dashboardplus') . ': ' . $row['number'],
            ];
         }
      }

      return [
         'columns' => [__('Alerta', 'dashboardplus'), __('Técnico', 'dashboardplus'), __('Detalhe', 'dashboardplus')],
         'rows' => array_slice($rows, 0, 30),
      ];
   }

   private function loadRows(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $tu = 'glpi_tickets_users';
      $gt = 'glpi_groups_tickets';
      $config = Config::getSettings()['capacity_config'] ?? Config::getDefaultCapacityConfig();
      $priority_case = $this->priorityCaseSql($config['priority_weights'] ?? []);
      $aging_case = $this->agingCaseSql($config['aging_weights'] ?? []);
      $sla_case = $this->slaCaseSql($config);
      $where = $this->activeTicketWhereSql($context, $table);
      $group_join = '';

      if ($context->getGroupsId() !== null) {
         $groups_id = (int) $context->getGroupsId();
         $group_join = " INNER JOIN `{$gt}` gt_filter ON gt_filter.tickets_id = {$table}.id AND gt_filter.type = " . (int) CommonITILActor::ASSIGN . " AND gt_filter.groups_id = {$groups_id}";
      }

      $sql = "SELECT {$tu}.users_id,
                     COUNT(DISTINCT {$table}.id) AS active_tickets,
                     SUM(CASE WHEN {$table}.priority >= 5 THEN 1 ELSE 0 END) AS critical_tickets,
                     SUM(CASE WHEN {$table}.time_to_resolve IS NOT NULL AND {$table}.time_to_resolve <= NOW() THEN 1
                              WHEN {$table}.time_to_resolve IS NOT NULL
                                   AND TIMESTAMPDIFF(SECOND, {$table}.date, NOW()) / NULLIF(TIMESTAMPDIFF(SECOND, {$table}.date, {$table}.time_to_resolve), 0) >= 0.70 THEN 1
                              ELSE 0 END) AS sla_risk_tickets,
                     SUM(({$priority_case}) + ({$aging_case}) + ({$sla_case})) AS weighted_load
              FROM `{$table}`
              INNER JOIN `{$tu}` ON {$tu}.tickets_id = {$table}.id AND {$tu}.type = " . (int) CommonITILActor::ASSIGN . "
              {$group_join}
              WHERE {$where}
              GROUP BY {$tu}.users_id
              ORDER BY weighted_load DESC, active_tickets DESC, {$tu}.users_id ASC
              LIMIT 100";

      return $this->fetchRows($sql);
   }

   private function activeTicketWhereSql(DashboardContext $context, string $table): string
   {
      $statuses = implode(',', array_map('intval', Ticket::getNotSolvedStatusArray()));
      $clauses = [
         "{$table}.is_deleted = 0",
         "{$table}.status IN ({$statuses})",
         "{$table}.date <= '" . addslashes($context->getEndDateTime()) . "'",
         $this->entityWhereSql($context, $table),
      ];

      if ($context->getUsersId() !== null) {
         $clauses[] = 'glpi_tickets_users.users_id = ' . (int) $context->getUsersId();
      }
      if ($context->getItilcategoriesId() !== null) {
         $clauses[] = "{$table}.itilcategories_id = " . (int) $context->getItilcategoriesId();
      }
      if ($context->getType() !== null) {
         $clauses[] = "{$table}.type = " . (int) $context->getType();
      }
      if ($context->getPriority() !== null) {
         $clauses[] = "{$table}.priority = " . (int) $context->getPriority();
      }

      return implode(' AND ', $clauses);
   }

   private function entityWhereSql(DashboardContext $context, string $table): string
   {
      $ids = $this->entityIds($context);
      if ($ids === null) {
         return '1 = 1';
      }
      if ($ids === []) {
         return "{$table}.entities_id IN (0)";
      }

      return "{$table}.entities_id IN (" . implode(',', array_map('intval', array_unique($ids))) . ")";
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

   private function priorityCaseSql(array $weights): string
   {
      $parts = ['CASE'];
      foreach (range(1, 6) as $priority) {
         $parts[] = 'WHEN glpi_tickets.priority = ' . $priority . ' THEN ' . max(0, (int) ($weights[$priority] ?? $priority));
      }
      $parts[] = 'ELSE 0 END';
      return implode(' ', $parts);
   }

   private function agingCaseSql(array $ranges): string
   {
      $parts = ['CASE'];
      foreach ($ranges as $range) {
         $min = max(0, (int) ($range['min'] ?? 0));
         $max = max(0, (int) ($range['max'] ?? 0));
         $weight = max(0, (int) ($range['weight'] ?? 0));
         if ($max > 0) {
            $parts[] = "WHEN DATEDIFF(NOW(), glpi_tickets.date) BETWEEN {$min} AND {$max} THEN {$weight}";
         } else {
            $parts[] = "WHEN DATEDIFF(NOW(), glpi_tickets.date) >= {$min} THEN {$weight}";
         }
      }
      $parts[] = 'ELSE 0 END';
      return implode(' ', $parts);
   }

   private function slaCaseSql(array $config): string
   {
      $weights = $config['sla_weights'] ?? [];
      $attention = max(1, min(99, (int) ($config['sla_attention_percent'] ?? 70))) / 100;
      $critical = max(1, min(100, (int) ($config['sla_critical_percent'] ?? 90))) / 100;
      $violated = max(0, (int) ($weights['violated'] ?? 5));
      $critical_weight = max(0, (int) ($weights['critical'] ?? 3));
      $attention_weight = max(0, (int) ($weights['attention'] ?? 1));

      return "CASE
         WHEN glpi_tickets.time_to_resolve IS NULL THEN 0
         WHEN glpi_tickets.time_to_resolve <= NOW() THEN {$violated}
         WHEN TIMESTAMPDIFF(SECOND, glpi_tickets.date, NOW()) / NULLIF(TIMESTAMPDIFF(SECOND, glpi_tickets.date, glpi_tickets.time_to_resolve), 0) >= {$critical} THEN {$critical_weight}
         WHEN TIMESTAMPDIFF(SECOND, glpi_tickets.date, NOW()) / NULLIF(TIMESTAMPDIFF(SECOND, glpi_tickets.date, glpi_tickets.time_to_resolve), 0) >= {$attention} THEN {$attention_weight}
         ELSE 0 END";
   }

   private function classify(int $index, array $thresholds): string
   {
      if ($index <= (int) ($thresholds['low'] ?? 25)) {
         return __('Baixa', 'dashboardplus');
      }
      if ($index <= (int) ($thresholds['moderate'] ?? 50)) {
         return __('Moderada', 'dashboardplus');
      }
      if ($index <= (int) ($thresholds['high'] ?? 75)) {
         return __('Alta', 'dashboardplus');
      }
      return __('Crítica', 'dashboardplus');
   }

   private function classificationColor(string $classification): string
   {
      if ($classification === __('Crítica', 'dashboardplus')) {
         return '#dc2626';
      }
      if ($classification === __('Alta', 'dashboardplus')) {
         return '#f59e0b';
      }
      if ($classification === __('Moderada', 'dashboardplus')) {
         return '#2563eb';
      }
      return '#16a34a';
   }

   private function referenceHours(DashboardContext $context, int $weekly_hours): float
   {
      $days = max(1, min(366, (int) floor((strtotime($context->getEnd()) - strtotime($context->getStart())) / 86400) + 1));
      return max(1.0, ($weekly_hours / 7) * $days);
   }

   private function isEnabled(): bool
   {
      return (int) (Config::getSettings()['capacity_enabled'] ?? 1) === 1;
   }

   private function wrapRows(array $rows): array
   {
      return [
         'rows' => $rows,
         'total' => array_sum(array_map(static function(array $row): int {
            return (int) ($row['number'] ?? 0);
         }, $rows)),
      ];
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
