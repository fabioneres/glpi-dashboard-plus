# Relatorio tecnico - Plugin 006 Dashboard Plus

## 1. Contexto do projeto

O plugin `dashboardplus` foi iniciado como o **Plugin 006 - Dashboard Plus** para GLPI 10.0.25. O objetivo e modernizar e expandir o conceito de dashboard do GLPI com uma arquitetura modular de widgets, sem alterar o core, sem duplicar dados e sem recriar regras de negocio dos demais plugins do ecossistema.

O plugin foi pensado como agregador de indicadores. A fonte principal do MVP e o proprio GLPI, especialmente `glpi_tickets` e tabelas relacionadas a chamados, grupos, usuarios, categorias e SLA.

Referencia funcional citada pelo usuario: plugin Dashboard do Stevenes Donato. A decisao adotada foi usar essa referencia como inspiracao de experiencia e objetivo funcional, mas implementar uma estrutura mais atual para GLPI 10, com carregamento AJAX, permissoes, cache, separacao de widgets e configuracao visual por widget.

## 2. Ambiente conhecido

- Caminho local atual do plugin: `C:\Projetos\glpi\plugins\meusplugins\dashboardplus`
- Caminho do GLPI na VM: `/var/www/html/glpi`
- Host da VM usado nos testes: `192.168.159.129`
- Compatibilidade GLPI declarada: `10.0.24+`
- Versao atual do plugin: `1.0.0`

Observacao de seguranca: credenciais de acesso SSH/GLPI foram usadas durante os testes, mas nao foram registradas neste relatorio.

## 3. Regras obrigatorias consideradas

- Nao alterar o core do GLPI.
- Seguir padroes de plugin GLPI/Teclib.
- Consumir dados existentes em vez de duplicar dados.
- Nao recriar regras de negocio de outros plugins.
- Respeitar perfil, entidade e permissoes desde o inicio.
- Tratar multi-entidade no contexto de consulta.
- Evitar acesso direto indevido por URL.
- Registrar falhas sem quebrar a tela inicial.
- Evitar consultas sem periodo, sem filtro de entidade ou sem limite.
- Preparar arquitetura para widgets externos futuramente.
- Manter o MVP sem integracoes profundas com os demais plugins.

## 4. Estrutura principal do plugin

Arquivos principais:

- `setup.php`: inicializacao, hooks, CSS/JS, menu, config page, prerequisitos de versao GLPI.
- `hook.php`: install, upgrade e uninstall.
- `bootstrap.php`: constantes, autoload e inicializacao basica.
- `composer.json`: metadados/autoload.
- `install/install.sql`: criacao das tabelas do plugin.
- `install/uninstall.sql`: remocao das tabelas.
- `front/dashboard.php`: pagina principal do dashboard.
- `front/config.form.php`: tela de configuracao.
- `front/widget.ajax.php`: endpoint AJAX para carregar widgets.
- `css/dashboardplus.css`: layout visual do dashboard.
- `js/dashboardplus.js`: carregamento AJAX, lazy loading e auto-refresh.
- `tests/vm_validate.php`: validador de instalacao/configuracao na VM.
- `tests/TEST_PLAN.md`: plano de testes manual/regressao.

Classes principais:

- `GlpiPlugin\Dashboardplus\Config`
- `GlpiPlugin\Dashboardplus\ConfigPage`
- `GlpiPlugin\Dashboardplus\Dashboard`
- `GlpiPlugin\Dashboardplus\DashboardContext`
- `GlpiPlugin\Dashboardplus\Installer`
- `GlpiPlugin\Dashboardplus\Logger`
- `GlpiPlugin\Dashboardplus\Menu`
- `GlpiPlugin\Dashboardplus\Profile`
- `GlpiPlugin\Dashboardplus\Cache\DashboardCache`
- `GlpiPlugin\Dashboardplus\Provider\TicketMetricsProvider`
- `GlpiPlugin\Dashboardplus\Widget\WidgetInterface`
- `GlpiPlugin\Dashboardplus\Widget\WidgetRegistry`
- `GlpiPlugin\Dashboardplus\Widget\WidgetRenderer`

## 5. Banco de dados

O plugin cria apenas tabelas de configuracao. Nenhum dado operacional do GLPI e duplicado.

### `glpi_plugin_dashboardplus_configs`

