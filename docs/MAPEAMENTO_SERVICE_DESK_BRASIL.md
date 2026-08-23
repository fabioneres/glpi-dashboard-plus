# Mapeamento Service Desk Brasil

## Fontes analisadas

- `sdb-vw-tickets-travessia.txt`: view de chamados usada no Metabase.
- `sdb-vw-computers-travessia.txt`: view de computadores usada no Metabase.
- `traducao-das-colunas.txt`: tradução de status, tipo, prioridade, satisfação, SLA e reabertura.
- `json-mapa-brasil.txt`: referência GeoJSON de UF para mapas.
- `travessia-2024-inteligencia-de-dados.pdf`: lista os modelos públicos de dashboard.
- `Dashboard  Pesquisa Satisfação (1).pdf`: captura visual do dashboard de pesquisa de satisfação.
- `17441_rev1.json` e `17441_rev2.json`: dashboards Grafana de assistência/monitoramento GLPI.

Os arquivos foram tratados como referência funcional, não como instruções operacionais.

## Dashboards identificados

### Monitoramento

Indicadores equivalentes implementados ou já existentes:

- Tickets recebidos por dia.
- Tickets solucionados / encerrados por dia.
- Nº de tickets em aberto por dia nos últimos 30 dias.
- Cards de atendimento: novos, não atribuídos, planejados, fila de notificações, solucionados, pendentes, encerrados, abertos, atribuídos e solucionados hoje.
- Cards de prioridade média, alta e crítica.
- Tickets por localização.
- Chamados por status.
- Incidentes x requisições.
- Chamados por prioridade.
- Chamados por entidade.
- Chamados por origem.
- Chamados por categoria.
- Chamados por grupo técnico.
- Chamados por técnico.
- Evolução de chamados por mês/ano.
- Chamados reabertos.
- Motivos de pendência.

### SLA

Indicadores equivalentes implementados ou já existentes:

- Chamados atrasados.
- SLA de solução cumprido x violado.
- SLA de atendimento cumprido x violado.
- Tempo médio de solução dos chamados fechados.

### Nota de Satisfação

Indicadores equivalentes implementados:

- Nota média de satisfação.
- Pesquisas respondidas.
- Percentual de pesquisas respondidas.
- Satisfação geral com respondidos e não respondidos.
- Satisfação por nota.
- Média de satisfação por grupo de atendimento.
- Categoria x pesquisa de satisfação, com total de chamados, respondidas e média.
- Pesquisa satisfação x comentário, com chamado, atendente, requerente, nota e comentário.
- Pesquisas respondidas por mês.
- Média de satisfação por mês.
- Chamados reabertos.

O Dashboard Plus separa o período global de abertura do chamado do período da pesquisa de satisfação. Assim, "Data Abertura" define o universo de chamados analisado e "Data Pesquisa" define quais respostas entram em médias, comentários, respondidas e gráficos mensais.

### Tarefas

Indicador equivalente implementado:

- Horas de tarefas por técnico, considerando tarefas finalizadas e tempo apontado.

### Controle de Ativos

Indicadores equivalentes implementados:

- Total de computadores.
- Total de monitores.
- Total de impressoras.
- Total de telefones.
- Computadores por fabricante.
- Monitor por fabricante.
- Computadores por tipo.
- Computadores por localização.
- Computadores por sistema operacional.
- Dispositivos por CPU.
- Ativos por cidade no estado de São Paulo, com marcadores proporcionais no mapa.

O mapa de São Paulo usa `glpi_locations.town`, `glpi_locations.state`, `glpi_locations.latitude` e `glpi_locations.longitude`. Quando latitude/longitude não estão cadastradas, o provider tenta resolver coordenadas de cidades paulistas comuns por tabela interna simples. Cidades sem coordenada ficam listadas como não mapeadas, sem quebrar o widget.

Limitações atuais:

- O mapa considera computadores localizados no estado de São Paulo; outros tipos de ativo podem ser incorporados depois se o modelo operacional exigir.
- A VM pode exibir estado vazio se os ativos de teste não tiverem cidade/UF/latitude/longitude em `glpi_locations`.
- Modelo ainda não foi implementado porque depende de relacionamento mais específico de inventário em cada ambiente GLPI.

## Decisões de implementação

- Não foram criadas views SQL no banco do GLPI.
- As consultas usam tabelas nativas e respeitam o `DashboardContext`.
- Rankings usam limite padrão para evitar consultas amplas.
- Filtros de período e entidade continuam centralizados.
- Widgets novos passam pelo mesmo fluxo de permissão, habilitação, AJAX e cache dos widgets existentes.
- Widgets de ativos possuem provider próprio e usam permissões nativas do GLPI para o tipo de inventário consultado.

## Pendência recomendada

- Popular a VM com localizações e ativos de teste em cidades de São Paulo.
- Avaliar inclusão de modelo após confirmar quais campos/tabelas estão preenchidos na produção.
- Avaliar uso de GeoJSON oficial do estado de São Paulo em uma versão futura se for necessário desenho cartográfico mais preciso.
