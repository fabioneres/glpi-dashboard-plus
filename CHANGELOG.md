# Histórico de alterações

## 1.0.0

- Versão consolidada do Dashboard Plus para GLPI 10.0.25.
- Período `Todo histórico` para permitir análise desde chamados antigos até o chamado atual, mantendo filtro de data no servidor.
- Correção da data de referência dos KPIs principais: abertura por `date`, solução por `solvedate` e fechamento por `closedate`.
- Drill-down dos KPIs principais alinhado aos campos nativos de busca do GLPI.
- Catálogo inicial de indicadores com fonte, classificação e data de referência.
- Aba Distribuições com indicadores do Atribuição Inteligente e opções de visualização por gráfico.
- Aba Ativos com mapa de São Paulo e rankings de inventário.

## 0.2

- Novos dashboards inspirados nos modelos da Service Desk Brasil.
- Indicadores adicionais de monitoramento, SLA, satisfação e tarefas.
- Nova aba de Ativos com totais de computadores, monitores, impressoras e telefones.
- Rankings de computadores por fabricante, tipo, localização e sistema operacional.
- Mapa do estado de São Paulo com marcação das cidades onde há computadores localizados.
- Consultas agregadas com limites em rankings e respeito ao escopo de entidade do GLPI.
- Smoke test ampliado para validar dados e renderização dos widgets.
- Novas visualizações inspiradas nos modelos do Metabase: medidor e velocímetro para indicadores percentuais.
- Ajustes visuais em pizza, donut, barras e barras verticais para leitura mais próxima de painéis BI.
- Contorno do mapa de São Paulo ajustado a partir de GeoJSON de UF, com marcadores posicionados por localização geográfica.

## 0.1.0

- Estrutura inicial do Dashboard Plus para GLPI 10.0.25.
- Instalador, desinstalador, direitos de perfil e configuração padrão.
- Tela moderna do dashboard com carregamento assíncrono de widgets.
- Widgets iniciais de chamados e página de configuração.
- Renderização de widgets com cache e logs defensivos.
- Área de métricas principais separada dos indicadores detalhados.
- Configuração de visualização por widget com cartão, compacto, barras, tabela, pizza, donut e faixa proporcional.
- Visualização em barras verticais para gráficos de distribuição.
- Cards por status nativo do GLPI: novos, atribuídos, planejados, pendentes, solucionados e fechados.
- Cores dos widgets de status alinhadas às classes nativas `itilstatus` do GLPI.
- Indicador "Não solucionados x solucionados/fechados" respeitando o período e entidade selecionados nos filtros superiores.
- Indicador "Evolução de chamados por mês/ano" com barras verticais e preenchimento de meses sem chamados.
