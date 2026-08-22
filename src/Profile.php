<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use CommonGLPI;
use DBmysql;
use DbUtils;
use Html;
use Profile as GlpiProfile;
use ProfileRight;
use Session;

class Profile extends GlpiProfile
{
   public static $rightname = 'profile';

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item instanceof GlpiProfile && $item->getID()) {
         return self::createTabEntry(Config::getTypeName(), 0, $item::getType(), Config::getIcon());
      }

      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item instanceof GlpiProfile) {
         self::addDefaultProfileInfos((int) $item->getID(), self::getDefaultRights());

         $profile = new self();
         $profile->showForm((int) $item->getID());
      }

      return true;
   }

   public static function getAllRights($all = false)
   {
      return [
         [
            'itemtype' => Dashboard::class,
            'label'    => __('Visualizar Dashboard Plus', 'dashboardplus'),
            'field'    => Config::RIGHT_VIEW,
         ],
         [
            'itemtype' => Config::class,
            'label'    => __('Administrar Dashboard Plus', 'dashboardplus'),
            'field'    => Config::RIGHT_ADMIN,
         ],
         [
            'itemtype' => Config::class,
            'label'    => __('Configurar widgets do Dashboard Plus', 'dashboardplus'),
            'field'    => Config::RIGHT_WIDGETS,
         ],
         [
            'itemtype' => Dashboard::class,
            'label'    => __('Visualizar indicadores globais do Dashboard Plus', 'dashboardplus'),
            'field'    => Config::RIGHT_GLOBAL,
         ],
      ];
   }

   public static function getDefaultRights(): array
   {
      return [
         Config::RIGHT_VIEW    => 0,
         Config::RIGHT_ADMIN   => 0,
         Config::RIGHT_WIDGETS => 0,
         Config::RIGHT_GLOBAL  => 0,
      ];
   }

   public static function createFirstAccess(int $profiles_id): void
   {
      self::addDefaultProfileInfos($profiles_id, [
         Config::RIGHT_VIEW    => READ,
         Config::RIGHT_ADMIN   => ALLSTANDARDRIGHT,
         Config::RIGHT_WIDGETS => UPDATE,
         Config::RIGHT_GLOBAL  => READ,
      ], true);
   }

   public static function installRights(): void
   {
      $dbu = new DbUtils();

      foreach (Config::getRightNames() as $right) {
         if ($dbu->countElementsInTable('glpi_profilerights', ['name' => $right]) === 0) {
            ProfileRight::addProfileRights([$right]);
         }
      }
   }

   public static function initProfile(): void
   {
      /** @var DBmysql $DB */
      global $DB;

      self::installRights();

      if (!isset($_SESSION['glpiactiveprofile']['id'])
         || !$DB->tableExists('glpi_profilerights')
      ) {
         return;
      }

      $profiles_id = (int) $_SESSION['glpiactiveprofile']['id'];
      self::addDefaultProfileInfos($profiles_id, self::getDefaultRights());

      $iterator = $DB->request([
         'SELECT' => ['name', 'rights'],
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => [
            'profiles_id' => $profiles_id,
            'name'        => Config::getRightNames(),
         ],
      ]);

      foreach ($iterator as $row) {
         $_SESSION['glpiactiveprofile'][$row['name']] = (int) $row['rights'];
      }
   }

   public static function removeRightsFromSession(): void
   {
      foreach (Config::getRightNames() as $right) {
         unset($_SESSION['glpiactiveprofile'][$right]);
      }
   }

   public static function addDefaultProfileInfos(int $profiles_id, array $rights, bool $upgrade = false): void
   {
      /** @var DBmysql $DB */
      global $DB;

      if (!$DB->tableExists('glpi_profilerights')) {
         return;
      }

      $profileRight = new ProfileRight();

      foreach ($rights as $right => $value) {
         $iterator = $DB->request([
            'SELECT' => ['id', 'rights'],
            'FROM'   => 'glpi_profilerights',
            'WHERE'  => [
               'profiles_id' => $profiles_id,
               'name'        => $right,
            ],
            'LIMIT'  => 1,
         ]);

         $row = $iterator->current();
         $session_value = (int) $value;

         if (!$row) {
            $profileRight->add([
               'profiles_id' => $profiles_id,
               'name'        => $right,
               'rights'      => $value,
            ]);
         } elseif ($upgrade) {
            $current = (int) ($row['rights'] ?? 0);
            $new = $current | (int) $value;
            if ($new !== $current) {
               $DB->update('glpi_profilerights', ['rights' => $new], ['id' => (int) $row['id']]);
            }
            $session_value = $new;
         } else {
            $session_value = (int) ($row['rights'] ?? 0);
         }

         if (isset($_SESSION['glpiactiveprofile']['id'])
            && (int) $_SESSION['glpiactiveprofile']['id'] === $profiles_id
         ) {
            $_SESSION['glpiactiveprofile'][$right] = $session_value;
         }
      }
   }

   public function showForm($ID, $options = [])
   {
      if (!self::canView()) {
         return false;
      }

      $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
      $profile = new GlpiProfile();

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' action='" . GlpiProfile::getFormURL() . "' data-track-changes='true'>";
      }

      $profile->displayRightsChoiceMatrix(self::getAllRights(), [
         'title'         => Config::getTypeName(),
         'canedit'       => $canedit,
         'default_class' => 'tab_bg_2',
      ]);

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(__('Salvar', 'dashboardplus'), [
            'name'  => 'update',
            'class' => 'btn btn-primary mt-2',
         ]);
         echo "</div>";
         Html::closeForm();
      }
      echo "</div>";

      return true;
   }
}
