# Histórico de alterações

## 1.2.4

- Adiciona edição visual de layout no dashboard, com botão "Editar layout", reordenação por arrastar e soltar e salvamento via AJAX protegido por CSRF.
- Permite redimensionar widgets no painel e persistir largura, altura e ordem usando a configuração existente de widgets.
- Reorganiza a configuração de habilitação por entidade em matriz única, mostrando entidades acessíveis, estado ativo, recursividade e abas habilitadas.
- Registra os novos assets específicos do editor de layout no carregamento do plugin.
- Corrige a visualização em tabela do "Resumo por distribuidor" para não expor campos internos de gráfico como `label`, `number`, `value` e `color`.
- Mantém os dados técnicos internamente para alimentar os gráficos, exibindo na tabela apenas colunas de negócio como Distribuidor, Quantidade e Percentual.

## 1.2.3

- Adiciona o indicador "Tempo médio de solução" com tempo corrido entre abertura e solução dos chamados fechados.
- Renomeia o indicador baseado em `solve_delay_stat` para "Tempo médio útil de solução", diferenciando a métrica operacional/SLA gravada pelo GLPI.
- Ajusta a aba Ativos para usar entidades visíveis do usuário quando nenhuma entidade é escolhida explicitamente no filtro, evitando zerar inventário em cenários multi-entidade.
- Mantém respeito ao filtro de entidade e recursividade quando o usuário escolhe uma entidade específica.
- Reorganiza visualmente a configuração "Entidades e abas" em cards e chips, reduzindo quebra de layout e excesso de checkboxes soltos.
- Atualiza a seção Sobre com a explicação das duas métricas de tempo de solução.

## 1.2.2

- Hotfix dos filtros de entidade no dashboard e nos widgets AJAX.
- Corrige cenário em que selecionar entidade com recursividade gerava critério `glpi_tickets.is_recursive`, campo inexistente em chamados.
- O filtro recursivo agora expande a entidade selecionada para entidade + filhas acessíveis e aplica `entities_id IN (...)`.
- Corrige indicadores zerados ao selecionar entidade, especialmente em Atendimento, SLA e widgets baseados em chamados.
- O filtro de entidade da tela inicial agora usa a entidade ativa do usuário como padrão e carrega a lista pelo backend, sem depender do dropdown AJAX nativo.
- Reorganiza a configuração de entidades para diferenciar disponibilidade por entidade, abas habilitadas e escopo avançado dos dados.
- O antigo escopo de entidades fica como opção avançada opcional, preservando compatibilidade com configurações já existentes.

## 1.2.1

- Hotfix da tela de configuração para carregar a lista de entidades sem depender do dropdown AJAX nativo do GLPI.
- A seleção de entidades agora é montada no backend a partir de `glpi_entities`, respeitando `Session::haveAccessToEntity()`.
- Correção do erro de interface "os resultados não podem ser carregados" nos campos de entidade da configuração.

## 1.2.0

- Nova seção Sobre no Dashboard Plus, acessível pelo painel e pela tela de configuração.
- Explicações dos indicadores organizadas por abas internas: Visão Geral, Atendimento, SLA, Satisfação, Tarefas, Ativos, Distribuições e Capacidade.
- Cards visuais com ícones, descrições curtas, fórmulas e alertas para reduzir carga textual.
- Navegação da seção Sobre por links reais com parâmetro `tab`, funcionando mesmo quando o JavaScript ou o cache de assets ainda não estiver atualizado.
- Estilos essenciais da seção Sobre protegidos contra cache agressivo de CSS do GLPI.
- Explicação formal dos níveis de carga operacional e do cuidado para não interpretar carga como produtividade individual.

## 1.1.0

- Compatibilidade mínima ajustada para GLPI 10.0.24.
- Configuração de largura e altura por widget usando os campos existentes de configuração.
- Aplicação automática do layout recomendado 1.1.0 para organizar o tamanho ideal por tipo de gráfico.
- Novos indicadores de SLA por técnico e por categoria.
- Grid do dashboard remodelado com linhas responsivas e melhor aproveitamento de área.
- Revisão visual do Dashboard Plus com padrão mais coeso de UX corporativa.
- Reforço da hierarquia de abas, seções, cards, tabelas, rankings e estados de carregamento.
- Ajustes de responsividade para manter leitura em desktop e mobile.
- Temas globais customizáveis com paleta executiva, azul/verde, grafite e referência Metabase escura.
- Paletas de gráficos configuráveis e cor de destaque global para apresentações à gestão.
- Acabamento visual reforçado em cards, gráficos de pizza/rosca, barras, velocímetros, tabelas e mapa.
- Layout recomendado 1.1.1 reorganizado para aproximar painéis relacionados, reduzir espaços desproporcionais e evitar corte de informações.
- Rankings, tabelas, legendas e listas passam a ter rolagem interna e melhor quebra de texto dentro dos cards.
- Layout recomendado 1.1.2 com grade mais densa, cards mais compactos e composição mais próxima de painel executivo/coeso.
- Habilitação do Dashboard Plus por entidade, com suporte a recursividade e abas habilitadas por entidade.
- Permissões granulares por perfil para as abas Visão Geral, Atendimento, Distribuição, Capacidade, SLA, Satisfação, Tarefas e Ativos.
- Nova aba Capacidade com indicadores de carga operacional, carga ponderada, alertas e tabela de apoio por técnico.
- Integração desacoplada para capacidade usando dados do Atribuição Inteligente quando disponíveis, com fallback seguro quando a escala não puder ser determinada.
- Schema 1.1.2 com reparo idempotente para upgrades e inclusão automática da aba Capacidade em configurações antigas que tinham todas as abas habilitadas.

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
