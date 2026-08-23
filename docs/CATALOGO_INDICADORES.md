# Catálogo de Indicadores - Dashboard Plus

## Regra geral

Todos os indicadores devem respeitar:

- permissões do Dashboard Plus;
- permissão da aba do Dashboard Plus associada ao indicador;
- permissão de leitura de chamados quando o indicador usa chamados;
- perfil ativo do GLPI;
- entidades visíveis, entidade filtrada e recursividade;
- período selecionado no servidor;
- filtros globais aplicáveis.

O período `Todo histórico` usa `1970-01-01 00:00:00` até a data final atual. Isso mantém filtro de data nas consultas e permite considerar chamados antigos, inclusive do chamado 1 ao atual, desde que o usuário tenha permissão e entidade visível.

## Permissões por aba

O Dashboard Plus possui direitos por perfil para controlar a exibição e o carregamento AJAX das abas:

- Aba Visão Geral.
- Aba Atendimento.
- Aba SLA.
- Aba Satisfação.
- Aba Tarefas.
- Aba Ativos.
- Aba Distribuições.

As permissões de aba não substituem a permissão geral de visualizar o Dashboard Plus. Um usuário precisa ter a permissão geral do plugin e a permissão da aba correspondente; administradores do plugin podem visualizar todas as abas.

## Chamados

| Indicador | Código | Classificação | Data de referência | Fonte | Observação |
|---|---|---|---|---|---|
| Chamados abertos | `tickets_open` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Mede chamados criados no período. |
| Chamados novos | `tickets_new` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Mede chamados criados no período que permanecem em status Novo. |
| Chamados atribuídos | `tickets_assigned` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Mede chamados criados no período que permanecem atribuídos. |
| Chamados planejados | `tickets_planned` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Mede chamados criados no período que permanecem planejados. |
| Chamados pendentes | `tickets_pending` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Mede chamados criados no período que permanecem pendentes. |
| Chamados solucionados | `tickets_solved` | Nativo | `glpi_tickets.solvedate` | `glpi_tickets` | Mede evento de solução no período, mesmo que o chamado tenha sido aberto antes. |
| Chamados fechados | `tickets_closed` | Nativo | `glpi_tickets.closedate` | `glpi_tickets` | Mede evento de fechamento no período, separado da solução. |
| Chamados atrasados | `tickets_late` | Derivado | `glpi_tickets.date` | `glpi_tickets` + cálculo SLA/OLA GLPI | Mede chamados não solucionados com prazo excedido. |
| Evolução mensal/anual | `tickets_monthly_evolution` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Usa mês para períodos até 3 anos e ano para períodos maiores. |
| Tickets recebidos por dia | `tickets_received_by_day` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Série diária limitada para leitura e performance. |
| Tickets solucionados / encerrados por dia | `tickets_solved_closed_by_day` | Nativo | `glpi_tickets.solvedate` e `glpi_tickets.closedate` | `glpi_tickets` | Soma solucionados e fechados por dia, mantendo valores separados no rótulo. |
| Nº de tickets em aberto por dia | `tickets_open_by_day` | Derivado | `glpi_tickets.date` | `glpi_tickets` | Série diária dos chamados ainda não solucionados, limitada aos últimos 30 dias do período. |
| Tickets não atribuídos | `tickets_unassigned` | Derivado | `glpi_tickets.date` | `glpi_tickets`, `glpi_tickets_users`, `glpi_groups_tickets` | Conta chamados não solucionados sem técnico e sem grupo atribuído. |
| Solucionados hoje | `tickets_solved_today` | Nativo | `glpi_tickets.solvedate` | `glpi_tickets` | Conta soluções na data atual, respeitando entidade e filtros globais aplicáveis. |
| Fila de notificações | `notification_queue` | Nativo GLPI | `glpi_queuednotifications.sent_time` | `glpi_queuednotifications` | Conta notificações ainda não enviadas quando a tabela existe no GLPI. |
| Prioridade média | `tickets_priority_medium` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Conta chamados de prioridade média. |
| Prioridade alta | `tickets_priority_high` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Conta chamados de prioridade alta ou muito alta. |
| Prioridade crítica | `tickets_priority_critical` | Nativo | `glpi_tickets.date` | `glpi_tickets` | Conta chamados de prioridade crítica. |
| Tickets por localização | `tickets_by_location` | Nativo | `glpi_tickets.date` | `glpi_tickets`, `glpi_locations` | Ranking limitado por localização do chamado. |

## SLA

| Indicador | Código | Classificação | Data de referência | Fonte | Observação |
|---|---|---|---|---|---|
| SLA cumprido x violado | `sla_compliance` | Derivado | `glpi_tickets.date` | `glpi_tickets.time_to_resolve`, `glpi_tickets.solvedate` | Usa a mesma regra de comparação aplicada nos recortes detalhados. |
| SLA de atendimento | `sla_response_compliance` | Derivado | `glpi_tickets.date` | `glpi_tickets.time_to_own`, `glpi_tickets.takeintoaccount_delay_stat` | Mede cumprimento do prazo de atendimento. |
| SLA por técnico | `sla_by_technician` | Derivado | `glpi_tickets.date` | `glpi_tickets`, `glpi_tickets_users`, `glpi_users` | Ranking limitado por técnico atribuído, com total, cumpridos, violados, sem meta e percentual. |
| SLA por categoria | `sla_by_category` | Derivado | `glpi_tickets.date` | `glpi_tickets`, `glpi_itilcategories` | Ranking limitado por categoria, com total, cumpridos, violados, sem meta e percentual. |

