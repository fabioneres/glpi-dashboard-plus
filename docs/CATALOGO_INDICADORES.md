# Catálogo de Indicadores - Dashboard Plus

## Regra geral

Todos os indicadores devem respeitar:

- permissões do Dashboard Plus;
- permissão de leitura de chamados quando o indicador usa chamados;
- perfil ativo do GLPI;
- entidades visíveis, entidade filtrada e recursividade;
- período selecionado no servidor;
- filtros globais aplicáveis.

O período `Todo histórico` usa `1970-01-01 00:00:00` até a data final atual. Isso mantém filtro de data nas consultas e permite considerar chamados antigos, inclusive do chamado 1 ao atual, desde que o usuário tenha permissão e entidade visível.

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
