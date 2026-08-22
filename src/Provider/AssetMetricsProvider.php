<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

use Computer;
use DBConnection;
use GlpiPlugin\Dashboardplus\DashboardContext;
use Monitor;
use Phone;
use Printer;
use QueryExpression;

class AssetMetricsProvider
{
   private const CITY_COORDINATES = [
      'sao paulo' => [-46.6333, -23.5505],
      'são paulo' => [-46.6333, -23.5505],
      'guarulhos' => [-46.5333, -23.4628],
      'campinas' => [-47.0608, -22.9056],
      'sao bernardo do campo' => [-46.5650, -23.6914],
      'são bernardo do campo' => [-46.5650, -23.6914],
      'santo andre' => [-46.5383, -23.6639],
      'santo andré' => [-46.5383, -23.6639],
      'osasco' => [-46.7917, -23.5329],
      'sorocaba' => [-47.4581, -23.5015],
      'ribeirao preto' => [-47.8103, -21.1775],
      'ribeirão preto' => [-47.8103, -21.1775],
      'santos' => [-46.3336, -23.9608],
      'sao jose dos campos' => [-45.8842, -23.2237],
      'são josé dos campos' => [-45.8842, -23.2237],
      'jundiai' => [-46.8845, -23.1857],
      'jundiaí' => [-46.8845, -23.1857],
      'piracicaba' => [-47.6492, -22.7253],
      'bauru' => [-49.0600, -22.3147],
      'sao jose do rio preto' => [-49.3797, -20.8113],
      'são josé do rio preto' => [-49.3797, -20.8113],
      'mogi das cruzes' => [-46.1878, -23.5208],
      'diadema' => [-46.6228, -23.6861],
      'carapicuiba' => [-46.8356, -23.5227],
      'carapicuíba' => [-46.8356, -23.5227],
      'taubate' => [-45.5553, -23.0264],
      'taubaté' => [-45.5553, -23.0264],
      'limeira' => [-47.4017, -22.5647],
      'suzano' => [-46.3108, -23.5425],
      'barueri' => [-46.8764, -23.5111],
      'embu das artes' => [-46.8522, -23.6489],
      'indaiatuba' => [-47.2181, -23.0884],
      'americana' => [-47.3314, -22.7392],
      'araraquara' => [-48.1756, -21.7944],
      'marilia' => [-49.9458, -22.2171],
      'marília' => [-49.9458, -22.2171],
      'presidente prudente' => [-51.3889, -22.1256],
      'rio claro' => [-47.5614, -22.4114],
      'aracatuba' => [-50.4328, -21.2076],
      'araçatuba' => [-50.4328, -21.2076],
      'franca' => [-47.4008, -20.5386],
   ];

   public function totalComputers(DashboardContext $context): array
   {
      return $this->countAssets(Computer::getTable(), $context, '#2563eb');
   }

   public function totalMonitors(DashboardContext $context): array
   {
      return $this->countAssets(Monitor::getTable(), $context, '#0891b2');
   }

   public function totalPrinters(DashboardContext $context): array
   {
      return $this->countAssets(Printer::getTable(), $context, '#7c3aed');
   }

   public function totalPhones(DashboardContext $context): array
   {
      return $this->countAssets(Phone::getTable(), $context, '#16a34a');
   }

   public function computersByManufacturer(DashboardContext $context, int $limit = 10): array
   {
      return $this->breakdownByDropdown(
         Computer::getTable(),
         'glpi_manufacturers',
         'manufacturers_id',
         $context,
         __('Sem fabricante', 'dashboardplus'),
         $limit
      );
   }

   public function computersByType(DashboardContext $context, int $limit = 10): array
   {
      return $this->breakdownByDropdown(
         Computer::getTable(),
         'glpi_computertypes',
         'computertypes_id',
         $context,
         __('Sem tipo', 'dashboardplus'),
         $limit
      );
   }

   public function computersByLocation(DashboardContext $context, int $limit = 10): array
   {
      return $this->breakdownByDropdown(
         Computer::getTable(),
         'glpi_locations',
         'locations_id',
         $context,
         __('Sem localização', 'dashboardplus'),
         $limit,
         'completename'
      );
   }

   public function computersByOperatingSystem(DashboardContext $context, int $limit = 10): array
   {
      $table = Computer::getTable();
      $items_os_table = 'glpi_items_operatingsystems';
      $os_table = 'glpi_operatingsystems';

      $criteria = [
         'SELECT' => [
            "$os_table.name AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $items_os_table => [
               'ON' => [
                  $items_os_table => 'items_id',
                  $table          => 'id',
                  [
                     'AND' => [
                        "$items_os_table.itemtype"   => 'Computer',
                        "$items_os_table.is_deleted" => 0,
                     ],
                  ],
               ],
            ],
            $os_table => [
               'ON' => [
                  $os_table       => 'id',
                  $items_os_table => 'operatingsystems_id',
               ],
            ],
         ],
         'WHERE'   => $this->getAssetBaseWhere($table, $context),
         'GROUPBY' => ["$os_table.id", "$os_table.name"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ];

      return $this->rowsFromCriteria($criteria, __('Sem sistema operacional', 'dashboardplus'));
   }

