<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

use DBConnection;
use Dropdown;
use GlpiPlugin\Dashboardplus\Config;
use GlpiPlugin\Dashboardplus\DashboardContext;
use Session;

class DistributionMetricsProvider
{
   private const LOG_TABLE = 'glpi_plugin_atribuicaointeligente_distribution_logs';

   private const ACTION_TECHNICIAN_ASSIGNED = 'technician_assigned';
   private const ACTION_ENTITY_TRANSFERRED  = 'entity_transferred';

   private const SOURCE_MANUAL = 'manual';
   private const SOURCE_PLUGIN = 'plugin';

   public function isAvailable(): bool
   {
      return $this->getReadDB()->tableExists(self::LOG_TABLE);
   }

   public function distinctTickets(DashboardContext $context): array
   {
      return $this->metric($this->countDistinctTickets($context), '#2563eb');
   }

   public function automationRate(DashboardContext $context): array
   {
      $summary = $this->actuationSummary($context);
      $automated = $summary['plugin_only']['number'] + $summary['plugin_human']['number'];
      $total = max(0, $summary['total']);
      $rate = $total > 0 ? round(($automated / $total) * 100, 1) : 0.0;

      return [
         'number' => (int) round($rate),
         'value'  => $this->formatPercent($rate),
         'color'  => $rate >= 75 ? '#16a34a' : ($rate >= 45 ? '#d97706' : '#dc2626'),
      ];
   }

   public function automationIntegralTickets(DashboardContext $context): array
   {
      $summary = $this->actuationSummary($context);
      return $this->metric($summary['plugin_only']['number'], '#16a34a');
   }

   public function automationPartialTickets(DashboardContext $context): array
   {
      $summary = $this->actuationSummary($context);
      return $this->metric($summary['plugin_human']['number'], '#d97706');
   }

   public function manualTickets(DashboardContext $context): array
   {
      $summary = $this->actuationSummary($context);
      return $this->metric($summary['human_only']['number'], '#dc2626');
   }

   public function transferTickets(DashboardContext $context): array
   {
      return $this->metric($this->countDistinctTickets($context, [
         "action_type = '" . self::ACTION_ENTITY_TRANSFERRED . "'",
      ]), '#7c3aed');
   }

