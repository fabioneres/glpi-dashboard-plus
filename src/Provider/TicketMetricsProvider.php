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
use GlpiPlugin\Dashboardplus\DashboardContext;
use QueryExpression;
use Ticket;
use Toolbox;

class TicketMetricsProvider
{
   private const DATE_OPEN   = 'date';
   private const DATE_SOLVED = 'solvedate';
   private const DATE_CLOSED = 'closedate';

   public function countByStatuses(DashboardContext $context, array $statuses): array
   {
      $where = $this->getBaseWhere($context, self::DATE_OPEN);
      if ($statuses !== []) {
         $where[Ticket::getTable() . '.status'] = $statuses;
      }

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => Ticket::getTable() . '.id AS total',
         ],
         'FROM'  => Ticket::getTable(),
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      $result = [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrl($context, $statuses),
      ];

      if (count($statuses) === 1) {
         $result += $this->getStatusMetadata((int) reset($statuses));
      }

      return $result;
   }

   public function countCreated(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $this->getBaseWhere($context, self::DATE_OPEN),
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrl($context, [], '', [], self::DATE_OPEN),
         'color'  => '#2563eb',
      ];
   }

   public function countSolved(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context, self::DATE_SOLVED);
      $where["$table.status"] = array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray());
      $where[] = new QueryExpression("$table.solvedate IS NOT NULL");

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrl($context, [], '', [], self::DATE_SOLVED),
      ] + $this->getStatusMetadata(Ticket::SOLVED);
   }

   public function countClosed(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context, self::DATE_CLOSED);
      $where["$table.status"] = Ticket::getClosedStatusArray();
      $where[] = new QueryExpression("$table.closedate IS NOT NULL");

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrl($context, [], '', [], self::DATE_CLOSED),
      ] + $this->getStatusMetadata(Ticket::CLOSED);
   }

   public function countLate(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context);
      $where["$table.status"] = Ticket::getNotSolvedStatusArray();
      $where[] = [
         'OR' => [
            CommonITILObject::generateSLAOLAComputation('time_to_resolve', $table),
            CommonITILObject::generateSLAOLAComputation('internal_time_to_resolve', $table),
            CommonITILObject::generateSLAOLAComputation('time_to_own', $table),
            CommonITILObject::generateSLAOLAComputation('internal_time_to_own', $table),
         ],
      ];

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrl($context, [], 'late'),
         'color'  => '#d63939',
      ];
   }

   public function countUnassigned(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $ticket_user_table = 'glpi_tickets_users';
      $group_ticket_table = 'glpi_groups_tickets';
      $where = $this->getBaseWhere($context);
      $where["$table.status"] = Ticket::getNotSolvedStatusArray();
      $where[] = new QueryExpression("$ticket_user_table.id IS NULL");
      $where[] = new QueryExpression("$group_ticket_table.id IS NULL");

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $ticket_user_table => [
               'ON' => [
                  $ticket_user_table => 'tickets_id',
                  $table             => 'id',
                  [
                     'AND' => [
                        "$ticket_user_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ],
            $group_ticket_table => [
               'ON' => [
                  $group_ticket_table => 'tickets_id',
                  $table              => 'id',
                  [
                     'AND' => [
                        "$group_ticket_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ],
         ],
         'WHERE' => $where,
      ], $context, true, true);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrl($context, Ticket::getNotSolvedStatusArray()),
         'color'  => '#f59e0b',
      ];
   }

   public function countSolvedToday(DashboardContext $context): array
   {
      $today = date('Y-m-d');
      $table = Ticket::getTable();
      $where = $this->getBaseWhereForPeriod($context, self::DATE_SOLVED, $today, $today);
      $where["$table.status"] = array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray());
      $where[] = new QueryExpression("$table.solvedate IS NOT NULL");

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => $this->ticketSearchUrlForPeriod($context, $today, $today, [], '', [], self::DATE_SOLVED),
         'color'  => '#2563eb',
      ];
   }

   public function countNotificationQueue(DashboardContext $context): array
   {
      $table = 'glpi_queuednotifications';
      $db = $this->getReadDB();
      if (!method_exists($db, 'tableExists') || !$db->tableExists($table)) {
         return [
            'number' => 0,
            'color'  => '#16a34a',
         ];
      }

      $where = [];
      foreach (['sent_time', 'sent_try', 'is_deleted'] as $field) {
         if ($this->fieldExists($table, $field)) {
            if ($field === 'sent_time') {
               $where[] = new QueryExpression("$table.sent_time IS NULL");
            } elseif ($field === 'is_deleted') {
               $where["$table.is_deleted"] = 0;
            }
         }
      }

      $criteria = [
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
      ];
      if ($where !== []) {
         $criteria['WHERE'] = $where;
      }

      $row = $db->request($criteria)->current();
      $total = (int) ($row['total'] ?? 0);

      return [
         'number' => $total,
         'color'  => $total > 0 ? '#d97706' : '#16a34a',
      ];
   }

   public function countByPriority(DashboardContext $context, int $priority): array
   {
      return $this->countByPriorities($context, [$priority]);
   }

   public function countByPriorities(DashboardContext $context, array $priorities): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context);
      $priorities = array_values(array_filter(array_map('intval', $priorities), static function(int $priority): bool {
         return $priority >= 1 && $priority <= 6;
      }));
      if ($priorities === []) {
         $priorities = [3];
      }
      $where["$table.priority"] = $priorities;

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'url'    => count($priorities) === 1 ? $this->ticketSearchUrl($context, [], '', [
            [
               'field'      => 3,
               'searchtype' => 'equals',
               'value'      => reset($priorities),
            ],
         ]) : '',
         'color'  => $this->getPriorityColor(max($priorities)),
      ];
   }

   public function receivedByDay(DashboardContext $context, int $limit = 31): array
   {
      return $this->dailyTicketCounts($context, self::DATE_OPEN, $limit);
   }

   public function solvedClosedByDay(DashboardContext $context, int $limit = 31): array
   {
      $solved = $this->dailyCounts($context, self::DATE_SOLVED, $limit, true);
      $closed = $this->dailyCounts($context, self::DATE_CLOSED, $limit, true);
      $range = $this->getLimitedDateRange($context, $limit);
      $rows = [];

      foreach ($this->getDayBuckets($range['start'], $range['end']) as $bucket) {
         $solved_count = (int) ($solved[$bucket['key']] ?? 0);
         $closed_count = (int) ($closed[$bucket['key']] ?? 0);
         $total = $solved_count + $closed_count;
         $rows[] = [
            'label'  => $bucket['label'],
            'number' => $total,
            'value'  => sprintf(
               __('%1$s sol. / %2$s enc.', 'dashboardplus'),
               number_format($solved_count, 0, ',', '.'),
               number_format($closed_count, 0, ',', '.')
            ),
            'color'  => '#2563eb',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function openByDay(DashboardContext $context, int $limit = 30): array
   {
      $table = Ticket::getTable();
      $range = $this->getLimitedDateRange($context, $limit);
      $created_where = $this->getBaseWhereForPeriod($context, self::DATE_OPEN, $range['start'], $range['end']);

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("DATE($table.date) AS period_key"),
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => array_merge($created_where, [
            "$table.status" => Ticket::getNotSolvedStatusArray(),
         ]),
         'GROUPBY' => [new QueryExpression("DATE($table.date)")],
         'ORDER'   => [new QueryExpression('period_key ASC')],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $counts[(string) $row['period_key']] = (int) $row['total'];
      }

      $rows = [];
      foreach ($this->getDayBuckets($range['start'], $range['end']) as $bucket) {
         $rows[] = [
            'label'  => $bucket['label'],
            'number' => (int) ($counts[$bucket['key']] ?? 0),
            'url'    => $this->ticketSearchUrlForPeriod($context, $bucket['key'], $bucket['key'], Ticket::getNotSolvedStatusArray()),
            'color'  => '#16a34a',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByLocation(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $location_table = 'glpi_locations';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.locations_id AS item_key",
            "$location_table.completename AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $location_table => [
               'ON' => [
                  $location_table => 'id',
                  $table          => 'locations_id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.locations_id", "$location_table.completename"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = [
            'label'  => (string) ($row['label'] ?: __('Sem localização', 'dashboardplus')),
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 83,
                  'searchtype' => 'equals',
                  'value'      => (int) ($row['item_key'] ?? 0),
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByStatus(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.status AS item_key",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'    => $table,
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.status"],
         'ORDER'   => ["$table.status ASC"],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $status = (int) $row['item_key'];
         $counts[$status] = (int) $row['total'];
      }

      $rows = [];
      foreach (Ticket::getAllStatusArray() as $status => $label) {
         $status = (int) $status;
         $rows[] = [
            'label'  => (string) $label,
            'number' => (int) ($counts[$status] ?? 0),
            'url'    => $this->ticketSearchUrl($context, [$status]),
         ] + $this->getStatusMetadata($status);
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByPriority(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.priority AS item_key",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'    => $table,
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.priority"],
         'ORDER'   => ['total DESC'],
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $priority = (int) $row['item_key'];
         $rows[] = [
            'label'  => Ticket::getPriorityName($priority),
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 3,
                  'searchtype' => 'equals',
                  'value'      => $priority,
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByCategory(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $category_table = 'glpi_itilcategories';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.itilcategories_id AS item_key",
            "$category_table.completename AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $category_table => [
               'ON' => [
                  $category_table => 'id',
                  $table          => 'itilcategories_id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.itilcategories_id", "$category_table.completename"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $category_id = (int) ($row['item_key'] ?? 0);
         $rows[] = [
            'label'  => (string) ($row['label'] ?: __('Sem categoria', 'dashboardplus')),
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 7,
                  'searchtype' => 'equals',
                  'value'      => $category_id,
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByGroup(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $group_ticket_table = 'glpi_groups_tickets';
      $group_table = 'glpi_groups';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$group_table.id AS item_key",
            "$group_table.completename AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $group_ticket_table => [
               'ON' => [
                  $group_ticket_table => 'tickets_id',
                  $table              => 'id',
                  [
                     'AND' => [
                        "$group_ticket_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ],
            $group_table => [
               'ON' => [
                  $group_table        => 'id',
                  $group_ticket_table => 'groups_id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$group_table.id", "$group_table.completename"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context, true);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = [
            'label'  => (string) ($row['label'] ?: __('Sem grupo', 'dashboardplus')),
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 8,
                  'searchtype' => 'equals',
                  'value'      => (int) $row['item_key'],
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByTechnician(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $ticket_user_table = 'glpi_tickets_users';
      $user_table = 'glpi_users';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$user_table.id AS item_key",
            "$user_table.firstname AS firstname",
            "$user_table.realname AS realname",
            "$user_table.name AS login",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $ticket_user_table => [
               'ON' => [
                  $ticket_user_table => 'tickets_id',
                  $table             => 'id',
                  [
                     'AND' => [
                        "$ticket_user_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ],
            $user_table => [
               'ON' => [
                  $user_table        => 'id',
                  $ticket_user_table => 'users_id',
               ],
            ],
         ],
         'WHERE'   => array_merge($this->getBaseWhere($context), ["$user_table.is_deleted" => 0]),
         'GROUPBY' => ["$user_table.id", "$user_table.firstname", "$user_table.realname", "$user_table.name"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context, false, true);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $label = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['realname'] ?? ''));
         if ($label === '') {
            $label = (string) ($row['login'] ?? __('Desconhecido', 'dashboardplus'));
         }
         $rows[] = [
            'label'  => $label,
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 5,
                  'searchtype' => 'equals',
                  'value'      => (int) $row['item_key'],
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByType(DashboardContext $context): array
   {
      $table = Ticket::getTable();

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.type AS item_key",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'    => $table,
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.type"],
         'ORDER'   => ['total DESC'],
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $type = (int) ($row['item_key'] ?? 0);
         $rows[] = [
            'label'  => Ticket::getTicketTypeName($type),
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 14,
                  'searchtype' => 'equals',
                  'value'      => $type,
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByEntity(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $entity_table = 'glpi_entities';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$entity_table.id AS item_key",
            "$entity_table.completename AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $entity_table => [
               'ON' => [
                  $entity_table => 'id',
                  $table        => 'entities_id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$entity_table.id", "$entity_table.completename"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = [
            'label'  => (string) ($row['label'] ?: __('Sem entidade', 'dashboardplus')),
            'number' => (int) $row['total'],
            'url'    => $this->ticketSearchUrl($context, [], '', [
               [
                  'field'      => 80,
                  'searchtype' => 'equals',
                  'value'      => (int) $row['item_key'],
               ],
            ]),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function breakdownByRequestType(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $request_type_table = 'glpi_requesttypes';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.requesttypes_id AS item_key",
            "$request_type_table.name AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $request_type_table => [
               'ON' => [
                  $request_type_table => 'id',
                  $table              => 'requesttypes_id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.requesttypes_id", "$request_type_table.name"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = [
            'label'  => (string) ($row['label'] ?: __('Sem origem', 'dashboardplus')),
            'number' => (int) $row['total'],
         ];
      }

      return $this->wrapRows($rows);
   }

   public function satisfactionBreakdown(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$satisfaction_table.satisfaction AS item_key",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE'   => array_merge(
            $this->getBaseWhere($context),
            $this->getAnsweredSatisfactionWhere($context, $satisfaction_table)
         ),
         'GROUPBY' => ["$satisfaction_table.satisfaction"],
         'ORDER'   => ["$satisfaction_table.satisfaction ASC"],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $counts[(int) $row['item_key']] = (int) $row['total'];
      }

      $labels = [
         1 => __('Muito insatisfeito', 'dashboardplus'),
         2 => __('Insatisfeito', 'dashboardplus'),
         3 => __('Regular', 'dashboardplus'),
         4 => __('Satisfeito', 'dashboardplus'),
         5 => __('Muito satisfeito', 'dashboardplus'),
      ];
      $colors = [
         1 => '#dc2626',
         2 => '#d97706',
         3 => '#f59e0b',
         4 => '#16a34a',
         5 => '#2563eb',
      ];

      $rows = [];
      foreach ($labels as $score => $label) {
         $rows[] = [
            'label'  => $label,
            'number' => (int) ($counts[$score] ?? 0),
            'color'  => $colors[$score],
         ];
      }

      return $this->wrapRows($rows);
   }

   public function satisfactionGeneralBreakdown(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $date_condition = $this->getSatisfactionDateCondition($context, $satisfaction_table);

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression(
               "CASE
                  WHEN $satisfaction_table.satisfaction IS NULL
                     OR $satisfaction_table.satisfaction = 0
                     OR NOT ($date_condition) THEN 0
                  ELSE $satisfaction_table.satisfaction
               END AS item_key"
            ),
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ['item_key'],
         'ORDER'   => ['item_key ASC'],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $counts[(int) $row['item_key']] = (int) $row['total'];
      }

      $labels = $this->getSatisfactionLabels(true);
      $colors = $this->getSatisfactionColors(true);
      $rows = [];
      foreach ($labels as $score => $label) {
         $rows[] = [
            'label'  => $label,
            'number' => (int) ($counts[$score] ?? 0),
            'color'  => $colors[$score],
         ];
      }

      return $this->wrapRows($rows);
   }

   public function satisfactionAverage(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("AVG($satisfaction_table.satisfaction) AS average_score"),
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE' => array_merge(
            $this->getBaseWhere($context),
            $this->getAnsweredSatisfactionWhere($context, $satisfaction_table)
         ),
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();
      $average = round((float) ($row['average_score'] ?? 0), 2);

      return [
         'number' => (int) round($average * 100),
         'value'  => number_format($average, 2, ',', '.') . ' / 5',
         'color'  => $average >= 4 ? '#16a34a' : ($average >= 3 ? '#d97706' : '#dc2626'),
      ];
   }

   public function satisfactionAnsweredCount(DashboardContext $context): array
   {
      $row = $this->getSatisfactionTotals($context);

      return [
         'number' => (int) ($row['answered'] ?? 0),
         'color'  => '#16a34a',
      ];
   }

   public function satisfactionResponseRate(DashboardContext $context): array
   {
      $row = $this->getSatisfactionTotals($context);
      $total = max(0, (int) ($row['total'] ?? 0));
      $answered = max(0, (int) ($row['answered'] ?? 0));
      $rate = $total > 0 ? round(($answered / $total) * 100, 2) : 0;

      return [
         'number' => (int) round($rate),
         'value'  => number_format($rate, 2, ',', '.') . '%',
         'color'  => $rate >= 90 ? '#16a34a' : ($rate >= 75 ? '#d97706' : '#dc2626'),
      ];
   }

   public function satisfactionByGroupAverage(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $group_ticket_table = 'glpi_groups_tickets';
      $group_table = 'glpi_groups';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$group_table.completename AS group_name",
            new QueryExpression("ROUND(AVG($satisfaction_table.satisfaction), 2) AS average_score"),
            'COUNT DISTINCT' => "$table.id AS answered",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
            $group_ticket_table => [
               'ON' => [
                  $group_ticket_table => 'tickets_id',
                  $table              => 'id',
                  [
                     'AND' => [
                        "$group_ticket_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ],
            $group_table => [
               'ON' => [
                  $group_table        => 'id',
                  $group_ticket_table => 'groups_id',
               ],
            ],
         ],
         'WHERE' => array_merge(
            $this->getBaseWhere($context),
            $this->getAnsweredSatisfactionWhere($context, $satisfaction_table)
         ),
         'GROUPBY' => ["$group_table.id", "$group_table.completename"],
         'ORDER'   => ['average_score DESC', 'answered DESC'],
         'LIMIT'   => $limit,
      ], $context, true);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $average = (float) ($row['average_score'] ?? 0);
         $rows[] = [
            'label'  => (string) ($row['group_name'] ?: __('Sem grupo', 'dashboardplus')),
            'number' => (int) round($average * 100),
            'value'  => number_format($average, 2, ',', '.'),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function satisfactionByCategorySummary(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $category_table = 'glpi_itilcategories';
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $date_condition = $this->getSatisfactionDateCondition($context, $satisfaction_table);

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$category_table.completename AS category_name",
            'COUNT DISTINCT' => "$table.id AS total_tickets",
            new QueryExpression("COUNT(DISTINCT CASE WHEN $satisfaction_table.satisfaction > 0 AND $date_condition THEN $table.id END) AS answered"),
            new QueryExpression("ROUND(AVG(CASE WHEN $satisfaction_table.satisfaction > 0 AND $date_condition THEN $satisfaction_table.satisfaction END), 2) AS average_score"),
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $category_table => [
               'ON' => [
                  $category_table => 'id',
                  $table          => 'itilcategories_id',
               ],
            ],
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$table.itilcategories_id", "$category_table.completename"],
         'ORDER'   => ['total_tickets DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $average = $row['average_score'] !== null ? (float) $row['average_score'] : 0;
         $rows[] = [
            'Categoria'             => (string) ($row['category_name'] ?: __('Sem categoria', 'dashboardplus')),
            'Total de chamados'     => number_format((int) $row['total_tickets'], 0, ',', '.'),
            'Pesquisas respondidas' => number_format((int) $row['answered'], 0, ',', '.'),
            'Média de satisfação'   => $average > 0 ? number_format($average, 2, ',', '.') : '-',
         ];
      }

      return [
         'columns' => ['Categoria', 'Total de chamados', 'Pesquisas respondidas', 'Média de satisfação'],
         'rows'    => $rows,
      ];
   }

   public function satisfactionComments(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $user_table = 'glpi_users';

      $requester_sql = "(SELECT COALESCE(NULLIF(TRIM(CONCAT(u.firstname, ' ', u.realname)), ''), u.name)
         FROM glpi_tickets_users tu
         INNER JOIN $user_table u ON u.id = tu.users_id
         WHERE tu.tickets_id = $table.id AND tu.type = 1
         ORDER BY tu.id DESC
         LIMIT 1)";
      $technician_sql = "(SELECT COALESCE(NULLIF(TRIM(CONCAT(u.firstname, ' ', u.realname)), ''), u.name)
         FROM glpi_tickets_users tu
         INNER JOIN $user_table u ON u.id = tu.users_id
         WHERE tu.tickets_id = $table.id AND tu.type = " . (int) CommonITILActor::ASSIGN . "
         ORDER BY tu.id DESC
         LIMIT 1)";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$table.id AS ticket_id",
            new QueryExpression("$technician_sql AS technician_name"),
            new QueryExpression("$requester_sql AS requester_name"),
            "$satisfaction_table.satisfaction AS satisfaction_score",
            "$satisfaction_table.comment AS satisfaction_comment",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE' => array_merge(
            $this->getBaseWhere($context),
            $this->getAnsweredSatisfactionWhere($context, $satisfaction_table)
         ),
         'ORDER' => ["$satisfaction_table.date_answered DESC", "$table.id DESC"],
         'LIMIT' => $limit,
      ], $context);

      $labels = $this->getSatisfactionLabels(false);
      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $score = (int) ($row['satisfaction_score'] ?? 0);
         $rows[] = [
            'Chamado'      => '#' . (int) $row['ticket_id'],
            'Atendente'    => (string) ($row['technician_name'] ?: '-'),
            'Requerente'   => (string) ($row['requester_name'] ?: '-'),
            'Satisfação'   => (string) ($labels[$score] ?? $score),
            'Comentário'   => (string) ($row['satisfaction_comment'] ?: '-'),
         ];
      }

      return [
         'columns' => ['Chamado', 'Atendente', 'Requerente', 'Satisfação', 'Comentário'],
         'rows'    => $rows,
      ];
   }

   public function satisfactionAnsweredByMonth(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $period_expression = "DATE_FORMAT($satisfaction_table.date_answered, '%Y-%m')";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("$period_expression AS period_key"),
            new QueryExpression("COUNT(DISTINCT CASE WHEN $satisfaction_table.satisfaction > 0 THEN $table.id END) AS answered"),
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE'   => array_merge(
            $this->getBaseWhere($context),
            $this->getAnsweredSatisfactionWhere($context, $satisfaction_table)
         ),
         'GROUPBY' => [new QueryExpression($period_expression)],
         'ORDER'   => [new QueryExpression('period_key ASC')],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $counts[(string) $row['period_key']] = (int) $row['answered'];
      }

      $rows = [];
      foreach ($this->getMonthBucketsForRange($context->getSatisfactionStart(), $context->getSatisfactionEnd()) as $bucket) {
         $rows[] = [
            'label'  => $bucket['label'],
            'number' => (int) ($counts[$bucket['key']] ?? 0),
            'color'  => '#16a34a',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function satisfactionAverageByMonth(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $period_expression = "DATE_FORMAT($satisfaction_table.date_answered, '%Y-%m')";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("$period_expression AS period_key"),
            new QueryExpression("ROUND(AVG($satisfaction_table.satisfaction), 2) AS average_score"),
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE' => array_merge(
            $this->getBaseWhere($context),
            $this->getAnsweredSatisfactionWhere($context, $satisfaction_table)
         ),
         'GROUPBY' => [new QueryExpression($period_expression)],
         'ORDER'   => [new QueryExpression('period_key ASC')],
      ], $context);

      $averages = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $averages[(string) $row['period_key']] = (float) $row['average_score'];
      }

      $rows = [];
      foreach ($this->getMonthBucketsForRange($context->getSatisfactionStart(), $context->getSatisfactionEnd()) as $bucket) {
         $average = (float) ($averages[$bucket['key']] ?? 0);
         $rows[] = [
            'label'  => $bucket['label'],
            'number' => (int) round($average * 100),
            'value'  => $average > 0 ? number_format($average, 2, ',', '.') : '0',
            'color'  => '#7c3aed',
         ];
      }

      return $this->wrapRows($rows);
   }

   public function countReopened(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $solution_table = 'glpi_itilsolutions';
      $where = $this->getBaseWhere($context);
      $where[] = new QueryExpression(
         "EXISTS (
            SELECT 1
            FROM $solution_table
            WHERE $solution_table.itemtype = 'Ticket'
              AND $solution_table.items_id = $table.id
              AND $solution_table.status = 4
         )"
      );

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'color'  => '#d97706',
      ];
   }

   public function pendingReasons(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $reason_item_table = 'glpi_pendingreasons_items';
      $reason_table = 'glpi_pendingreasons';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$reason_table.id AS item_key",
            "$reason_table.name AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $reason_item_table => [
               'ON' => [
                  $reason_item_table => 'items_id',
                  $table             => 'id',
                  [
                     'AND' => [
                        "$reason_item_table.itemtype" => 'Ticket',
                     ],
                  ],
               ],
            ],
            $reason_table => [
               'ON' => [
                  $reason_table      => 'id',
                  $reason_item_table => 'pendingreasons_id',
               ],
            ],
         ],
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => ["$reason_table.id", "$reason_table.name"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = [
            'label'  => (string) ($row['label'] ?: __('Sem motivo', 'dashboardplus')),
            'number' => (int) $row['total'],
         ];
      }

      return $this->wrapRows($rows);
   }

   public function slaResponseCompliance(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context);
      $where[] = new QueryExpression("$table.time_to_own IS NOT NULL");

      $violation_sql = "(
         $table.status <> " . (int) Ticket::WAITING . "
         AND (
            $table.takeintoaccount_delay_stat > TIME_TO_SEC(TIMEDIFF($table.time_to_own, $table.date))
            OR (
               $table.takeintoaccount_delay_stat = 0
               AND $table.time_to_own < NOW()
            )
         )
      )";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("SUM(CASE WHEN $violation_sql THEN 0 ELSE 1 END) AS complied"),
            new QueryExpression("SUM(CASE WHEN $violation_sql THEN 1 ELSE 0 END) AS violated"),
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'rows' => [
            [
               'label'  => __('Atendimento no prazo', 'dashboardplus'),
               'number' => (int) ($row['complied'] ?? 0),
               'color'  => '#2fb344',
            ],
            [
               'label'  => __('Atendimento fora do prazo', 'dashboardplus'),
               'number' => (int) ($row['violated'] ?? 0),
               'color'  => '#d63939',
            ],
         ],
      ];
   }

   public function averageSolveTimeClosed(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context, self::DATE_CLOSED);
      $where["$table.status"] = Ticket::CLOSED;
      $where[] = new QueryExpression("$table.solve_delay_stat > 0");

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("AVG($table.solve_delay_stat) AS average_seconds"),
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();
      $seconds = (int) round((float) ($row['average_seconds'] ?? 0));

      return [
         'number' => $seconds,
         'value'  => $this->formatDuration($seconds),
         'color'  => '#2563eb',
      ];
   }

   public function averageElapsedSolveTimeClosed(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context, self::DATE_CLOSED);
      $where["$table.status"] = Ticket::CLOSED;
      $where[] = new QueryExpression("$table.solvedate IS NOT NULL");
      $where[] = new QueryExpression("$table.solvedate >= $table.date");

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("AVG(TIMESTAMPDIFF(SECOND, $table.date, $table.solvedate)) AS average_seconds"),
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();
      $seconds = (int) round((float) ($row['average_seconds'] ?? 0));

      return [
         'number' => $seconds,
         'value'  => $this->formatDuration($seconds),
         'color'  => '#0f766e',
      ];
   }

   public function taskEffortByTechnician(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $task_table = 'glpi_tickettasks';
      $user_table = 'glpi_users';

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$user_table.id AS item_key",
            "$user_table.firstname AS firstname",
            "$user_table.realname AS realname",
            "$user_table.name AS login",
            new QueryExpression("ROUND(SUM($task_table.actiontime) / 3600, 1) AS total"),
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $task_table => [
               'ON' => [
                  $task_table => 'tickets_id',
                  $table      => 'id',
               ],
            ],
            $user_table => [
               'ON' => [
                  $user_table => 'id',
                  $task_table => 'users_id',
               ],
            ],
         ],
         'WHERE'   => array_merge($this->getBaseWhere($context), [
            "$task_table.state"       => 2,
            "$task_table.actiontime"  => ['>', 0],
            "$user_table.is_deleted"  => 0,
         ]),
         'GROUPBY' => ["$user_table.id", "$user_table.firstname", "$user_table.realname", "$user_table.name"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ], $context, false, true);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $label = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['realname'] ?? ''));
         if ($label === '') {
            $label = (string) ($row['login'] ?? __('Desconhecido', 'dashboardplus'));
         }
         $rows[] = [
            'label'  => $label,
            'number' => (int) round((float) $row['total']),
         ];
      }

      return $this->wrapRows($rows);
   }

   public function slaCompliance(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context);
      $where[] = new QueryExpression("$table.time_to_resolve IS NOT NULL");

      $solved_statuses = implode(',', array_map('intval', array_merge(
         Ticket::getSolvedStatusArray(),
         Ticket::getClosedStatusArray()
      )));

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression(
               "SUM(CASE
                  WHEN $table.solvedate IS NOT NULL
                   AND $table.solvedate <= $table.time_to_resolve
                   AND $table.status IN ($solved_statuses)
                  THEN 1 ELSE 0 END) AS complied"
            ),
            new QueryExpression(
               "SUM(CASE
                  WHEN (
                     $table.solvedate IS NOT NULL
                     AND $table.solvedate > $table.time_to_resolve
                  ) OR (
                     $table.solvedate IS NULL
                     AND $table.status NOT IN ($solved_statuses)
                     AND $table.time_to_resolve < NOW()
                  )
                  THEN 1 ELSE 0 END) AS violated"
            ),
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      $rows = [
         [
            'label'  => __('SLA cumprido', 'dashboardplus'),
            'number' => (int) ($row['complied'] ?? 0),
            'color'  => '#2fb344',
         ],
         [
            'label'  => __('SLA violado', 'dashboardplus'),
            'number' => (int) ($row['violated'] ?? 0),
            'color'  => '#d63939',
         ],
      ];

      return [
         'rows' => $rows,
      ];
   }

   public function slaComplianceByTechnician(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $ticket_user_table = 'glpi_tickets_users';
      $user_table = 'glpi_users';
      $where = $this->getBaseWhere($context);
      $where["$ticket_user_table.type"] = CommonITILActor::ASSIGN;
      $where["$user_table.is_deleted"] = 0;

      $solved_statuses = $this->getSolvedClosedStatusSql();
      $complied_sql = "$table.time_to_resolve IS NOT NULL
         AND $table.solvedate IS NOT NULL
         AND $table.solvedate <= $table.time_to_resolve
         AND $table.status IN ($solved_statuses)";
      $violated_sql = "$table.time_to_resolve IS NOT NULL
         AND (
            ($table.solvedate IS NOT NULL AND $table.solvedate > $table.time_to_resolve)
            OR ($table.solvedate IS NULL AND $table.status NOT IN ($solved_statuses) AND $table.time_to_resolve < NOW())
         )";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$user_table.id AS item_key",
            "$user_table.firstname AS firstname",
            "$user_table.realname AS realname",
            "$user_table.name AS login",
            'COUNT DISTINCT' => "$table.id AS total",
            new QueryExpression("COUNT(DISTINCT CASE WHEN $complied_sql THEN $table.id END) AS complied"),
            new QueryExpression("COUNT(DISTINCT CASE WHEN $violated_sql THEN $table.id END) AS violated"),
            new QueryExpression("COUNT(DISTINCT CASE WHEN $table.time_to_resolve IS NULL THEN $table.id END) AS without_target"),
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $ticket_user_table => [
               'ON' => [
                  $ticket_user_table => 'tickets_id',
                  $table             => 'id',
               ],
            ],
            $user_table => [
               'ON' => [
                  $user_table        => 'id',
                  $ticket_user_table => 'users_id',
               ],
            ],
         ],
         'WHERE'   => $where,
         'GROUPBY' => ["$user_table.id", "$user_table.firstname", "$user_table.realname", "$user_table.name"],
         'ORDER'   => ['violated DESC', 'total DESC'],
         'LIMIT'   => $limit,
      ], $context, false, true);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $label = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['realname'] ?? ''));
         if ($label === '') {
            $label = (string) ($row['login'] ?? __('Desconhecido', 'dashboardplus'));
         }
         $rows[] = $this->formatSlaRankingRow($label, $row);
      }

      return [
         'columns' => ['Técnico', 'Total', 'Cumpridos', 'Violados', 'Sem meta', '% cumprimento'],
         'rows'    => $rows,
      ];
   }

   public function slaComplianceByCategory(DashboardContext $context, int $limit = 10): array
   {
      $table = Ticket::getTable();
      $category_table = 'glpi_itilcategories';
      $where = $this->getBaseWhere($context);

      $solved_statuses = $this->getSolvedClosedStatusSql();
      $complied_sql = "$table.time_to_resolve IS NOT NULL
         AND $table.solvedate IS NOT NULL
         AND $table.solvedate <= $table.time_to_resolve
         AND $table.status IN ($solved_statuses)";
      $violated_sql = "$table.time_to_resolve IS NOT NULL
         AND (
            ($table.solvedate IS NOT NULL AND $table.solvedate > $table.time_to_resolve)
            OR ($table.solvedate IS NULL AND $table.status NOT IN ($solved_statuses) AND $table.time_to_resolve < NOW())
         )";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            "$category_table.completename AS category_name",
            'COUNT DISTINCT' => "$table.id AS total",
            new QueryExpression("COUNT(DISTINCT CASE WHEN $complied_sql THEN $table.id END) AS complied"),
            new QueryExpression("COUNT(DISTINCT CASE WHEN $violated_sql THEN $table.id END) AS violated"),
            new QueryExpression("COUNT(DISTINCT CASE WHEN $table.time_to_resolve IS NULL THEN $table.id END) AS without_target"),
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $category_table => [
               'ON' => [
                  $category_table => 'id',
                  $table          => 'itilcategories_id',
               ],
            ],
         ],
         'WHERE'   => $where,
         'GROUPBY' => ["$table.itilcategories_id", "$category_table.completename"],
         'ORDER'   => ['violated DESC', 'total DESC'],
         'LIMIT'   => $limit,
      ], $context);

      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = $this->formatSlaRankingRow(
            (string) ($row['category_name'] ?: __('Sem categoria', 'dashboardplus')),
            $row
         );
      }

      return [
         'columns' => ['Categoria', 'Total', 'Cumpridos', 'Violados', 'Sem meta', '% cumprimento'],
         'rows'    => $rows,
      ];
   }

   public function resolutionRatio(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $where = $this->getBaseWhere($context);
      $solved_closed = array_merge(
         Ticket::getSolvedStatusArray(),
         Ticket::getClosedStatusArray()
      );
      $not_solved = Ticket::getNotSolvedStatusArray();

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression(
               "SUM(CASE WHEN $table.status IN (" . implode(',', array_map('intval', $not_solved)) . ") THEN 1 ELSE 0 END) AS not_solved"
            ),
            new QueryExpression(
               "SUM(CASE WHEN $table.status IN (" . implode(',', array_map('intval', $solved_closed)) . ") THEN 1 ELSE 0 END) AS solved_closed"
            ),
         ],
         'FROM'  => $table,
         'WHERE' => $where,
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return [
         'rows' => [
            [
               'label'  => __('Não solucionados', 'dashboardplus'),
               'number' => (int) ($row['not_solved'] ?? 0),
               'color'  => '#ffa500',
            ],
            [
               'label'  => __('Solucionados / fechados', 'dashboardplus'),
               'number' => (int) ($row['solved_closed'] ?? 0),
               'color'  => '#000000',
            ],
         ],
      ];
   }

   public function monthlyEvolution(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $buckets = $this->getEvolutionBuckets($context);
      $period_expression = $buckets['granularity'] === 'year'
         ? "DATE_FORMAT($table.date, '%Y')"
         : "DATE_FORMAT($table.date, '%Y-%m')";

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("$period_expression AS period_key"),
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'    => $table,
         'WHERE'   => $this->getBaseWhere($context),
         'GROUPBY' => [new QueryExpression($period_expression)],
         'ORDER'   => [new QueryExpression('period_key ASC')],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $counts[(string) $row['period_key']] = (int) $row['total'];
      }

      $rows = [];
      foreach ($buckets['items'] as $bucket) {
         $key = $bucket['key'];
         $rows[] = [
            'label'  => $bucket['label'],
            'number' => (int) ($counts[$key] ?? 0),
            'url'    => $this->ticketSearchUrlForPeriod($context, $bucket['start'], $bucket['end']),
            'color'  => '#206bc4',
         ];
      }

      return $this->wrapRows($rows);
   }

   private function getBaseWhere(DashboardContext $context, string $date_field = self::DATE_OPEN): array
   {
      return $this->getBaseWhereForPeriod($context, $date_field, $context->getStart(), $context->getEnd());
   }

   private function getBaseWhereForPeriod(
      DashboardContext $context,
      string $date_field,
      string $start,
      string $end
   ): array
   {
      $table = Ticket::getTable();
      $date_field = in_array($date_field, [self::DATE_OPEN, self::DATE_SOLVED, self::DATE_CLOSED], true)
         ? $date_field
         : self::DATE_OPEN;
      $where = [
         "$table.is_deleted" => 0,
      ];
      $where[] = ["$table.$date_field" => ['>=', $start . ' 00:00:00']];
      $where[] = ["$table.$date_field" => ['<=', $end . ' 23:59:59']];
      if ($context->getItilcategoriesId() !== null) {
         $where["$table.itilcategories_id"] = $context->getItilcategoriesId();
      }
      if ($context->getType() !== null) {
         $where["$table.type"] = $context->getType();
      }
      if ($context->getPriority() !== null) {
         $where["$table.priority"] = $context->getPriority();
      }

      return array_merge($where, $context->getEntityCriteria($table));
   }

   private function dailyTicketCounts(DashboardContext $context, string $date_field, int $limit): array
   {
      $counts = $this->dailyCounts($context, $date_field, $limit);
      $range = $this->getLimitedDateRange($context, $limit);
      $rows = [];

      foreach ($this->getDayBuckets($range['start'], $range['end']) as $bucket) {
         $rows[] = [
            'label'  => $bucket['label'],
            'number' => (int) ($counts[$bucket['key']] ?? 0),
            'url'    => $this->ticketSearchUrlForPeriod($context, $bucket['key'], $bucket['key'], [], '', [], $date_field),
            'color'  => '#2563eb',
         ];
      }

      return $this->wrapRows($rows);
   }

   private function dailyCounts(
      DashboardContext $context,
      string $date_field,
      int $limit,
      bool $only_with_date = false
   ): array
   {
      $table = Ticket::getTable();
      $range = $this->getLimitedDateRange($context, $limit);
      $where = $this->getBaseWhereForPeriod($context, $date_field, $range['start'], $range['end']);
      if ($only_with_date) {
         $where[] = new QueryExpression("$table.$date_field IS NOT NULL");
      }

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            new QueryExpression("DATE($table.$date_field) AS period_key"),
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'    => $table,
         'WHERE'   => $where,
         'GROUPBY' => [new QueryExpression("DATE($table.$date_field)")],
         'ORDER'   => [new QueryExpression('period_key ASC')],
      ], $context);

      $counts = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $counts[(string) $row['period_key']] = (int) $row['total'];
      }

      return $counts;
   }

   private function getLimitedDateRange(DashboardContext $context, int $limit): array
   {
      $limit = max(1, min(120, $limit));
      $start = new \DateTimeImmutable($context->getStart());
      $end = new \DateTimeImmutable($context->getEnd());
      $min_start = $end->modify('-' . ($limit - 1) . ' days');
      if ($start < $min_start) {
         $start = $min_start;
      }

      return [
         'start' => $start->format('Y-m-d'),
         'end'   => $end->format('Y-m-d'),
      ];
   }

   private function getDayBuckets(string $start, string $end): array
   {
      $cursor = new \DateTimeImmutable($start);
      $last = new \DateTimeImmutable($end);
      $buckets = [];

      while ($cursor <= $last) {
         $buckets[] = [
            'key'   => $cursor->format('Y-m-d'),
            'label' => $cursor->format('d/m'),
         ];
         $cursor = $cursor->modify('+1 day');
      }

      return $buckets;
   }

   private function withTicketProfileCriteria(
      array $criteria,
      DashboardContext $context,
      bool $has_group_join = false,
      bool $has_user_join = false
   ): array
   {
      $criteria = $this->withAssignmentFilterCriteria($criteria, $context, $has_group_join, $has_user_join);

      return array_merge_recursive($criteria, Ticket::getCriteriaFromProfile());
   }

   private function withAssignmentFilterCriteria(
      array $criteria,
      DashboardContext $context,
      bool $has_group_join,
      bool $has_user_join
   ): array
   {
      $table = Ticket::getTable();
      $group_ticket_table = 'glpi_groups_tickets';
      $ticket_user_table = 'glpi_tickets_users';

      if ($context->getGroupsId() !== null) {
         if (!$has_group_join) {
            $criteria['INNER JOIN'][$group_ticket_table] = [
               'ON' => [
                  $group_ticket_table => 'tickets_id',
                  $table              => 'id',
                  [
                     'AND' => [
                        "$group_ticket_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ];
         }
         $criteria['WHERE']["$group_ticket_table.groups_id"] = $context->getGroupsId();
      }

      if ($context->getUsersId() !== null) {
         if (!$has_user_join) {
            $criteria['INNER JOIN'][$ticket_user_table] = [
               'ON' => [
                  $ticket_user_table => 'tickets_id',
                  $table             => 'id',
                  [
                     'AND' => [
                        "$ticket_user_table.type" => CommonITILActor::ASSIGN,
                     ],
                  ],
               ],
            ];
         }
         $criteria['WHERE']["$ticket_user_table.users_id"] = $context->getUsersId();
      }

      return $criteria;
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

   private function getSolvedClosedStatusSql(): string
   {
      return implode(',', array_map('intval', array_merge(
         Ticket::getSolvedStatusArray(),
         Ticket::getClosedStatusArray()
      )));
   }

   private function formatSlaRankingRow(string $label, array $row): array
   {
      $total = (int) ($row['total'] ?? 0);
      $complied = (int) ($row['complied'] ?? 0);
      $violated = (int) ($row['violated'] ?? 0);
      $without_target = (int) ($row['without_target'] ?? 0);
      $with_target = max(0, $complied + $violated);
      $rate = $with_target > 0 ? round(($complied / $with_target) * 100, 2) : 0;

      return [
         array_key_exists('category_name', $row) ? 'Categoria' : 'Técnico' => $label,
         'Total'          => number_format($total, 0, ',', '.'),
         'Cumpridos'      => number_format($complied, 0, ',', '.'),
         'Violados'       => number_format($violated, 0, ',', '.'),
         'Sem meta'       => number_format($without_target, 0, ',', '.'),
         '% cumprimento'  => number_format($rate, 2, ',', '.') . '%',
      ];
   }

   private function getSatisfactionTotals(DashboardContext $context): array
   {
      $table = Ticket::getTable();
      $satisfaction_table = 'glpi_ticketsatisfactions';
      $date_condition = $this->getSatisfactionDateCondition($context, $satisfaction_table);

      $criteria = $this->withTicketProfileCriteria([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
            new QueryExpression("COUNT(DISTINCT CASE WHEN $satisfaction_table.satisfaction > 0 AND $date_condition THEN $table.id END) AS answered"),
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $satisfaction_table => [
               'ON' => [
                  $satisfaction_table => 'tickets_id',
                  $table              => 'id',
               ],
            ],
         ],
         'WHERE' => $this->getBaseWhere($context),
      ], $context);

      $row = $this->getReadDB()->request($criteria)->current();

      return is_array($row) ? $row : ['total' => 0, 'answered' => 0];
   }

   private function getAnsweredSatisfactionWhere(DashboardContext $context, string $satisfaction_table): array
   {
      return [
         "$satisfaction_table.satisfaction" => ['>', 0],
         ["$satisfaction_table.date_answered" => ['>=', $context->getSatisfactionStartDateTime()]],
         ["$satisfaction_table.date_answered" => ['<=', $context->getSatisfactionEndDateTime()]],
      ];
   }

   private function getSatisfactionDateCondition(DashboardContext $context, string $satisfaction_table): string
   {
      $start = addslashes($context->getSatisfactionStartDateTime());
      $end = addslashes($context->getSatisfactionEndDateTime());

      return "$satisfaction_table.date_answered >= '$start' AND $satisfaction_table.date_answered <= '$end'";
   }

   private function getSatisfactionLabels(bool $include_unanswered): array
   {
      $labels = [
         1 => __('Muito insatisfeito', 'dashboardplus'),
         2 => __('Insatisfeito', 'dashboardplus'),
         3 => __('Regular', 'dashboardplus'),
         4 => __('Satisfeito', 'dashboardplus'),
         5 => __('Muito satisfeito', 'dashboardplus'),
      ];

      if ($include_unanswered) {
         return [0 => __('Não respondido', 'dashboardplus')] + $labels;
      }

      return $labels;
   }

   private function getSatisfactionColors(bool $include_unanswered): array
   {
      $colors = [
         1 => '#dc2626',
         2 => '#f97316',
         3 => '#facc15',
         4 => '#60a5fa',
         5 => '#84cc16',
      ];

      if ($include_unanswered) {
         return [0 => '#9ca3af'] + $colors;
      }

      return $colors;
   }

   private function getStatusMetadata(int $status): array
   {
      return [
         'status_key'   => Ticket::getStatusKey($status),
         'status_class' => Ticket::getStatusClass($status),
         'color'        => $this->getStatusColor($status),
      ];
   }

   private function getStatusColor(int $status): string
   {
      switch ($status) {
         case Ticket::INCOMING:
         case Ticket::ASSIGNED:
            return '#49bf4d';

         case Ticket::PLANNED:
            return '#1b2f62';

         case Ticket::WAITING:
            return '#ffa500';

         case Ticket::SOLVED:
         case Ticket::CLOSED:
            return '#000000';
      }

      return '#626976';
   }

   private function getPriorityColor(int $priority): string
   {
      switch ($priority) {
         case 6:
            return '#be123c';

         case 5:
            return '#dc2626';

         case 4:
            return '#d97706';

         case 3:
            return '#facc15';

         case 2:
            return '#16a34a';
      }

      return '#2563eb';
   }

   private function getMonthBuckets(DashboardContext $context): array
   {
      return $this->getMonthBucketsForRange($context->getStart(), $context->getEnd());
   }

   private function getMonthBucketsForRange(string $start_date, string $end_date): array
   {
      $start = new \DateTimeImmutable($start_date);
      $end = new \DateTimeImmutable($end_date);
      $cursor = $start->modify('first day of this month');
      $last = $end->modify('first day of this month');
      $buckets = [];

      while ($cursor <= $last) {
         $month_start = $cursor->format('Y-m-01');
         $month_end = $cursor->modify('last day of this month')->format('Y-m-d');

         if ($month_start < $start_date) {
            $month_start = $start_date;
         }
         if ($month_end > $end_date) {
            $month_end = $end_date;
         }

         $buckets[] = [
            'key'   => $cursor->format('Y-m'),
            'label' => $cursor->format('m/Y'),
            'start' => $month_start,
            'end'   => $month_end,
         ];

         $cursor = $cursor->modify('+1 month');
      }

      return $buckets;
   }

   private function getEvolutionBuckets(DashboardContext $context): array
   {
      $start = new \DateTimeImmutable($context->getStart());
      $end = new \DateTimeImmutable($context->getEnd());
      $days = max(1, (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 86400) + 1);

      if ($days <= 1095) {
         return [
            'granularity' => 'month',
            'items'       => $this->getMonthBuckets($context),
         ];
      }

      $cursor = new \DateTimeImmutable($start->format('Y') . '-01-01');
      $last = new \DateTimeImmutable($end->format('Y') . '-01-01');
      $buckets = [];

      while ($cursor <= $last) {
         $year_start = $cursor->format('Y-01-01');
         $year_end = $cursor->format('Y-12-31');

         if ($year_start < $context->getStart()) {
            $year_start = $context->getStart();
         }
         if ($year_end > $context->getEnd()) {
            $year_end = $context->getEnd();
         }

         $buckets[] = [
            'key'   => $cursor->format('Y'),
            'label' => $cursor->format('Y'),
            'start' => $year_start,
            'end'   => $year_end,
         ];

         $cursor = $cursor->modify('+1 year');
      }

      return [
         'granularity' => 'year',
         'items'       => $buckets,
      ];
   }

   private function ticketSearchUrl(
      DashboardContext $context,
      array $statuses = [],
      string $special = '',
      array $extra = [],
      string $date_field = self::DATE_OPEN
   ): string
   {
      return $this->ticketSearchUrlForPeriod(
         $context,
         $context->getStart(),
         $context->getEnd(),
         $statuses,
         $special,
         $extra,
         $date_field
      );
   }

   private function ticketSearchUrlForPeriod(
      DashboardContext $context,
      string $start,
      string $end,
      array $statuses = [],
      string $special = '',
      array $extra = [],
      string $date_field = self::DATE_OPEN
   ): string
   {
      $date_search_field = $this->getDateSearchField($date_field);
      $criteria = [
         [
            'field'      => $date_search_field,
            'searchtype' => 'morethan',
            'value'      => $start,
         ],
         [
            'link'       => 'AND',
            'field'      => $date_search_field,
            'searchtype' => 'lessthan',
            'value'      => $end,
         ],
      ];

      if ($statuses !== []) {
         $criteria[] = [
            'link'       => 'AND',
            'field'      => 12,
            'searchtype' => 'equals',
            'value'      => count($statuses) === 1 ? reset($statuses) : 'notold',
         ];
      }

      if ($special === 'late') {
         $criteria[] = [
            'link'       => 'AND',
            'field'      => 82,
            'searchtype' => 'equals',
            'value'      => 1,
         ];
      }

      foreach ($this->getSearchCriteriaFromContext($context) as $item) {
         $criteria[] = $item;
      }

      foreach ($extra as $item) {
         $item['link'] = $item['link'] ?? 'AND';
         $criteria[] = $item;
      }

      return Ticket::getSearchURL() . '?' . Toolbox::append_params([
         'criteria' => $criteria,
         'reset'    => 'reset',
      ]);
   }

   private function getDateSearchField(string $date_field): int
   {
      switch ($date_field) {
         case self::DATE_SOLVED:
            return 17;

         case self::DATE_CLOSED:
            return 16;
      }

      return 15;
   }

   private function getSearchCriteriaFromContext(DashboardContext $context): array
   {
      $criteria = [];
      $map = [
         'groups_id'          => ['field' => 8, 'value' => $context->getGroupsId()],
         'users_id'           => ['field' => 5, 'value' => $context->getUsersId()],
         'itilcategories_id'  => ['field' => 7, 'value' => $context->getItilcategoriesId()],
         'type'               => ['field' => 14, 'value' => $context->getType()],
         'priority'           => ['field' => 3, 'value' => $context->getPriority()],
      ];

      foreach ($map as $filter) {
         if ($filter['value'] === null) {
            continue;
         }
         $criteria[] = [
            'link'       => 'AND',
            'field'      => $filter['field'],
            'searchtype' => 'equals',
            'value'      => $filter['value'],
         ];
      }

      return $criteria;
   }

   private function formatDuration(int $seconds): string
   {
      if ($seconds <= 0) {
         return '00:00:00';
      }

      $hours = (int) floor($seconds / 3600);
      $minutes = (int) floor(($seconds % 3600) / 60);
      $remaining_seconds = $seconds % 60;

      return sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining_seconds);
   }

   private function getReadDB()
   {
      if (class_exists(DBConnection::class)) {
         return DBConnection::getReadConnection();
      }

      global $DB;
      return $DB;
   }

   private function fieldExists(string $table, string $field): bool
   {
      global $DB;
      if (method_exists($DB, 'fieldExists')) {
         return (bool) $DB->fieldExists($table, $field);
      }

      return true;
   }
}
