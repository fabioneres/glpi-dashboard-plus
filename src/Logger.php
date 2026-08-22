<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use Throwable;
use Toolbox;

class Logger
{
   private const LOG_FILE = 'plugin_dashboardplus';

   public static function error(string $message, array $context = []): void
   {
      self::write('ERROR', $message, $context);
   }

   public static function warning(string $message, array $context = []): void
   {
      self::write('WARNING', $message, $context);
   }

   public static function info(string $message, array $context = []): void
   {
      self::write('INFO', $message, $context);
   }

   public static function exception(Throwable $e, string $message = 'Unhandled exception'): void
   {
      self::error($message, [
         'exception' => get_class($e),
         'message'   => $e->getMessage(),
         'file'      => $e->getFile(),
         'line'      => $e->getLine(),
      ]);
   }

   private static function write(string $level, string $message, array $context = []): void
   {
      $line = sprintf(
         '[%s] [%s] %s',
         date('Y-m-d H:i:s'),
         $level,
         $message
      );

      if ($context !== []) {
         $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
      }

      Toolbox::logInFile(self::LOG_FILE, $line . PHP_EOL);
   }
}