## Satisfação

| Indicador | Código | Classificação | Data de abertura | Data da pesquisa | Fonte | Observação |
|---|---|---|---|---|---|---|
| Nota média de satisfação | `satisfaction_average` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, `glpi_ticketsatisfactions` | Considera chamados abertos no período global e respostas dentro do período de pesquisa. |
| Pesquisas respondidas | `satisfaction_answered_count` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, `glpi_ticketsatisfactions` | Conta respostas válidas no período de pesquisa. |
| Percentual de pesquisas respondidas | `satisfaction_response_rate` | Derivado | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, `glpi_ticketsatisfactions` | Total usa data de abertura; respondidas usam data da pesquisa. |
| Satisfação por nota | `satisfaction_breakdown` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_ticketsatisfactions` | Ranking por nota, limitado ao período de pesquisa. |
| Satisfação geral | `satisfaction_general_breakdown` | Derivado | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, `glpi_ticketsatisfactions` | Exibe não respondidos quando não há resposta válida dentro do período de pesquisa. |
| Média por grupo | `satisfaction_by_group_average` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, grupos e satisfação | Ranking limitado para evitar consulta ampla. |
| Categoria x pesquisa de satisfação | `satisfaction_by_category_summary` | Derivado | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, categorias e satisfação | Total de chamados usa abertura; respondidas e média usam data da pesquisa. |
| Pesquisa satisfação x comentário | `satisfaction_comments` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_tickets`, usuários e satisfação | Lista limitada, ordenada pela resposta mais recente. |
| Pesquisas respondidas por mês | `satisfaction_answered_by_month` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_ticketsatisfactions` | Agrupa por mês da resposta da pesquisa. |
| Média de satisfação por mês | `satisfaction_average_by_month` | Nativo | `glpi_tickets.date` | `glpi_ticketsatisfactions.date_answered` | `glpi_ticketsatisfactions` | Agrupa por mês da resposta da pesquisa. |

## Ativos

| Indicador | Código | Classificação | Fonte | Observação |
|---|---|---|---|---|
| Total de computadores | `asset_total_computers` | Nativo | `glpi_computers` | Respeita entidade e recursividade. |
| Total de monitores | `asset_total_monitors` | Nativo | `glpi_monitors` | Respeita entidade e recursividade. |
| Total de impressoras | `asset_total_printers` | Nativo | `glpi_printers` | Respeita entidade e recursividade. |
| Total de telefones | `asset_total_phones` | Nativo | `glpi_phones` | Respeita entidade e recursividade. |
| Computadores por fabricante | `asset_computers_by_manufacturer` | Nativo | `glpi_computers`, `glpi_manufacturers` | Ranking limitado. |
| Monitor por fabricante | `asset_monitors_by_manufacturer` | Nativo | `glpi_monitors`, `glpi_manufacturers` | Ranking limitado. |
| Computadores por tipo | `asset_computers_by_type` | Nativo | `glpi_computers`, `glpi_computertypes` | Ranking limitado. |
| Computadores por localização | `asset_computers_by_location` | Nativo | `glpi_computers`, `glpi_locations` | Ranking limitado. |
| Computadores por sistema operacional | `asset_computers_by_os` | Nativo | `glpi_computers`, `glpi_items_operatingsystems`, `glpi_operatingsystems` | Retorna vazio se não houver inventário de SO relacionado. |
| Dispositivos por CPU | `asset_computers_by_cpu` | Nativo | `glpi_computers`, `glpi_items_deviceprocessors`, `glpi_deviceprocessors` | Retorna vazio se não houver inventário de processador relacionado. |
| Mapa de São Paulo | `asset_computers_sp_map` | Derivado | `glpi_computers`, `glpi_locations` | Marca cidades paulistas conforme localização cadastrada. |

## Distribuições

| Indicador | Código | Classificação | Data de referência | Fonte | Observação |
|---|---|---|---|---|---|
| Chamados distribuídos | `distribution_distinct_tickets` | Derivado | `date_creation` do log | `glpi_plugin_atribuicaointeligente_distribution_logs` | Conta chamados distintos com eventos de distribuição. |
| Taxa de automação | `distribution_automation_rate` | Derivado | `date_creation` do log | logs de distribuição | Classifica eventos como automação integral, parcial ou atuação manual. |
| Transferências de entidade | `distribution_transfer_tickets` | Derivado | `date_creation` do log | logs de distribuição | Usa ação `entity_transferred`. |

## Pendências

Ainda exigem definição formal antes de implementação completa:

- primeira resposta;
- produtividade;
- cobertura do inventário;
- completude da CMDB;
- hardware abaixo do padrão;
- sistemas operacionais homologados;
- metas institucionais;
- risco preventivo de violação de SLA.
