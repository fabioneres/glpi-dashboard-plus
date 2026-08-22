# Dashboard Plus - Aprendizados do MVP e auditoria inicial

## Contexto

O Dashboard Plus e o Plugin 006 do ecossistema local de plugins GLPI. Ele foi criado para
modernizar o dashboard do GLPI 10.0.25 com indicadores operacionais, taticos e executivos,
sem alterar o core e sem duplicar regras de negocio.

A fonte principal do MVP e o proprio GLPI, especialmente chamados, SLA, status, prioridades,
categorias, grupos, tecnicos e entidades. A referencia funcional foi o plugin Dashboard
classico do Stevenes Donato, mas a arquitetura adotada neste plugin e modular, com widgets
isolados, carregamento AJAX, cache, logs defensivos e configuracao por widget.

Este documento consolida o aprendizado obtido durante o desenvolvimento, validacao em VM,
analise tecnica e revisao contra as orientacoes da skill GLPI Plugins GLPI 10.

## Orientacoes da skill aplicadas

- Preservar a arquitetura existente do plugin.
- Pensar primeiro como arquiteto GLPI, depois como desenvolvedor PHP.
- Nao alterar o core do GLPI.
- Reutilizar mecanismos nativos sempre que possivel.
- Validar sessao, direitos, entidades e entrada de dados no servidor.
- Assumir multi-entidade por padrao.
- Manter compatibilidade com GLPI 10.0.x e PHP 7.4+.
- Evitar refatoracoes estruturais sem justificativa tecnica.
- Documentar falhas, decisoes, riscos, testes e rollback.
- Tratar AJAX como carregamento parcial, nao como forma de burlar permissoes.

## Estado arquitetural observado

### O que existe hoje

- Estrutura moderna com `src/`, `front/`, `install/`, `css/`, `js/`, `locales/`, `tests/`,
  `setup.php`, `hook.php`, `bootstrap.php` e `composer.json`.
- Namespace principal `GlpiPlugin\Dashboardplus`.
- `DashboardContext` centraliza periodo, datas, entidade, recursividade, cache e escopo.
- `TicketMetricsProvider` centraliza as consultas de chamados.
- `WidgetInterface` define o contrato minimo dos widgets.
- `WidgetRegistry` registra widgets internos e prepara extensao futura via
  `registerWidgetClass()`.
- `WidgetRenderer` centraliza renderizacao visual.
- `DashboardCache` usa cache GLPI quando habilitado.
- `Logger` registra falhas em `files/_log/plugin_dashboardplus.log`.
- `front/widget.ajax.php` protege o carregamento de widgets por login, permissao,
  existencia do widget e estado habilitado.

### Avaliacao geral

O plugin esta bem alinhado para um MVP GLPI 10.0.x. A arquitetura respeita a separacao de
responsabilidades e evita alterar o core. As consultas usam dados existentes e nao duplicam
informacao operacional. O acesso e controlado por direitos de perfil e os widgets carregam de
forma independente, reduzindo o impacto de falhas localizadas.

O plugin ainda precisa de hardening para ser tratado como release madura, principalmente em
upgrade/migration, guardas contra acesso direto em todos os PHP, documentacao operacional,
i18n formal e testes automatizados mais amplos.

## Licao 1 - Arquitetura modular de widgets funciona bem para dashboard GLPI

### Problema

Dashboards tendem a crescer rapidamente e misturar consultas, regras de permissao,
renderizacao e configuracao na mesma tela.

### Sintoma

Sem modularizacao, cada novo indicador aumenta o risco de duplicar filtros de entidade,
periodo, permissao e regras de apresentacao.

### Causa

Indicadores diferentes compartilham preocupacoes transversais: contexto, permissao, cache,
logs, carregamento AJAX e visualizacao.

### Solucao adotada

Separar o dashboard em:

- `DashboardContext` para contexto global.
- `TicketMetricsProvider` para dados.
- `WidgetInterface` para contrato.
- `WidgetRegistry` para registro/configuracao.
- `WidgetRenderer` para visualizacao.
- `front/widget.ajax.php` para carregamento isolado.