Tabela de configuracao global.

Campos:

- `id`
- `default_period_days`
- `auto_refresh`
- `refresh_interval`
- `use_cache`
- `cache_ttl`
- `date_creation`
- `date_mod`

Configuracao padrao:

- periodo padrao: 30 dias
- auto-refresh: habilitado
- intervalo de refresh: 300 segundos
- cache: habilitado
- TTL do cache: 120 segundos

### `glpi_plugin_dashboardplus_configentities`

Tabela para limitar entidades consideradas no dashboard.

Campos:

- `id`
- `plugin_dashboardplus_configs_id`
- `entities_id`
- `is_recursive`

Indices:

- unicidade por `plugin_dashboardplus_configs_id` + `entities_id`
- indice `idx_entity` em `entities_id`

### `glpi_plugin_dashboardplus_widgetconfigs`

Tabela de configuracao por widget.

Campos:

- `id`
- `widget_key`
- `is_enabled`
- `display_order`
- `width`
- `height`
- `config`
- `date_creation`
- `date_mod`

Indices:

- unicidade por `widget_key`
- indice `idx_enabled_order` para carregar widgets habilitados em ordem

O campo `config` armazena JSON. Hoje ele guarda principalmente a visualizacao do widget, por exemplo:

```json
{"visualization":"columns"}
```

## 6. Permissoes

Foram criados quatro direitos de perfil:

- `plugin_dashboardplus_view`: visualizar Dashboard Plus.
- `plugin_dashboardplus_admin`: administrar Dashboard Plus.
- `plugin_dashboardplus_widgets`: configurar widgets.
- `plugin_dashboardplus_global`: visualizar indicadores globais.

Implementacao:

- `Config::canView()`
- `Config::canAdmin()`
- `Config::canConfigureWidgets()`
- `Config::canViewGlobalIndicators()`
- `Profile::getAllRights()`
- `Profile::initProfile()`

O primeiro perfil ativo durante a instalacao recebe acesso inicial por `Profile::createFirstAccess()`, para evitar instalar o plugin e ficar sem acesso administrativo.

## 7. Modelo de widgets

Cada widget e uma classe isolada e implementa `WidgetInterface`.

Tipos atuais:

- `metric`: cards numericos principais.
- `breakdown`: distribuicoes/rankings.
- `ratio`: proporcoes comparativas.

Registro:

- Os widgets internos ficam listados em `WidgetRegistry::getWidgetClasses()`.
- Widgets externos podem ser adicionados futuramente com `WidgetRegistry::registerWidgetClass()`.

Carregamento:

- `Dashboard::show()` separa widgets metricos e detalhados.
- Cada card e renderizado inicialmente como placeholder.
- O JS busca o conteudo em `front/widget.ajax.php`.
- O endpoint AJAX valida login, permissao, existencia do widget e se o widget esta habilitado.

Renderizacao:

- `WidgetRenderer::render()` escolhe a visualizacao com base no tipo do widget e na configuracao salva.
- Visualizacoes disponiveis:
  - `card`
  - `compact`
  - `bars`
  - `columns`
  - `table`
  - `pie`
  - `donut`
  - `ratio`

## 8. Widgets implementados

Metrica:

- `tickets_open`: chamados abertos.
- `tickets_new`: chamados novos.
- `tickets_assigned`: chamados atribuidos.
- `tickets_planned`: chamados planejados.
- `tickets_pending`: chamados pendentes.
- `tickets_solved`: chamados solucionados.
- `tickets_closed`: chamados fechados.
- `tickets_late`: chamados atrasados.

Indicadores detalhados:

- `tickets_resolution_ratio`: nao solucionados x solucionados/fechados.
- `tickets_monthly_evolution`: evolucao de chamados por mes/ano.
- `tickets_by_status`: chamados por status.
- `tickets_by_priority`: chamados por prioridade.
- `tickets_by_category`: chamados por categoria.
- `tickets_by_group`: chamados por grupo tecnico.
- `tickets_by_technician`: chamados por tecnico.
- `sla_compliance`: SLA cumprido x violado.

Total atual esperado no validador: 16 widgets.

## 9. Consultas e dados

A classe central de dados e `TicketMetricsProvider`.

Padroes adotados:

- Sempre usar `Ticket::getTable()`.
- Sempre filtrar `is_deleted = 0`.
- Sempre filtrar periodo por `glpi_tickets.date`.
- Sempre aplicar entidade com `DashboardContext::getEntityCriteria()`.
- Sempre mesclar criterio de perfil com `Ticket::getCriteriaFromProfile()`.
- Usar `DBConnection::getReadConnection()` quando disponivel.
- Usar `COUNT DISTINCT` para reduzir duplicidade quando houver joins.

Consultas de ranking possuem `LIMIT`, por exemplo categoria, grupo e tecnico.

### Evolucao mensal

Implementada em `TicketMetricsProvider::monthlyEvolution()`.

Caracteristicas:

- Agrupa por `DATE_FORMAT(glpi_tickets.date, '%Y-%m')`.
- Usa abertura do chamado como base.
- Respeita periodo global superior.
- Respeita entidade e permissao.
- Preenche meses sem chamados com zero usando `getMonthBuckets()`.
- Gera labels no formato `m/Y`.
- Cada mes tem link para pesquisa de chamados no GLPI com periodo especifico daquele mes.

## 10. Interface

Tela principal:

- Titulo `Dashboard Plus`.
- Filtros superiores:
  - periodo em dias
  - data inicial
  - data final
  - entidade
  - recursivo
- Botao aplicar.
- Botao configuracoes para administradores.
- Secao `Metricas principais`.
- Secao `Indicadores detalhados`.

Tela de configuracao:

- Comportamento geral:
  - periodo padrao
  - intervalo de atualizacao automatica
  - tempo de cache
  - habilitar/desabilitar auto-refresh
  - habilitar/desabilitar cache
- Escopo de entidades:
  - entidades consideradas
  - incluir entidades filhas
- Widgets:
  - habilitar/desabilitar
  - visualizacao
  - ordem

## 11. Evolucoes implementadas durante o desenvolvimento

### Traducao para portugues do Brasil

O plugin foi ajustado para exibir textos em portugues do Brasil nas telas, menus, mensagens e widgets.

Ponto pendente: criar catalogo formal em `locales/` para distribuicao mais completa de i18n.

### Personalizacao de tipo de grafico

Foi adicionada selecao de visualizacao por widget.

O valor e salvo em `glpi_plugin_dashboardplus_widgetconfigs.config` como JSON.

### Barras verticais

Foi adicionada a visualizacao `columns`.

Uso atual:

- `tickets_by_status` usa barras verticais por padrao.
- `tickets_monthly_evolution` usa barras verticais por padrao.
- Widgets de distribuicao podem escolher barras verticais.
- Widgets ratio tambem podem usar barras verticais.

### Cores nativas por status

Os widgets de status usam cores alinhadas ao GLPI:

- novo/atribuido: verde
- planejado: azul escuro
- pendente: laranja
- solucionado/fechado: preto

Tambem sao usados metadados nativos como `Ticket::getStatusClass()` e `Ticket::getStatusKey()`.

### Indicador nao solucionados x solucionados/fechados

Foi criado `TicketsResolutionRatioWidget`.

Ele usa o periodo global do topo, sem filtro de periodo dentro do proprio widget.

### Evolucao de chamados por mes/ano

Foi criado `TicketsMonthlyEvolutionWidget`.

Ele usa barras verticais e permite visualizar janeiro, fevereiro, marco, abril, maio etc. conforme o periodo selecionado.

## 12. Testes realizados

### Testes locais

Foram executadas validacoes locais durante o desenvolvimento:

- Lint PHP em todos os arquivos PHP do plugin.
- Checagem de sintaxe JavaScript em `js/dashboardplus.js`.
- Validacao do `composer.json`.
- Busca por referencias esperadas com `rg`, incluindo:
  - `monthlyEvolution`
  - `TicketsMonthlyEvolutionWidget`
  - `tickets_monthly_evolution`
  - `DATE_FORMAT`
  - `getMonthBuckets`
  - validacao de 16 widgets no `vm_validate.php`

### Instalacao/sincronizacao na VM

Fluxo executado:

1. Sincronizacao dos arquivos locais para `/tmp/dashboardplus_sync`.
2. Copia para `/var/www/html/glpi/plugins/dashboardplus`.
3. Ajuste de owner para `www-data:www-data`.
4. Ajuste de permissoes de leitura/escrita adequadas.
5. Execucao de lint PHP dentro da VM.

