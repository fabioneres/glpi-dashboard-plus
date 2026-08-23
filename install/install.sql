CREATE TABLE IF NOT EXISTS `glpi_plugin_dashboardplus_configs` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `default_period_days` int unsigned NOT NULL DEFAULT '30',
   `auto_refresh` tinyint NOT NULL DEFAULT '1',
   `refresh_interval` int unsigned NOT NULL DEFAULT '300',
   `use_cache` tinyint NOT NULL DEFAULT '1',
   `cache_ttl` int unsigned NOT NULL DEFAULT '120',
   `dashboard_theme` varchar(30) NOT NULL DEFAULT 'executive',
   `accent_color` varchar(7) NOT NULL DEFAULT '#10b6b4',
   `chart_palette` varchar(30) NOT NULL DEFAULT 'business',
   `entity_default_enabled` tinyint NOT NULL DEFAULT '1',
   `default_enabled_tabs` longtext NULL,
   `capacity_enabled` tinyint NOT NULL DEFAULT '1',
   `capacity_cache_ttl` int unsigned NOT NULL DEFAULT '60',
   `capacity_config` longtext NULL,
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_dashboardplus_configentities` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `plugin_dashboardplus_configs_id` int unsigned NOT NULL DEFAULT '1',
   `entities_id` int unsigned NOT NULL DEFAULT '0',
   `is_recursive` tinyint NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`plugin_dashboardplus_configs_id`, `entities_id`),
   KEY `idx_entity` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_dashboardplus_entityconfigs` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `entities_id` int unsigned NOT NULL DEFAULT '0',
   `is_enabled` tinyint NOT NULL DEFAULT '1',
   `is_recursive` tinyint NOT NULL DEFAULT '0',
   `enabled_tabs` longtext NULL,
   `config` longtext NULL,
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`entities_id`),
   KEY `idx_entity_enabled` (`entities_id`, `is_enabled`),
   KEY `idx_recursive` (`is_recursive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_dashboardplus_widgetconfigs` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `widget_key` varchar(100) NOT NULL,
   `is_enabled` tinyint NOT NULL DEFAULT '1',
   `display_order` int unsigned NOT NULL DEFAULT '0',
   `width` int unsigned NOT NULL DEFAULT '3',
   `height` int unsigned NOT NULL DEFAULT '2',
   `config` longtext NULL,
   `date_creation` timestamp NULL DEFAULT NULL,
   `date_mod` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unicity` (`widget_key`),
   KEY `idx_enabled_order` (`is_enabled`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
