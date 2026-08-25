<?php
/**
 * -------------------------------------------------------------------------
 * Dashboard Plus plugin for GLPI
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Dashboardplus;

use Html;

class AboutPage
{
   public static function show(): void
   {
      Config::checkView();

      $settings = Config::getSettings();
      $theme_class = Html::cleanInputText(Config::getThemeClass($settings));
      $theme_style = Config::getThemeStyleAttribute($settings);

      echo "<div class='dashboardplus-config dashboardplus-about {$theme_class}'{$theme_style}>";
      self::showInlineStyles();
      self::showHeader();
      self::showTabbedContent();
      echo "</div>";
   }

   private static function showInlineStyles(): void
   {
      echo "<style>
         .dashboardplus-about {
            max-width: 1180px;
         }
         .dashboardplus-about .dashboardplus-config-section {
            border-left: 0;
         }
         .dashboardplus-about-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
         }
         .dashboardplus-about-card {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 12px;
            min-height: 132px;
            padding: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 8px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.88)), var(--dp-surface);
            box-shadow: 0 8px 20px rgba(31, 41, 55, 0.05);
         }
         .dashboardplus-about-card-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            color: #ffffff;
            background: linear-gradient(135deg, var(--dp-accent, #10b6b4), var(--dp-blue, #2563eb));
         }
         .dashboardplus-about-card-warning .dashboardplus-about-card-icon {
            background: linear-gradient(135deg, #f59e0b, #fb7185);
         }
         .dashboardplus-about-card-danger .dashboardplus-about-card-icon {
            background: linear-gradient(135deg, #ef4444, #f97316);
         }
         .dashboardplus-about-card h3 {
            margin: 0 0 6px;
            font-size: 14px;
            font-weight: 700;
            color: var(--dp-text);
         }
         .dashboardplus-about-card p {
            margin: 0;
            color: var(--dp-muted);
            font-size: 13px;
            line-height: 1.42;
         }
         .dashboardplus-about-card code {
            display: inline-block;
            margin-top: 10px;
            max-width: 100%;
            padding: 5px 8px;
            border: 1px solid rgba(37, 99, 235, 0.16);
            border-radius: 6px;
            background: rgba(37, 99, 235, 0.07);
            color: #1d4ed8;
            white-space: normal;
            line-height: 1.25;
         }
         @media (max-width: 1280px) {
            .dashboardplus-about-card-grid {
               grid-template-columns: repeat(2, minmax(0, 1fr));
            }
         }
         @media (max-width: 860px) {
            .dashboardplus-about-card-grid {
               grid-template-columns: 1fr;
            }
         }
      </style>";
   }

   private static function showHeader(): void
   {
      echo "<div class='dashboardplus-config-header'>";
      echo "<h1><i class='ti ti-info-circle'></i> " . __('Sobre o Dashboard Plus', 'dashboardplus') . "</h1>";
      echo "<div class='dashboardplus-header-actions'>";
      echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/dashboard.php') . "'>";
      echo "<i class='ti ti-arrow-left'></i> " . __('Voltar ao painel', 'dashboardplus');
      echo "</a>";
      if (Config::canAdmin()) {
         echo "<a class='btn btn-outline-secondary' href='" . Config::pluginUrl('/front/config.form.php') . "'>";
         echo "<i class='ti ti-settings'></i> " . __('Configurações', 'dashboardplus');
         echo "</a>";
      }
      echo "</div>";
      echo "</div>";
   }

   private static function showTabbedContent(): void
   {
      $tabs = [
         'overview'      => ['icon' => 'ti ti-activity', 'label' => __('Visão Geral', 'dashboardplus')],
         'attendance'    => ['icon' => 'ti ti-headset', 'label' => __('Atendimento', 'dashboardplus')],
         'sla'           => ['icon' => 'ti ti-clock-check', 'label' => __('SLA', 'dashboardplus')],
         'satisfaction'  => ['icon' => 'ti ti-star', 'label' => __('Satisfação', 'dashboardplus')],
         'tasks'         => ['icon' => 'ti ti-list-check', 'label' => __('Tarefas', 'dashboardplus')],
         'assets'        => ['icon' => 'ti ti-devices', 'label' => __('Ativos', 'dashboardplus')],
         'distributions' => ['icon' => 'ti ti-route', 'label' => __('Distribuições', 'dashboardplus')],
         'capacity'      => ['icon' => 'ti ti-users-group', 'label' => __('Capacidade', 'dashboardplus')],
      ];
      $active_tab = self::activeTab(array_keys($tabs));

      echo "<div class='dashboardplus-tabs dashboardplus-about-tabs' role='tablist'>";
      foreach ($tabs as $key => $tab) {
         $is_active = $key === $active_tab;
         $active = $is_active ? ' active' : '';
         $selected = $is_active ? 'true' : 'false';
         $safe_key = Html::cleanInputText($key);
         echo "<a class='dashboardplus-tab{$active}' href='" . Config::pluginUrl('/front/about.php') . "?tab={$safe_key}' data-dashboardplus-tab='{$safe_key}' aria-selected='{$selected}'>";
         echo "<i class='" . Html::cleanInputText($tab['icon']) . "'></i>";
         echo "<span>" . Html::cleanInputText($tab['label']) . "</span>";
         echo "</a>";
      }
      echo "</div>";

      self::panel('overview', $active_tab === 'overview', [
         [__('Objetivo da seção', 'dashboardplus'), self::purposeItems()],
         [__('Regras gerais de cálculo', 'dashboardplus'), self::globalRuleItems()],
         [__('Como interpretar os números', 'dashboardplus'), self::interpretationRuleItems()],
      ]);
      self::panel('attendance', $active_tab === 'attendance', [
         [__('Indicadores de chamados', 'dashboardplus'), self::ticketIndicatorItems()],
      ]);
      self::panel('sla', $active_tab === 'sla', [
         [__('Indicadores de SLA', 'dashboardplus'), self::slaIndicatorItems()],
      ]);
      self::panel('satisfaction', $active_tab === 'satisfaction', [
         [__('Indicadores de satisfação', 'dashboardplus'), self::satisfactionIndicatorItems()],
      ]);
      self::panel('tasks', $active_tab === 'tasks', [
         [__('Indicadores de tarefas', 'dashboardplus'), self::taskIndicatorItems()],
      ]);
      self::panel('assets', $active_tab === 'assets', [
         [__('Indicadores de ativos', 'dashboardplus'), self::assetIndicatorItems()],
      ]);
      self::panel('distributions', $active_tab === 'distributions', [
         [__('Indicadores de distribuição', 'dashboardplus'), self::distributionIndicatorItems()],
      ]);
      self::panel('capacity', $active_tab === 'capacity', [
         [__('Carga operacional e capacidade', 'dashboardplus'), self::capacityIndicatorItems()],
      ]);
   }

   private static function activeTab(array $allowed): string
   {
      $tab = isset($_GET['tab']) ? (string) $_GET['tab'] : 'overview';
      return in_array($tab, $allowed, true) ? $tab : 'overview';
   }

   private static function purposeItems(): array
   {
      return [
         self::card('ti ti-calculator', __('Cálculos transparentes', 'dashboardplus'), __('Cada aba mostra a lógica dos principais indicadores em linguagem simples.', 'dashboardplus')),
         self::card('ti ti-database', __('Sem duplicar regras', 'dashboardplus'), __('O plugin lê dados nativos do GLPI e dos plugins integrados, sem recriar a regra de negócio.', 'dashboardplus')),
         self::card('ti ti-shield-check', __('Leitura com contexto', 'dashboardplus'), __('Os indicadores apoiam gestão operacional. Evite conclusões individuais sem olhar fila, entidade, SLA, prioridade e escala.', 'dashboardplus'), null, 'warning'),
      ];
   }

   private static function globalRuleItems(): array
   {
      return [
         self::card('ti ti-filter', __('Filtros globais', 'dashboardplus'), __('Período, datas, entidade, recursividade, grupo, técnico, categoria, tipo e prioridade limitam os dados exibidos.', 'dashboardplus')),
         self::card('ti ti-building', __('Entidade e perfil', 'dashboardplus'), __('A consulta respeita entidade ativa, entidades visíveis, perfil do usuário e permissões por aba.', 'dashboardplus')),
         self::card('ti ti-calendar-search', __('Data certa por métrica', 'dashboardplus'), __('Abertos usam abertura; solucionados usam solução; fechados usam fechamento; satisfação usa data da pesquisa.', 'dashboardplus')),
         self::card('ti ti-ban', __('Sem consulta desnecessária', 'dashboardplus'), __('Aba ou widget desabilitado não deve aparecer nem executar AJAX.', 'dashboardplus')),
         self::card('ti ti-list-numbers', __('Rankings limitados', 'dashboardplus'), __('Listas e rankings usam limite para proteger bases grandes.', 'dashboardplus')),
      ];
   }

   private static function ticketIndicatorItems(): array
   {
      return [
         self::card('ti ti-inbox', __('Abertos', 'dashboardplus'), __('Chamados ainda não solucionados ou fechados.', 'dashboardplus'), __('status em chamados não resolvidos', 'dashboardplus')),
         self::card('ti ti-tags', __('Por status', 'dashboardplus'), __('Novos, atribuídos, planejados e pendentes são contados pelo status nativo do GLPI.', 'dashboardplus')),
         self::card('ti ti-check', __('Solucionados', 'dashboardplus'), __('Chamados com data de solução dentro do período filtrado.', 'dashboardplus'), __('solvedate no período', 'dashboardplus')),
         self::card('ti ti-lock-check', __('Fechados', 'dashboardplus'), __('Chamados com data de fechamento dentro do período filtrado.', 'dashboardplus'), __('closedate no período', 'dashboardplus')),
         self::card('ti ti-alert-triangle', __('Atrasados', 'dashboardplus'), __('Chamados ativos cujo prazo de solução/SLA já venceu.', 'dashboardplus'), null, 'danger'),
         self::card('ti ti-chart-line', __('Evolução diária', 'dashboardplus'), __('Recebidos usam abertura; solucionados e encerrados usam suas respectivas datas de conclusão.', 'dashboardplus')),
         self::card('ti ti-chart-bar', __('Distribuições', 'dashboardplus'), __('Status, prioridade, categoria, grupo, técnico, localização e entidade são agregações diretas do GLPI.', 'dashboardplus')),
      ];
   }

   private static function slaIndicatorItems(): array
   {
      return [
         self::card('ti ti-clock-check', __('Solução cumprida', 'dashboardplus'), __('Chamados resolvidos dentro do prazo de solução.', 'dashboardplus'), __('solução <= prazo', 'dashboardplus')),
         self::card('ti ti-clock-exclamation', __('Solução violada', 'dashboardplus'), __('Chamados resolvidos após o prazo ou ainda ativos com prazo vencido.', 'dashboardplus'), __('solução > prazo ou prazo < agora', 'dashboardplus'), 'danger'),
         self::card('ti ti-message-check', __('Resposta cumprida', 'dashboardplus'), __('Primeira resposta dentro do prazo de atendimento/resposta, quando o dado existir.', 'dashboardplus')),
         self::card('ti ti-user', __('SLA por técnico', 'dashboardplus'), __('Agrupa chamados atribuídos por técnico e calcula cumpridos, violados e taxa.', 'dashboardplus')),
         self::card('ti ti-category', __('SLA por categoria', 'dashboardplus'), __('Agrupa chamados por categoria e calcula cumpridos, violados e taxa.', 'dashboardplus')),
         self::card('ti ti-hourglass', __('Tempo médio', 'dashboardplus'), __('Média entre abertura e solução/fechamento para chamados encerrados.', 'dashboardplus')),
      ];
   }

   private static function satisfactionIndicatorItems(): array
   {
      return [
         self::card('ti ti-chart-donut', __('Satisfação geral', 'dashboardplus'), __('Mostra todas as pesquisas consideradas, incluindo não respondidas quando aplicável.', 'dashboardplus')),
         self::card('ti ti-star', __('Por nota', 'dashboardplus'), __('Mostra somente pesquisas respondidas com nota registrada.', 'dashboardplus')),
         self::card('ti ti-calculator', __('Média', 'dashboardplus'), __('Soma das notas respondidas dividida pela quantidade de respostas.', 'dashboardplus'), __('notas / respostas', 'dashboardplus')),
         self::card('ti ti-percentage', __('Taxa de resposta', 'dashboardplus'), __('Pesquisas respondidas divididas pelo total de pesquisas consideradas.', 'dashboardplus'), __('respondidas / total', 'dashboardplus')),
         self::card('ti ti-users', __('Por grupo e categoria', 'dashboardplus'), __('Agrupa média e volume por grupo de atendimento ou categoria do chamado.', 'dashboardplus')),
         self::card('ti ti-message-dots', __('Comentários', 'dashboardplus'), __('Lista respostas com comentário dentro do período e escopo filtrado.', 'dashboardplus')),
      ];
   }

   private static function capacityIndicatorItems(): array
   {
      return [
         self::card('ti ti-gauge', __('Índice de carga', 'dashboardplus'), __('É um índice de pontos para gestão de risco operacional. Não é percentual de ocupação em horas.', 'dashboardplus'), null, 'warning'),
         self::card('ti ti-ticket', __('Carga simples', 'dashboardplus'), __('Quantidade de chamados ativos atribuídos ao técnico.', 'dashboardplus'), __('chamados ativos', 'dashboardplus')),
         self::card('ti ti-weight', __('Carga ponderada', 'dashboardplus'), __('Soma dos pesos de prioridade, idade do chamado e risco de SLA.', 'dashboardplus'), __('prioridade + idade + SLA', 'dashboardplus')),
         self::card('ti ti-clock', __('Normalização por escala', 'dashboardplus'), __('Quando há escala, a carga é ajustada pela capacidade disponível do técnico.', 'dashboardplus'), __('carga ponderada / fator de capacidade', 'dashboardplus')),
         self::card('ti ti-calendar-time', __('Fator de capacidade', 'dashboardplus'), __('Horas disponíveis divididas pela jornada semanal de referência.', 'dashboardplus'), __('20h / 40h = 0,50', 'dashboardplus')),
         self::card('ti ti-stairs', __('Níveis padrão', 'dashboardplus'), __('Baixa até 25; Moderada até 50; Alta até 75; Crítica acima de 75.', 'dashboardplus')),
         self::card('ti ti-alert-circle', __('Crítica', 'dashboardplus'), __('Indica necessidade de analisar redistribuição, prioridade ou SLA. Não significa baixa produtividade.', 'dashboardplus'), null, 'danger'),
      ];
   }

   private static function assetIndicatorItems(): array
   {
      return [
         self::card('ti ti-devices', __('Totais de ativos', 'dashboardplus'), __('Conta computadores, monitores, impressoras e telefones cadastrados no inventário GLPI.', 'dashboardplus')),
         self::card('ti ti-building-store', __('Fabricante e tipo', 'dashboardplus'), __('Agrupa computadores e monitores por fabricante, tipo e classificação disponível.', 'dashboardplus')),
         self::card('ti ti-cpu', __('Sistema e hardware', 'dashboardplus'), __('Agrupa computadores por sistema operacional e processador quando os dados existem.', 'dashboardplus')),
         self::card('ti ti-map-pin', __('Mapa de São Paulo', 'dashboardplus'), __('Marca cidades a partir da localização associada aos ativos.', 'dashboardplus')),
      ];
   }

   private static function taskIndicatorItems(): array
   {
      return [
         self::card('ti ti-list-check', __('Tarefas por técnico', 'dashboardplus'), __('Agrupa tarefas vinculadas a chamados por técnico responsável quando disponível.', 'dashboardplus')),
         self::card('ti ti-clock-record', __('Esforço registrado', 'dashboardplus'), __('Quando há duração informada, consolida tempo registrado por técnico.', 'dashboardplus')),
         self::card('ti ti-clipboard-data', __('Qualidade do dado', 'dashboardplus'), __('Sem tarefas preenchidas, os indicadores podem ficar zerados ou pouco representativos.', 'dashboardplus'), null, 'warning'),
         self::card('ti ti-users-group', __('Diferença para Capacidade', 'dashboardplus'), __('Capacidade não depende obrigatoriamente de tarefas; usa chamados ativos e pesos operacionais.', 'dashboardplus')),
      ];
   }

   private static function distributionIndicatorItems(): array
   {
      return [
         self::card('ti ti-route', __('Distribuições', 'dashboardplus'), __('Agrupa dados de atribuição, distribuidores, técnicos, categorias e transferências.', 'dashboardplus')),
         self::card('ti ti-robot', __('Taxa de automação', 'dashboardplus'), __('Chamados distribuídos automaticamente divididos pelo total distribuído.', 'dashboardplus'), __('automáticos / total', 'dashboardplus')),
         self::card('ti ti-arrows-transfer-up', __('Transferências', 'dashboardplus'), __('Mostra volume de chamados transferidos por entidade ou fluxo disponível.', 'dashboardplus')),
         self::card('ti ti-user-cog', __('Top distribuidores', 'dashboardplus'), __('Ranking limitado por usuários responsáveis pela distribuição no escopo analisado.', 'dashboardplus')),
      ];
   }

   private static function interpretationRuleItems(): array
   {
      return [
         self::card('ti ti-trending-up', __('Alto não é sempre ruim', 'dashboardplus'), __('Volume alto pode refletir demanda, sazonalidade, entidade maior ou regra de fila.', 'dashboardplus')),
         self::card('ti ti-trending-down', __('Baixo não é sempre bom', 'dashboardplus'), __('Volume baixo pode indicar filtro restrito, baixa adesão ou dado incompleto.', 'dashboardplus')),
         self::card('ti ti-scale', __('Compare contexto', 'dashboardplus'), __('Analise tendência, proporção, prioridade, idade, SLA, escala, categoria e entidade.', 'dashboardplus')),
         self::card('ti ti-heart-handshake', __('Foco de gestão', 'dashboardplus'), __('Use Capacidade para equilibrar fila e proteger SLA, não como ranking de pessoas.', 'dashboardplus'), null, 'warning'),
      ];
   }

   private static function card(string $icon, string $title, string $text, ?string $formula = null, string $tone = 'default'): array
   {
      return [
         'icon'    => $icon,
         'title'   => $title,
         'text'    => $text,
         'formula' => $formula,
         'tone'    => $tone,
      ];
   }

   private static function panel(string $key, bool $active, array $sections): void
   {
      $active_class = $active ? ' active' : '';
      echo "<div class='dashboardplus-panel{$active_class}' data-dashboardplus-panel='" . Html::cleanInputText($key) . "'>";
      foreach ($sections as $section) {
         self::section($section[0], $section[1]);
      }
      echo "</div>";
   }

   private static function section(string $title, array $items): void
   {
      echo "<section class='dashboardplus-config-section dashboardplus-about-section'>";
      echo "<h2>" . Html::cleanInputText($title) . "</h2>";
      echo "<div class='dashboardplus-about-card-grid'>";
      foreach ($items as $item) {
         self::renderCard($item);
      }
      echo "</div>";
      echo "</section>";
   }

   private static function renderCard($item): void
   {
      if (!is_array($item)) {
         echo "<article class='dashboardplus-about-card'>";
         echo "<p>" . Html::cleanInputText((string) $item) . "</p>";
         echo "</article>";
         return;
      }

      $tone = in_array($item['tone'] ?? 'default', ['default', 'warning', 'danger'], true) ? $item['tone'] : 'default';
      echo "<article class='dashboardplus-about-card dashboardplus-about-card-{$tone}'>";
      echo "<div class='dashboardplus-about-card-icon'><i class='" . Html::cleanInputText((string) ($item['icon'] ?? 'ti ti-info-circle')) . "'></i></div>";
      echo "<div>";
      echo "<h3>" . Html::cleanInputText((string) ($item['title'] ?? '')) . "</h3>";
      echo "<p>" . Html::cleanInputText((string) ($item['text'] ?? '')) . "</p>";
      if (!empty($item['formula'])) {
         echo "<code>" . Html::cleanInputText((string) $item['formula']) . "</code>";
      }
      echo "</div>";
      echo "</article>";
   }
}
