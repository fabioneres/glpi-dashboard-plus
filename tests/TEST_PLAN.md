# Plano de testes do Dashboard Plus

## Instalação e ciclo de vida

- Instalar o plugin no GLPI 10.0.25 e confirmar a criação de todas as tabelas `glpi_plugin_dashboardplus_*`.
- Ativar e desativar o plugin sem avisos de PHP.
- Desinstalar o plugin e confirmar a remoção das tabelas e dos direitos de perfil.
- Reinstalar após a desinstalação para validar instalação idempotente.

## Permissões

- Usuário sem `plugin_dashboardplus_view` não pode acessar `front/dashboard.php` nem `front/widget.ajax.php`.
- Usuário com direito de visualização pode abrir o painel e vê apenas dados de chamados permitidos pelo GLPI.
- Usuário com direito de administração pode abrir `front/config.form.php`.
- Usuário sem direito de configuração de widgets não pode salvar configurações de widgets.
- Acesso AJAX direto a widgets desabilitados ou desconhecidos retorna erro protegido.

## Entidades

- Usuário em entidade única vê apenas dados daquela entidade.
- Filtro recursivo de entidade inclui entidades filhas somente quando habilitado.
- Escopo de entidades configurado limita as consultas mesmo quando o usuário tem acesso mais amplo.
- Escopo de entidades vazio usa como padrão as entidades ativas visíveis para cada usuário.

## Widgets

- Cada widget do MVP carrega de forma independente por AJAX.
- Uma falha em um widget é registrada em log e não quebra a página do painel.
- Estado habilitado/desabilitado do widget é respeitado na tela e nas chamadas AJAX.
- Visualização escolhida por widget é salva no campo `config` e aplicada na próxima carga.
- Widgets de métrica aceitam cartão e compacto.
- Widgets de distribuição aceitam barras, barras verticais, tabela, pizza e donut.
- Widget de SLA aceita faixa proporcional, barras verticais, barras, tabela e donut.
- Widget "Chamados por status" exibe novo, atribuído, planejado, pendente, solucionado e fechado com cores nativas do GLPI.
- Widget "Não solucionados x solucionados/fechados" respeita o período configurado no topo do painel e não duplica filtros de data no próprio card.
- Widget "Evolução de chamados por mês/ano" agrupa pela abertura do chamado, respeita o período superior e mostra meses sem chamados com valor zero.
- Links de busca gerados pelos widgets métricos abrem pesquisas de chamados no GLPI com o período selecionado.

## Performance

- Validar o comportamento do painel com uma tabela `glpi_tickets` grande.
- Confirmar que widgets de lista mantêm os limites configurados.
- Confirmar que o cache pode ser habilitado/desabilitado e respeita o TTL configurado.
- Confirmar que filtros de data e entidade estão presentes em toda consulta de chamados.

## Regressão

- Lint de sintaxe PHP em todos os arquivos PHP do plugin.
- Checagem de sintaxe JavaScript em `js/dashboardplus.js`.
- Validação da sintaxe JSON do `composer.json`.
- Revisão de `files/_log/plugin_dashboardplus.log` após simulação de falha em widget.