### Prevencao

Todo novo widget deve passar pelo mesmo contrato. Antes de criar um widget, confirmar:

- fonte dos dados;
- permissao necessaria;
- filtro de periodo;
- filtro de entidade;
- limite para rankings;
- impacto de performance;
- comportamento em caso de falha.

## Licao 2 - O plugin deve continuar como agregador, nao dono da regra de negocio

### Problema

Ha risco de o Dashboard Plus passar a recriar regras de outros plugins ou do proprio GLPI.

### Sintoma

Indicadores podem divergir dos dados oficiais se o plugin duplicar regras de negocio,
armazenar contagens proprias ou interpretar status de forma diferente do GLPI.

### Causa

Dashboards costumam parecer simples, mas cada numero depende de regras de permissao,
entidade, status, SLA, grupos, tecnicos e filtros de busca.

### Solucao adotada

O plugin le dados existentes, principalmente `glpi_tickets`, e nao cria tabelas operacionais.
As tabelas proprias guardam apenas configuracao global, escopo de entidades e configuracao de
widgets.

### Prevencao

Para integracoes futuras, consumir APIs/camadas publicas dos outros plugins quando existirem.
Nao copiar regras internas de Atribuicao Inteligente, Aprovacao de Solucao, Pesquisa de
Satisfacao Plus ou outros plugins do ecossistema.

## Licao 3 - Permissao e entidade precisam ser aplicadas no servidor e por widget

### Problema

Dashboards carregados por AJAX podem expor dados se a tela principal valida permissao, mas o
endpoint AJAX nao valida novamente.

### Sintoma

Um usuario sem direito poderia chamar diretamente `front/widget.ajax.php` e tentar obter HTML
ou dados de um widget.

### Causa

Endpoints AJAX sao URLs independentes e nao herdam automaticamente a intencao de seguranca da
tela que os chamou.

### Solucao adotada

O endpoint de widget valida:

- usuario autenticado;
- direito de visualizar Dashboard Plus;
- widget existente;
- `canView()` do widget;
- configuracao habilitada/desabilitada.

As consultas passam por `DashboardContext::getEntityCriteria()` e por
`Ticket::getCriteriaFromProfile()`.

### Prevencao

Nunca criar endpoint AJAX sem:

- `Session::checkLoginUser()`;
- permissao especifica;
- validacao de entrada;
- validacao de entidade quando aplicavel;
- retorno controlado sem stack trace;
- log de erro tecnico.

## Licao 4 - Filtro de data e entidade deve ser centralizado

### Problema

Consultas de dashboard sem data e entidade podem ficar pesadas e vazar informacao entre
entidades.

### Sintoma

Widgets com numeros inconsistentes, carregamento lento ou exibicao de dados fora do escopo do
usuario.

### Causa

Quando cada widget monta sua propria consulta, aumenta a chance de esquecer um filtro.

### Solucao adotada

`TicketMetricsProvider::getBaseWhere()` centraliza:

- `is_deleted = 0`;
- periodo por `glpi_tickets.date`;
- criterio de entidade vindo do `DashboardContext`.

As consultas tambem sao mescladas com `Ticket::getCriteriaFromProfile()`.

### Prevencao

Nao criar consultas diretas fora do provider sem justificativa. Se outro provider for criado,
ele deve repetir o mesmo padrao: periodo, entidade, perfil ativo, limites e leitura defensiva.

## Licao 5 - Rankings precisam de limite desde o MVP

### Problema

Rankings por categoria, grupo e tecnico podem consultar grandes volumes de dados.

### Sintoma

Dashboard lento, uso excessivo de banco, timeouts ou impacto em outros usuarios.

### Causa

Dashboards executam multiplas consultas em uma mesma tela e podem ser recarregados
automaticamente.

### Solucao adotada

Rankings usam `LIMIT`, e o dashboard possui cache configuravel com TTL.

### Prevencao

Manter limite padrao para rankings. Para rankings maiores, avaliar:

- paginacao;
- drill-down;
- cache mais agressivo;
- filtros adicionais;
- execucao sob demanda.

## Licao 6 - Cache deve considerar usuario, perfil, periodo e entidade

### Problema

Cache de dashboard pode causar vazamento de dados se a chave for generica.

### Sintoma

Um usuario poderia ver resultado calculado para outro perfil, outra entidade ou outro periodo.

### Causa

Indicadores dependem de sessao, perfil ativo, entidade ativa, recursividade e filtros.

### Solucao adotada

A chave de cache inclui:

- usuario;
- perfil;
- periodo;
- entidade selecionada;
- recursividade;
- escopo configurado;
- entidades ativas.

### Prevencao

Toda nova dimensao que altere resultado do widget deve entrar na chave do cache. Exemplos:
tipo de chamado, grupo, categoria, prioridade, escopo avancado ou permissao adicional.

## Licao 7 - Configuracao visual deve ficar no JSON do widget

### Problema

Adicionar uma coluna no banco para cada preferencia visual torna upgrades mais frequentes e
aumenta o custo de manutencao.

### Sintoma

Schema cresce com opcoes pequenas como visualizacao, mostrar valores, densidade e altura.

### Causa

Configuracoes visuais mudam mais rapido do que dados estruturais.

### Solucao adotada

O campo `config` em `glpi_plugin_dashboardplus_widgetconfigs` guarda JSON. Hoje ele armazena
principalmente `visualization`.

### Prevencao

Novas opcoes visuais simples devem ser adicionadas ao JSON, por exemplo `show_values`, sem
migration. Usar migration apenas quando a mudanca estrutural exigir busca, indice,
relacionamento, obrigatoriedade ou integridade de dados.

## Licao 8 - Upgrade precisa evoluir para Migration idempotente

### Problema

O instalador atual executa `install.sql` com `CREATE TABLE IF NOT EXISTS`.

### Sintoma

Instalacao limpa funciona, mas upgrade futuro pode nao criar campos novos, ajustar indices ou
reparar schema existente.

### Causa

`CREATE TABLE IF NOT EXISTS` nao altera tabelas ja existentes.

### Solucao atual

Para o MVP, o schema inicial foi criado por `install/install.sql`, e o upgrade chama a mesma
rotina de instalacao.

### Prevencao

Antes da proxima release publica, implementar upgrade com `Migration` e verificacoes
idempotentes:

- `tableExists`;
- `fieldExists`;
- indices existentes;
- criacao segura de campos;
- atualizacao de `dbversion`;
- preservacao de configuracoes existentes.

## Licao 9 - Guardas de acesso direto devem cobrir todos os PHP

### Problema

Arquivos `setup.php`, `hook.php` e `bootstrap.php` possuem guarda `GLPI_ROOT`, mas as classes
em `src/` nao possuem a mesma protecao no topo.

### Sintoma

Arquivos de classe podem ser acessados diretamente pelo navegador se o servidor expuser a
pasta do plugin.

### Causa

O autoload moderno facilita classes em `src/`, mas a regra de seguranca da skill continua
valendo para todo PHP.

### Solucao atual

As classes nao executam acoes sensiveis sozinhas, mas ainda falta padronizar a guarda.

### Prevencao

Adicionar em todos os arquivos PHP do plugin, inclusive classes:

```php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}
```

Preservar compatibilidade com PHP 7.4 e nao alterar comportamento funcional ao aplicar essa
proteção.

## Licao 10 - Drill-down deve refletir o mesmo escopo do indicador

### Problema

Os numeros dos widgets usam entidade e perfil, mas os links para a busca do GLPI precisam
representar o mesmo escopo sempre que possivel.

### Sintoma

Ao clicar em um indicador, a busca pode mostrar conjunto diferente do numero exibido, mesmo
que o GLPI continue respeitando permissoes.

### Causa

Os links atuais montam criterios de data, status e filtros especificos, mas precisam ser
avaliados para entidade/escopo configurado e recursividade.

### Solucao atual