Resultado: sem erros de sintaxe.

### Teste HTTP autenticado

Foi realizado teste via HTTP contra o GLPI da VM:

- Login no GLPI.
- Acesso a tela de configuracao.
- Salvamento dos 16 widgets habilitados.
- Definicao de visualizacoes:
  - cards para metricas
  - ratio para comparativos
  - columns para status e evolucao mensal
  - table/bars/donut para alguns rankings
- Acesso ao dashboard com periodo `2026-01-01` a `2026-05-31`.
- Validacao de 16 cards na tela.
- Chamada AJAX direta para `tickets_monthly_evolution`.

Resultado do teste do widget mensal:

- HTTP 200.
- JSON `ok = true`.
- Titulo presente.
- Classe `dashboardplus-column-bars` presente.
- Meses `01/2026` a `05/2026` presentes.
- Nenhum `dashboardplus-widget-error`.

### Validador da VM

Foi executado `plugins/dashboardplus/tests/vm_validate.php` na VM.

Resultado observado:

- GLPI `10.0.25`.
- Plugin `Dashboard Plus` instalado e ativo.
- Tabelas criadas:
  - `glpi_plugin_dashboardplus_configs`
  - `glpi_plugin_dashboardplus_configentities`
  - `glpi_plugin_dashboardplus_widgetconfigs`
- 16 widgets configurados.
- Direitos de perfil criados:
  - `plugin_dashboardplus_view`
  - `plugin_dashboardplus_admin`
  - `plugin_dashboardplus_widgets`
  - `plugin_dashboardplus_global`

### Teste visual no navegador

Foi usado navegador interno para abrir o GLPI na VM e validar a renderizacao.

Observacao encontrada:

- Alguns widgets usam carregamento preguiçoso.
- O widget mensal inicialmente apareceu como placeholder porque estava abaixo da primeira dobra.
- Ao rolar a pagina, o lazy loading carregou o widget.

Resultado final:

- 16 cards na pagina.
- 0 widgets em loading apos a rolagem.
- 0 erros visuais de widget.
- Widget mensal presente.
- `dashboardplus-column-bars` presente.
- 5 barras mensais renderizadas.
- Sem overflow horizontal.

### Logs verificados

Foram verificados logs do GLPI e Apache:

- `files/_log/php-errors.log`
- `files/_log/sql-errors.log`
- `/var/log/apache2/error.log`

Busca feita por termos relacionados:

- `dashboardplus`
- `tickets_monthly_evolution`
- `DATE_FORMAT`
- `monthlyEvolution`

Resultado: nenhuma ocorrencia de erro relacionada ao Dashboard Plus ou ao widget mensal.

## 13. Erros, bloqueios e correcoes

### 13.1 Publicacao no GitHub bloqueada

Problema:

- O diretorio `dashboardplus` nao era repositorio Git.
- O comando `gh` nao estava instalado.
- A conexao GitHub disponivel nao listava repositorios acessiveis.

Impacto:

- Nao foi possivel subir a primeira versao para GitHub naquele momento.

Status:

- Pendente. E necessario informar um repositorio remoto ou instalar/autenticar o GitHub CLI.

### 13.2 Caminho local mudou no workspace atual

Problema:

- Em um momento anterior o plugin estava em `C:\Projetos\glpi\plugins\dashboardplus`.
- No workspace atual, o plugin esta em `C:\Projetos\glpi\plugins\meusplugins\dashboardplus`.

Correcao:

- Foi feita nova busca com `rg --files` e `Get-ChildItem`.
- O caminho correto atual foi identificado.

Impacto:

- Nenhum impacto funcional no plugin, mas o novo chat deve usar o caminho atual.

### 13.3 Caracteres acentuados aparentavam quebrados no terminal

Problema:

- Saidas do PowerShell exibiram strings como `ConfiguraÃ§Ãµes`.

Causa provavel:

- Diferenca de encoding na exibicao do terminal, nao necessariamente no arquivo ou na UI.

Status:

- A interface GLPI foi validada visualmente com textos em portugues.
- Ainda e recomendado criar arquivos formais de localizacao em `locales/`.

### 13.4 Lazy loading confundiu o primeiro teste visual

Problema:

- Ao abrir a pagina no navegador, o widget mensal ainda aparecia como `Carregando Evolucao de chamados por mes/ano`.

Causa:

- O widget estava abaixo da dobra da tela e dependia de carregamento preguiçoso.

Correcao:

- Foi feita rolagem ate o widget.
- O carregamento AJAX foi disparado.
- O widget renderizou corretamente.

Status:

- Nao foi considerado bug. O comportamento esta coerente com lazy loading.

### 13.5 Necessidade de atualizar o validador

Problema:

- O numero esperado de widgets mudou com as novas inclusoes.

Correcao:

- `tests/vm_validate.php` foi ajustado para esperar pelo menos 16 widgets.

Status:

- Validado na VM.

## 14. Estado atual das funcionalidades

Funcionando e validado:

- Plugin instalado e ativo no GLPI 10.0.25.
- Menu `Dashboard Plus`.
- Tela principal do dashboard.
- Tela de configuracao.
- Permissoes de perfil.
- Configuracao de entidade.
- Configuracao de periodo.
- Auto-refresh configuravel.
- Cache configuravel.
- Habilitar/desabilitar widgets.
- Ordem de widgets.
- Escolha de visualizacao por widget.
- Widgets carregados via AJAX.
- Tratamento de erro por widget.
- Widgets principais de chamados.
- Widgets por status, prioridade, categoria, grupo e tecnico.
- SLA cumprido x violado.
- Nao solucionados x solucionados/fechados.
- Evolucao mensal por mes/ano.

## 15. Pendencias tecnicas e funcionais

Pendencias imediatas:

- Inserir chamados de teste na VM para janeiro a maio de 2026, com volume suficiente para demonstrar o grafico mensal.
- Implementar opcao por widget para mostrar/ocultar numeros exibidos nos graficos. O pedido surgiu porque alguns graficos ficam visualmente poluidos.

Pendencias de publicacao:

- Criar/informar repositorio GitHub.
- Inicializar Git local caso ainda nao exista.
- Fazer commit da primeira versao.
- Publicar a versao inicial.

Pendencias de qualidade:

- Criar catalogos formais em `locales/`.
- Ampliar testes automatizados.
- Testar usuario sem permissao em navegador.
- Testar multi-entidade com entidades filhas reais.
- Testar volume grande de chamados.
- Testar desinstalacao/reinstalacao em ciclo completo.
- Documentar rollback.

Pendencias de produto:

- Melhorar configuracao granular por widget.
- Permitir ocultar valores numericos em barras/pizza/donut/ratio.
- Possivelmente permitir altura do widget ou densidade visual.
- Criar seed/demo data opcional apenas para ambiente de teste, nunca para producao.

## 16. Proximos passos recomendados

1. Criar chamados de teste na VM entre janeiro e maio de 2026.
2. Implementar `show_values` ou equivalente no JSON `config` de cada widget.
3. Ajustar `WidgetRenderer` para respeitar essa opcao nas visualizacoes.
4. Expor a opcao na tela `ConfigPage`.
5. Validar visualmente cards, barras, colunas, pizza, donut e ratio com a opcao ligada/desligada.
6. Rodar lint PHP e JS.
7. Sincronizar plugin para VM.
8. Executar `vm_validate.php`.
9. Testar dashboard via HTTP/AJAX.
10. Verificar logs do GLPI e Apache.
11. Publicar a versao 1.0.0 no GitHub com tag e pacote de release.

## 17. Observacoes importantes para o novo chat

- O plugin deve continuar sendo tratado como agregador de dados, nao como dono das regras de negocio.
- Nao inserir integracoes profundas com outros plugins no MVP.
- Antes de criar qualquer widget novo, verificar fonte dos dados, permissao necessaria, entidade aplicada e impacto de performance.
- Nao duplicar dados do GLPI.
- Toda consulta de chamados deve passar pelo contexto de periodo e entidade.
- Todo widget deve ser protegido contra acesso direto.
- Erros de widget devem ser logados e exibidos como erro local do widget.
- O campo `config` da tabela de widgets ja e o lugar natural para novas opcoes visuais.
- A opcao de ocultar numeros deve provavelmente morar em `config`, junto de `visualization`.
- Para dados de teste, preferir script controlado e reversivel, com identificador claro nos chamados criados, para permitir limpeza depois.