   public function computersBySaoPauloCity(DashboardContext $context, int $limit = 30): array
   {
      $table = Computer::getTable();
      $location_table = 'glpi_locations';
      $where = $this->getAssetBaseWhere($table, $context);
      $where[] = [
         'OR' => [
            ["$location_table.state" => ['LIKE', 'SP']],
            ["$location_table.state" => ['LIKE', 'São Paulo']],
            ["$location_table.state" => ['LIKE', 'Sao Paulo']],
         ],
      ];

      $criteria = [
         'SELECT' => [
            "$location_table.town AS city",
            "$location_table.latitude AS latitude",
            "$location_table.longitude AS longitude",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'INNER JOIN' => [
            $location_table => [
               'ON' => [
                  $location_table => 'id',
                  $table          => 'locations_id',
               ],
            ],
         ],
         'WHERE'   => $where,
         'GROUPBY' => ["$location_table.town", "$location_table.latitude", "$location_table.longitude"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ];

      $markers = [];
      $unmapped = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $city = trim((string) ($row['city'] ?? ''));
         if ($city === '') {
            $unmapped[] = [
               'label'  => __('Sem cidade', 'dashboardplus'),
               'number' => (int) $row['total'],
            ];
            continue;
         }

         $coordinates = $this->resolveCoordinates($city, $row['latitude'] ?? null, $row['longitude'] ?? null);
         if ($coordinates === null) {
            $unmapped[] = [
               'label'  => $city,
               'number' => (int) $row['total'],
            ];
            continue;
         }

         $markers[] = [
            'label'     => $city,
            'number'    => (int) $row['total'],
            'longitude' => $coordinates[0],
            'latitude'  => $coordinates[1],
         ];
      }

      return [
         'markers'  => $markers,
         'unmapped' => $unmapped,
         'total'    => array_sum(array_map(static function(array $marker): int {
            return (int) ($marker['number'] ?? 0);
         }, array_merge($markers, $unmapped))),
      ];
   }

   private function countAssets(string $table, DashboardContext $context, string $color): array
   {
      $row = $this->getReadDB()->request([
         'SELECT' => [
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM'  => $table,
         'WHERE' => $this->getAssetBaseWhere($table, $context),
      ])->current();

      return [
         'number' => (int) ($row['total'] ?? 0),
         'color'  => $color,
      ];
   }

   private function breakdownByDropdown(
      string $table,
      string $dropdown_table,
      string $foreign_key,
      DashboardContext $context,
      string $empty_label,
      int $limit,
      string $label_field = 'name'
   ): array
   {
      $criteria = [
         'SELECT' => [
            "$dropdown_table.$label_field AS label",
            'COUNT DISTINCT' => "$table.id AS total",
         ],
         'FROM' => $table,
         'LEFT JOIN' => [
            $dropdown_table => [
               'ON' => [
                  $dropdown_table => 'id',
                  $table          => $foreign_key,
               ],
            ],
         ],
         'WHERE'   => $this->getAssetBaseWhere($table, $context),
         'GROUPBY' => ["$table.$foreign_key", "$dropdown_table.$label_field"],
         'ORDER'   => ['total DESC'],
         'LIMIT'   => $limit,
      ];

      return $this->rowsFromCriteria($criteria, $empty_label);
   }

   private function rowsFromCriteria(array $criteria, string $empty_label): array
   {
      $rows = [];
      foreach ($this->getReadDB()->request($criteria) as $row) {
         $rows[] = [
            'label'  => (string) ($row['label'] ?: $empty_label),
            'number' => (int) $row['total'],
         ];
      }

      return [
         'rows'  => $rows,
         'total' => array_sum(array_map(static function(array $row): int {
            return (int) ($row['number'] ?? 0);
         }, $rows)),
      ];
   }

   private function getAssetBaseWhere(string $table, DashboardContext $context): array
   {
      $where = [
         "$table.is_deleted" => 0,
      ];

      if ($this->hasColumn($table, 'is_template')) {
         $where["$table.is_template"] = 0;
      }

      return array_merge($where, $context->getEntityCriteria($table));
   }

   private function resolveCoordinates(string $city, $latitude, $longitude): ?array
   {
      $lat = str_replace(',', '.', trim((string) $latitude));
      $lon = str_replace(',', '.', trim((string) $longitude));
      if (is_numeric($lat) && is_numeric($lon)) {
         return [(float) $lon, (float) $lat];
      }

      $key = mb_strtolower($city, 'UTF-8');
      return self::CITY_COORDINATES[$key] ?? null;
   }

   private function hasColumn(string $table, string $column): bool
   {
      static $cache = [];
      $key = $table . '.' . $column;
      if (array_key_exists($key, $cache)) {
         return $cache[$key];
      }

      $row = $this->getReadDB()->request([
         'SELECT' => [new QueryExpression('1')],
         'FROM'   => 'information_schema.COLUMNS',
         'WHERE'  => [
            'TABLE_SCHEMA' => $this->getReadDB()->dbdefault,
            'TABLE_NAME'   => $table,
            'COLUMN_NAME'  => $column,
         ],
         'LIMIT'  => 1,
      ])->current();

      $cache[$key] = (bool) $row;
      return $cache[$key];
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
