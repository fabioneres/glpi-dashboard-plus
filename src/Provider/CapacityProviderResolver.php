<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus\Provider;

class CapacityProviderResolver
{
   public static function resolve(): CapacityProviderInterface
   {
      $providers = [
         new SmartAssignmentCapacityProvider(),
         new DefaultCapacityProvider(),
      ];

      foreach ($providers as $provider) {
         if ($provider->isAvailable()) {
            return $provider;
         }
      }

      return new DefaultCapacityProvider();
   }
}
