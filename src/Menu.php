<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use CommonDBTM;

class Menu extends CommonDBTM
{
   protected static $notable = true;

   public static function getTypeName($nb = 0)
   {
      return Config::getTypeName($nb);
   }

   public static function getIcon()
   {
      return Config::getIcon();
   }

   public static function getMenuName()
   {
      return Config::getTypeName();
   }

   public static function getMenuContent()
   {
      if (!Config::canView()) {
         return false;
      }

      $menu = [
         'title' => self::getMenuName(),
         'page'  => Config::pluginUrl('/front/dashboard.php', false),
         'icon'  => self::getIcon(),
      ];

      if (Config::canAdmin()) {
         $menu['links']['config'] = Config::pluginUrl('/front/config.form.php', false);
      }

      return $menu;
   }
}