Os links usam `Ticket::getSearchURL()` e criterios do mecanismo de busca do GLPI.

### Prevencao

Validar os campos de busca nativos para entidade e recursividade antes de adicionar filtros.
Nao inventar IDs de campos sem conferir no Search do GLPI 10.0.25. Testar os links para:

- entidade ativa;
- entidade selecionada;
- escopo configurado;
- recursividade ligada/desligada;
- usuario com permissao parcial.

## Licao 11 - Direitos devem ser usados ou documentados como futuros

### Problema

O direito `plugin_dashboardplus_global` existe, mas os widgets atuais aparentemente usam o
direito padrao de visualizacao.

### Sintoma

Administradores podem configurar um direito que ainda nao altera o comportamento visivel.

### Causa

O contrato `getRequiredRight()` ja suporta direito global, mas nenhum widget atual sobrescreve
esse metodo para exigir `RIGHT_GLOBAL`.

### Solucao atual

Manter o direito como preparacao para indicadores executivos/globais.

### Prevencao

Na proxima revisao, escolher uma das opcoes:

- documentar claramente que `plugin_dashboardplus_global` e reservado para widgets futuros;
- aplicar o direito a widgets executivos que realmente tenham escopo global;
- remover o direito se nao houver uso planejado imediato.

## Licao 12 - I18n formal deve existir antes de distribuicao

### Problema

O plugin usa `Plugin::loadLang('dashboardplus')` e strings com dominio `dashboardplus`, mas a
pasta `locales/` ainda nao possui catalogo formal.

### Sintoma

Interface em portugues funciona no codigo atual, mas distribuicao, traducao e manutencao de
idiomas ficam incompletas.

### Causa

As strings foram priorizadas no codigo durante o MVP.

### Solucao atual

Manter textos em portugues no codigo e registrar a pendencia.

### Prevencao

Criar catalogo formal em `locales/` antes da release publica. Validar encoding UTF-8 e evitar
confundir problema de terminal com problema real de arquivo ou UI.

## Licao 13 - Lazy loading exige validacao visual com rolagem

### Problema

Widgets abaixo da primeira dobra podem permanecer como placeholder ate entrar na area de
observacao do navegador.

### Sintoma

Durante teste visual, um widget parece nao carregar, mas apenas ainda nao foi observado pelo
`IntersectionObserver`.

### Causa

O dashboard usa lazy loading para reduzir custo inicial de carregamento.

### Solucao adotada

Durante a validacao visual, rolar a pagina ate os widgets detalhados e confirmar que o AJAX
foi disparado.

### Prevencao

Todo teste visual deve incluir:

- primeira dobra;
- rolagem ate o final;
- contagem de widgets ainda em loading;
- erros locais de widget;
- overflow horizontal;
- auto-refresh quando habilitado.

## Licao 14 - Dados de teste devem ser controlados e reversiveis

### Problema

Graficos mensais precisam de dados realistas para janeiro a maio de 2026, mas dados de teste
nao podem contaminar producao.

### Sintoma

Graficos ficam pobres ou parecem quebrados por falta de volume de chamados.

### Causa

Ambiente de teste nao possui massa suficiente para demonstrar evolucao mensal, rankings e SLA.

### Solucao recomendada

Criar seed/demo data apenas para VM ou ambiente de teste, com identificador claro em titulo,
descricao ou marcador controlado.

### Prevencao

O script de seed deve:

- nunca rodar em producao sem confirmacao explicita;
- criar chamados com marcador rastreavel;
- registrar quantidade e periodo;
- permitir limpeza posterior;
- respeitar entidades e relacionamentos validos do GLPI;
- documentar rollback.

## Licao 15 - Publicacao no GitHub depende de repositorio e credencial definidos

### Problema

A publicacao da versao 0.2 foi bloqueada por falta de repositorio/remoto e ferramenta ou
credencial GitHub disponivel.

### Sintoma

Nao foi possivel gerar a primeira publicacao no GitHub naquele momento.

### Causa

O diretorio do plugin nao estava como repositorio Git independente, `gh` nao estava instalado
e a conexao GitHub disponivel nao listava repositorios acessiveis.

