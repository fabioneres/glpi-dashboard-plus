# Dashboard Plus

Dashboard Plus é um plugin para GLPI 10.0.24+ que entrega um painel moderno e modular para indicadores operacionais, táticos e executivos de chamados.

A versão 1.2.0 adiciona a seção **Sobre**, com explicações visuais dos cálculos, fórmulas e níveis de interpretação por aba do dashboard.

Esta implementação usa o plugin Dashboard clássico do Stevenes Donato como inspiração funcional para a experiência esperada, mas segue uma arquitetura mais atual para GLPI 10: widgets isolados, direitos por perfil, consultas com entidade, carregamento AJAX, cache e logs defensivos.

## Escopo do MVP

- Entrada de menu "Dashboard Plus".
- Tela principal do painel com filtros de período, datas e entidade.
- Widgets iniciais de chamados:
  - chamados abertos;
  - chamados novos;
  - chamados atribuídos;
  - chamados planejados;
  - chamados pendentes;
  - chamados solucionados;
  - chamados fechados;
  - não solucionados x solucionados/fechados;
  - chamados atrasados;
  - evolução de chamados por mês/ano;
  - chamados por status;
  - chamados por prioridade;
  - chamados por categoria;
  - chamados por grupo técnico;
  - chamados por técnico;
  - SLA cumprido x violado.
- Registro de widgets preparado para widgets fornecidos por outros plugins.
- Página de configuração para período padrão, atualização automática, cache e widgets habilitados.
- Seleção de visualização por widget:
  - cartão;
  - compacto;
  - barras;
  - barras verticais;
  - tabela;
  - pizza;
  - donut;
  - faixa proporcional.
- Configuração de escopo de entidades consideradas.
- Direitos de perfil:
  - visualizar Dashboard Plus;
  - administrar Dashboard Plus;
  - configurar widgets;
  - visualizar indicadores globais.

## Regras de desenho

- Não alterar o core do GLPI.
- Não duplicar dados de negócio.
- Não implementar integrações profundas com outros plugins no MVP.
- Todo widget deve validar permissão antes de renderizar.
- Filtros de entidade são aplicados no nível da consulta.
- Widgets pesados devem usar limite, cache, carregamento assíncrono ou paginação.
- Falhas são registradas em `files/_log/plugin_dashboardplus.log` e exibidas por widget, sem quebrar a página.

## Validação

Consulte `tests/TEST_PLAN.md` para o checklist de testes do MVP.

## Pontos de integração futura

O registro de widgets é centralizado de propósito. Plugins futuros podem registrar classes de widget com `GlpiPlugin\Dashboardplus\Widget\WidgetRegistry::registerWidgetClass()` e implementar `GlpiPlugin\Dashboardplus\Widget\WidgetInterface`.

Fontes planejadas:

- Plugin 001 - Atribuição Inteligente;
- Plugin 002 - Gestão de Insumos;
- Plugin 003 - Aprovação de Solução;
- Plugin 007 - Pesquisa de Satisfação Plus;
- Plugin 008 - Geolocalização Plus;
- Plugin 009 - Project Manager Plus.

Essas integrações devem consumir a camada pública de dados/API de cada plugin quando disponível e não devem duplicar regras de negócio.