   public function summaryByDistributor(DashboardContext $context, int $limit = 50): array
   {
      if (!$this->isAvailable()) {
         return $this->wrapRows([]);
      }

      $where = $this->baseWhereSql($context);
      $limit = $this->normalizeLimit($limit);
      $rows = [];

      foreach ($this->fetchRows(
         "SELECT users_id_actor,
                 COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_ENTITY_TRANSFERRED . "' THEN tickets_id ELSE NULL END) AS transfer_tickets,
                 COUNT(DISTINCT tickets_id) AS tickets_count
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
          GROUP BY users_id_actor
          ORDER BY tickets_count DESC, transfer_tickets DESC, users_id_actor ASC
          LIMIT {$limit}"
      ) as $row) {
         $tickets = (int) ($row['tickets_count'] ?? 0);
         $transfers = (int) ($row['transfer_tickets'] ?? 0);
         $rows[] = [
            'label'  => $this->userLabel((int) ($row['users_id_actor'] ?? 0), __('Sistema', 'dashboardplus')),
            'number' => $tickets,
            'value'  => number_format($tickets, 0, ',', '.') . ' / ' . number_format($transfers, 0, ',', '.'),
            'color'  => '#2563eb',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function topDistributors(DashboardContext $context, int $limit = 50): array
   {
      if (!$this->isAvailable()) {
         return $this->wrapRows([]);
      }

      $where = $this->baseWhereSql($context);
      $limit = $this->normalizeLimit($limit);
      $rows = [];

      foreach ($this->fetchRows(
         "SELECT users_id_actor, COUNT(DISTINCT tickets_id) AS tickets_count
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
          GROUP BY users_id_actor
          ORDER BY tickets_count DESC, users_id_actor ASC
          LIMIT {$limit}"
      ) as $row) {
         $rows[] = [
            'label'  => $this->userLabel((int) ($row['users_id_actor'] ?? 0), __('Sistema', 'dashboardplus')),
            'number' => (int) ($row['tickets_count'] ?? 0),
            'color'  => '#0891b2',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function byCategory(DashboardContext $context, int $limit = 50): array
   {
      if (!$this->isAvailable()) {
         return $this->wrapRows([]);
      }

      $where = $this->baseWhereSql($context);
      $limit = $this->normalizeLimit($limit);
      $rows = [];

      foreach ($this->fetchRows(
         "SELECT COALESCE(itilcategories_id, 0) AS itilcategories_id,
                 COUNT(DISTINCT tickets_id) AS tickets_count
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
          GROUP BY COALESCE(itilcategories_id, 0)
          ORDER BY tickets_count DESC, itilcategories_id ASC
          LIMIT {$limit}"
      ) as $row) {
         $category_id = (int) ($row['itilcategories_id'] ?? 0);
         $rows[] = [
            'label'  => $this->dropdownLabel('glpi_itilcategories', $category_id, __('Sem categoria', 'dashboardplus')),
            'number' => (int) ($row['tickets_count'] ?? 0),
            'color'  => '#16a34a',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function evolution(DashboardContext $context): array
   {
      if (!$this->isAvailable()) {
         return $this->wrapRows([]);
      }

      $days = max(1, (int) floor((strtotime($context->getEnd()) - strtotime($context->getStart())) / 86400) + 1);
      $period = $days > 370 ? 'year' : ($days > 90 ? 'month' : 'day');
      $period_sql = "DATE(date_creation)";
      if ($period === 'month') {
         $period_sql = "DATE_FORMAT(date_creation, '%Y-%m')";
      } elseif ($period === 'year') {
         $period_sql = "DATE_FORMAT(date_creation, '%Y')";
      }

      $where = $this->baseWhereSql($context);
      $rows = [];

      foreach ($this->fetchRows(
         "SELECT {$period_sql} AS distribution_period,
                 COUNT(DISTINCT tickets_id) AS tickets_count
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
          GROUP BY {$period_sql}
          ORDER BY distribution_period ASC
          LIMIT 120"
      ) as $row) {
         $label = (string) ($row['distribution_period'] ?? '');
         if ($period === 'day' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $label)) {
            $label = date('d/m', strtotime($label));
         } elseif ($period === 'month' && preg_match('/^\d{4}-\d{2}$/', $label)) {
            $label = date('m/Y', strtotime($label . '-01'));
         }
         $rows[] = [
            'label'  => $label,
            'number' => (int) ($row['tickets_count'] ?? 0),
            'color'  => '#2563eb',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function actuation(DashboardContext $context): array
   {
      $summary = $this->actuationSummary($context);

      return $this->wrapRows([
         $summary['plugin_only'],
         $summary['plugin_human'],
         $summary['human_only'],
      ]);
   }

   public function topTechnicians(DashboardContext $context, int $limit = 50): array
   {
      if (!$this->isAvailable()) {
         return $this->wrapRows([]);
      }

      $where = $this->baseWhereSql($context);
      $limit = $this->normalizeLimit($limit);
      $rows = [];

      foreach ($this->fetchRows(
         "SELECT users_id_to, COUNT(DISTINCT tickets_id) AS tickets_count
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
            AND action_type = '" . self::ACTION_TECHNICIAN_ASSIGNED . "'
            AND users_id_to IS NOT NULL
            AND users_id_to > 0
          GROUP BY users_id_to
          ORDER BY tickets_count DESC, users_id_to ASC
          LIMIT {$limit}"
      ) as $row) {
         $rows[] = [
            'label'  => $this->userLabel((int) ($row['users_id_to'] ?? 0), __('Sem técnico', 'dashboardplus')),
            'number' => (int) ($row['tickets_count'] ?? 0),
            'color'  => '#d97706',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function transfersByEntity(DashboardContext $context, int $limit = 50): array
   {
      if (!$this->isAvailable()) {
         return $this->wrapRows([]);
      }

      $where = $this->baseWhereSql($context);
      $limit = $this->normalizeLimit($limit);
      $rows = [];

      foreach ($this->fetchRows(
         "SELECT entities_id_from, entities_id_to, COUNT(DISTINCT tickets_id) AS tickets_count
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
            AND action_type = '" . self::ACTION_ENTITY_TRANSFERRED . "'
          GROUP BY entities_id_from, entities_id_to
          ORDER BY tickets_count DESC, entities_id_from ASC, entities_id_to ASC
          LIMIT {$limit}"
      ) as $row) {
         $from = $this->dropdownLabel('glpi_entities', (int) ($row['entities_id_from'] ?? 0), __('Origem não informada', 'dashboardplus'));
         $to = $this->dropdownLabel('glpi_entities', (int) ($row['entities_id_to'] ?? 0), __('Destino não informado', 'dashboardplus'));
         $rows[] = [
            'label'  => $from . ' -> ' . $to,
            'number' => (int) ($row['tickets_count'] ?? 0),
            'color'  => '#7c3aed',
         ];
      }

      return $this->wrapRows($rows);
   }

   private function actuationSummary(DashboardContext $context): array
   {
      $rows = [
         'plugin_only' => [
            'label'  => __('Automação integral', 'dashboardplus'),
            'number' => 0,
            'color'  => '#16a34a',
         ],
         'plugin_human' => [
            'label'  => __('Automação parcial', 'dashboardplus'),
            'number' => 0,
            'color'  => '#d97706',
         ],
         'human_only' => [
            'label'  => __('Atuação manual', 'dashboardplus'),
            'number' => 0,
            'color'  => '#dc2626',
         ],
      ];

      if (!$this->isAvailable()) {
         return ['total' => 0] + $rows;
      }

      $where = $this->baseWhereSql($context);
      foreach ($this->fetchRows(
         "SELECT classified.classification,
                 COUNT(*) AS tickets_count
          FROM (
             SELECT ticket_summary.tickets_id,
                    CASE
                       WHEN ticket_summary.plugin_events > 0 AND ticket_summary.manual_events = 0 THEN 'plugin_only'
                       WHEN ticket_summary.plugin_events > 0 AND ticket_summary.manual_events > 0 THEN 'plugin_human'
                       ELSE 'human_only'
                    END AS classification
             FROM (
                SELECT tickets_id,
                       SUM(CASE WHEN source = '" . self::SOURCE_PLUGIN . "' THEN 1 ELSE 0 END) AS plugin_events,
                       SUM(CASE WHEN source = '" . self::SOURCE_MANUAL . "' THEN 1 ELSE 0 END) AS manual_events
                FROM `" . self::LOG_TABLE . "`
                WHERE {$where}
                  AND tickets_id > 0
                GROUP BY tickets_id
             ) ticket_summary
          ) classified
          GROUP BY classified.classification"
      ) as $row) {
         $classification = (string) ($row['classification'] ?? '');
         if (isset($rows[$classification])) {
            $rows[$classification]['number'] = (int) ($row['tickets_count'] ?? 0);
         }
      }

      $total = array_sum(array_map(static function(array $row): int {
         return (int) ($row['number'] ?? 0);
      }, $rows));

      return ['total' => $total] + $rows;
   }

   private function countDistinctTickets(DashboardContext $context, array $extra_where = []): int
   {
      if (!$this->isAvailable()) {
         return 0;
      }

      $where = $this->baseWhereSql($context);
      if ($extra_where !== []) {
         $where .= ' AND ' . implode(' AND ', $extra_where);
      }

      $row = $this->fetchRows(
         "SELECT COUNT(DISTINCT tickets_id) AS total
          FROM `" . self::LOG_TABLE . "`
          WHERE {$where}
            AND tickets_id > 0"
      )[0] ?? [];

      return (int) ($row['total'] ?? 0);
   }

   private function baseWhereSql(DashboardContext $context): string
   {
      $clauses = [
         "date_creation >= '" . addslashes($context->getStartDateTime()) . "'",
         "date_creation <= '" . addslashes($context->getEndDateTime()) . "'",
         $this->entityWhereSql($context),
      ];

      if ($context->getItilcategoriesId() !== null) {
         $clauses[] = 'itilcategories_id = ' . (int) $context->getItilcategoriesId();
      }

      if ($context->getUsersId() !== null) {
         $users_id = (int) $context->getUsersId();
         $clauses[] = "(users_id_actor = {$users_id} OR users_id_from = {$users_id} OR users_id_to = {$users_id})";
      }

      if ($context->getGroupsId() !== null) {
         $groups_id = (int) $context->getGroupsId();
         $clauses[] = "(groups_id_from = {$groups_id} OR groups_id_to = {$groups_id})";
      }

      return implode(' AND ', array_filter($clauses));
   }

   private function entityWhereSql(DashboardContext $context): string
   {
      $ids = $this->entityIds($context);
      if ($ids === null) {
         return '1 = 1';
      }

      if ($ids === []) {
         return 'entities_id IN (0)';
      }

      $list = implode(',', array_map('intval', array_values(array_unique($ids))));
      return "(entities_id IN ({$list}) OR entities_id_from IN ({$list}) OR entities_id_to IN ({$list}))";
   }

   private function entityIds(DashboardContext $context): ?array
   {
      if ($context->getEntitiesId() !== null) {
         return $this->expandEntity((int) $context->getEntitiesId(), $context->isRecursive());
      }

      $configured = Config::getConfiguredEntityRows();
      if ($configured !== []) {
         $ids = [];
         foreach ($configured as $row) {
            $entities_id = (int) ($row['entities_id'] ?? 0);
            $recursive = (int) ($row['is_recursive'] ?? 1) === 1;
            if (!Session::haveAccessToEntity($entities_id, $recursive)) {
               continue;
            }
            $ids = array_merge($ids, $this->expandEntity($entities_id, $recursive));
         }
         return $ids;
      }

      if (Session::canViewAllEntities()) {
         return null;
      }

      $ids = array_map('intval', $_SESSION['glpiactiveentities'] ?? []);
      $ids[] = 0;
      return $ids;
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

   private function userLabel(int $users_id, string $empty_label): string
   {
      if ($users_id <= 0) {
         return $empty_label;
      }

      $label = getUserName($users_id);
      return $label !== '' ? $label : $empty_label;
   }

   private function dropdownLabel(string $table, int $id, string $empty_label): string
   {
      if ($id <= 0) {
         return $empty_label;
      }

      $label = Dropdown::getDropdownName($table, $id);
      return $label !== '' ? $label : $empty_label;
   }

   private function metric(int $number, string $color): array
   {
      return [
         'number' => $number,
         'color'  => $color,
      ];
   }

   private function wrapRows(array $rows): array
   {
      return [
         'rows'  => $rows,
         'total' => array_sum(array_map(static function(array $row): int {
            return (int) ($row['number'] ?? 0);
         }, $rows)),
      ];
   }

   private function normalizeLimit(int $limit): int
   {
      return max(1, min(50, $limit));
   }

   private function formatPercent(float $percent): string
   {
      $decimals = abs($percent - round($percent)) < 0.01 ? 0 : 1;
      return number_format($percent, $decimals, ',', '.') . '%';
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