### Solucao atual

Registrar a pendencia e manter o plugin local no workspace.

### Prevencao

Antes da release:

- definir se o repositorio sera monorepo ou repo proprio do plugin;
- configurar remoto;
- validar credencial;
- criar tag/release;
- documentar processo de empacotamento.

## Licao 16 - Documentacao de release precisa cobrir instalacao, upgrade e rollback

### Problema

O relatorio tecnico e o README descrevem bem o MVP, mas ainda falta documentacao operacional
para instalacao, upgrade e rollback.

### Sintoma

Um administrador consegue entender o objetivo do plugin, mas nao tem um roteiro completo para
operar o ciclo de vida em ambiente corporativo.

### Causa

Durante o MVP, a prioridade foi validar arquitetura, widgets, permissoes, consultas e
renderizacao.

### Solucao atual

Existe `tests/TEST_PLAN.md` e `tests/vm_validate.php`, alem do relatorio tecnico.

### Prevencao

Criar documentacao especifica para:

- instalacao limpa;
- ativacao;
- upgrade;
- rollback;
- desinstalacao;
- permissao inicial;
- verificacao de logs;
- validacao visual;
- ciclo de release.

## Licao 17 - Testes precisam evoluir alem do validador da VM

### Problema

O validador confirma instalacao, tabelas, direitos e contagem de widgets, mas nao cobre todos
os fluxos funcionais.

### Sintoma

E possivel passar no validador e ainda ter erro em permissao negativa, multi-entidade real,
drill-down, cache ou visualizacao especifica.

### Causa

O MVP priorizou smoke tests e validacao manual/HTTP.

### Solucao atual

Manter `tests/TEST_PLAN.md` como checklist e `tests/vm_validate.php` como validador de
instalacao/configuracao.

### Prevencao

Ampliar testes para:

- usuario sem permissao;
- usuario com permissao parcial;
- widgets desabilitados via AJAX;
- entidade unica;
- entidade recursiva;
- escopo configurado;
- cache ligado/desligado;
- links de busca;
- upgrade e uninstall;
- lint PHP e JS.

## Checklist para proximas evolucoes

Antes de implementar qualquer mudanca relevante:

- Confirmar que a mudanca pertence ao Dashboard Plus e nao ao core GLPI.
- Verificar se a mudanca duplica regra de negocio existente.
- Identificar fonte de dados e permissao necessaria.
- Validar impacto em entidade e recursividade.
- Confirmar compatibilidade com GLPI 10.0.x e PHP 7.4+.
- Evitar dependencias novas sem justificativa.
- Preferir configuracao em JSON para opcoes visuais simples.
- Usar Migration para mudancas estruturais de banco.
- Proteger endpoints e arquivos PHP contra acesso direto.
- Registrar erro tecnico sem quebrar o dashboard inteiro.
- Atualizar documentacao e testes quando a mudanca alterar comportamento.

## Pendencias priorizadas

1. Implementar upgrade idempotente com `Migration`.
2. Adicionar guardas `GLPI_ROOT` nos arquivos PHP de `src/` e testes quando aplicavel.
3. Garantir que drill-down reflita entidade/escopo/recursividade dos indicadores.
4. Decidir o uso efetivo do direito `plugin_dashboardplus_global`.
5. Criar catalogo formal em `locales/`.
6. Documentar instalacao, upgrade, rollback e release.
7. Ampliar testes automatizados e negativos.
8. Criar massa de teste controlada para janeiro a maio de 2026 na VM.
9. Implementar `show_values` no JSON de configuracao dos widgets.
10. Definir repositorio/remoto GitHub e processo de publicacao.

## Regra de ouro para o Dashboard Plus

O Dashboard Plus deve continuar sendo um agregador visual seguro e performatico dos dados do
GLPI. Ele nao deve virar dono das regras de negocio, nao deve alterar core, nao deve consultar
dados sem periodo/entidade/perfil e nao deve permitir que uma falha de widget comprometa o
painel inteiro.
