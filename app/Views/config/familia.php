<?php

use App\Core\Csrf;

/** @var array $config */
/** @var array $historico */

$janela = $config['janela_dia'];
$regra = $config['regra_edicao'];
$alerta = $config['alerta_omissao'];
$resumo = $config['resumo_diario'];
$granularidade = (string)$config['granularidade'];
?>
<h2>Configurações da família</h2>
<form method="post" action="<?= e(url('config.familia.salvar')) ?>" class="formulario formulario-config">
    <?= Csrf::campo() ?>

    <fieldset class="cartao">
        <legend>Janela do dia</legend>
        <div class="linha-campos">
            <div>
                <label for="janela_inicio">Início</label>
                <input type="time" id="janela_inicio" name="janela_inicio" value="<?= e($janela['inicio']) ?>" required>
            </div>
            <div>
                <label for="janela_fim">Fim</label>
                <input type="time" id="janela_fim" name="janela_fim" value="<?= e($janela['fim']) ?>" required>
            </div>
        </div>
        <label class="caixa-selecao">
            <input type="checkbox" name="variacao_semana" value="1" <?= !empty($janela['variacao_semana']) ? 'checked' : '' ?>>
            <span>Variar por dia da semana (ajuste fino no painel de roteiro)</span>
        </label>
    </fieldset>

    <fieldset class="cartao">
        <legend>Grade de blocos</legend>
        <label for="granularidade">Granularidade</label>
        <select id="granularidade" name="granularidade">
            <option value="flexivel" <?= $granularidade === 'flexivel' ? 'selected' : '' ?>>Blocos flexíveis (padrão)</option>
            <option value="15" <?= $granularidade === '15' ? 'selected' : '' ?>>15 minutos</option>
            <option value="30" <?= $granularidade === '30' ? 'selected' : '' ?>>30 minutos</option>
            <option value="60" <?= $granularidade === '60' ? 'selected' : '' ?>>60 minutos</option>
        </select>
        <label class="caixa-selecao">
            <input type="checkbox" name="roteiro_ativo" value="1" <?= !empty($config['roteiro_ativo']) ? 'checked' : '' ?>>
            <span>Usar roteiro prescrito (os pais definem o que deve acontecer em cada bloco — opcional)</span>
        </label>
        <label for="tolerancia_atraso">Tolerância de atraso do bloco (minutos até ficar âmbar)</label>
        <input type="number" id="tolerancia_atraso" name="tolerancia_atraso" min="0" max="120"
               value="<?= (int)$config['tolerancia_atraso_minutos'] ?>">
        <label for="local_permanencia">Local de permanência da criança</label>
        <select id="local_permanencia" name="local_permanencia">
            <?php foreach (['casa' => 'Casa o dia todo', 'casa_escola' => 'Casa + escola', 'casa_creche' => 'Casa + creche', 'outro' => 'Outro'] as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $config['local_permanencia'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <fieldset class="cartao">
        <legend>Edição de registros</legend>
        <label for="regra_edicao">Quando o cuidador pode editar</label>
        <select id="regra_edicao" name="regra_edicao">
            <option value="livre" <?= $regra['tipo'] === 'livre' ? 'selected' : '' ?>>Livre — qualquer registro, a qualquer momento</option>
            <option value="mesmo_dia" <?= $regra['tipo'] === 'mesmo_dia' ? 'selected' : '' ?>>Mesmo dia (padrão)</option>
            <option value="janela_minutos" <?= $regra['tipo'] === 'janela_minutos' ? 'selected' : '' ?>>Até N minutos após criar</option>
            <option value="somente_com_aprovacao" <?= $regra['tipo'] === 'somente_com_aprovacao' ? 'selected' : '' ?>>Somente com aprovação dos pais</option>
        </select>
        <label for="janela_minutos">N minutos (para a opção "até N minutos")</label>
        <input type="number" id="janela_minutos" name="janela_minutos" min="5" max="1440" value="<?= (int)$regra['janela_minutos'] ?>">
        <label class="caixa-selecao">
            <input type="checkbox" name="edicao_dias_anteriores" value="1" <?= !empty($config['edicao_dias_anteriores']) ? 'checked' : '' ?>>
            <span>Permitir edição direta de dias anteriores (desmarcado: vira solicitação para os pais aprovarem)</span>
        </label>
    </fieldset>

    <fieldset class="cartao">
        <legend>Alerta de omissão</legend>
        <label class="caixa-selecao">
            <input type="checkbox" name="alerta_ativo" value="1" <?= !empty($alerta['ativo']) ? 'checked' : '' ?>>
            <span>Avisar os pais se nada for registrado por muito tempo dentro da janela do dia</span>
        </label>
        <label for="alerta_minutos">Silêncio tolerado (minutos)</label>
        <input type="number" id="alerta_minutos" name="alerta_minutos" min="15" max="720" value="<?= (int)$alerta['minutos'] ?>">
    </fieldset>

    <fieldset class="cartao">
        <legend>Fotos nos registros</legend>
        <select name="fotos" aria-label="Política de fotos">
            <?php foreach (['obrigatoria' => 'Obrigatória', 'opcional' => 'Opcional (padrão)', 'desativada' => 'Desativada'] as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $config['fotos'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </fieldset>

    <fieldset class="cartao">
        <legend>Resumo diário</legend>
        <label class="caixa-selecao">
            <input type="checkbox" name="resumo_ativo" value="1" <?= !empty($resumo['ativo']) ? 'checked' : '' ?>>
            <span>Enviar resumo do dia em linguagem natural ao fim da janela</span>
        </label>
        <label for="resumo_horario">Horário de envio</label>
        <input type="time" id="resumo_horario" name="resumo_horario" value="<?= e($resumo['horario']) ?>">
        <p class="texto-apoio" style="margin-bottom:0">Canais:</p>
        <?php foreach (['whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'push' => 'Push (em breve)'] as $canal => $rotulo): ?>
            <label class="caixa-selecao" style="margin:.25rem 0">
                <input type="checkbox" name="resumo_canais[]" value="<?= e($canal) ?>"
                    <?= in_array($canal, (array)$resumo['canais'], true) ? 'checked' : '' ?>>
                <span><?= e($rotulo) ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <fieldset class="cartao">
        <legend>Retenção de dados (LGPD)</legend>
        <label for="retencao_meses">Expurgar registros após (meses) — vazio mantém tudo</label>
        <input type="number" id="retencao_meses" name="retencao_meses" min="6" max="120"
               value="<?= $config['retencao_meses'] === null ? '' : (int)$config['retencao_meses'] ?>">
    </fieldset>

    <button type="submit" class="botao botao-primario botao-largo">Salvar configurações</button>
</form>

<?php if ($historico !== []): ?>
    <details class="cartao" style="margin-top:1rem">
        <summary>Últimas alterações (<?= count($historico) ?>)</summary>
        <div class="tabela-rolavel"><table class="tabela">
            <thead><tr><th>Quando</th><th>Quem</th><th>Chave</th></tr></thead>
            <tbody>
            <?php foreach ($historico as $item): ?>
                <tr>
                    <td><?= e(data_br($item['criado_em'])) ?></td>
                    <td><?= e($item['usuario_nome']) ?></td>
                    <td><code><?= e($item['chave']) ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </details>
<?php endif; ?>
