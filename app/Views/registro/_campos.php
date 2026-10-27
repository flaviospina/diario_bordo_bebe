<?php
/**
 * Partial: renderiza os campos dinâmicos de uma categoria a partir do
 * schema_campos JSON — nenhum formulário de categoria é hardcoded.
 * Espera: array $schema (com 'campos') e array $dados (valores atuais).
 * Opcional: string $prefixoCampos — troca o prefixo padrão 'c_' (usado pelo
 * registro multi-atividade para separar os campos de cada categoria).
 */
/** @var array $schema */
/** @var array $dados */
?>
<?php foreach (($schema['campos'] ?? []) as $campo): ?>
    <?php
    $nome = ($prefixoCampos ?? 'c_') . $campo['nome'];
    $id = 'campo_' . (isset($prefixoCampos) ? $nome : $campo['nome']);
    $atual = $dados[$campo['nome']] ?? null;
    $obrigatorio = !empty($campo['obrigatorio']);
    ?>
    <label for="<?= e($id) ?>"><?= e($campo['rotulo']) ?><?= $obrigatorio ? ' *' : '' ?></label>
    <?php switch ($campo['tipo']):
        case 'opcoes': ?>
            <div class="grupo-opcoes" role="radiogroup" aria-label="<?= e($campo['rotulo']) ?>">
                <?php foreach (($campo['opcoes'] ?? []) as $opcao): ?>
                    <label class="opcao-botao">
                        <input type="radio" name="<?= e($nome) ?>" value="<?= e($opcao['valor']) ?>"
                            <?= $atual === $opcao['valor'] ? 'checked' : '' ?> <?= $obrigatorio ? 'required' : '' ?>>
                        <span><?= e($opcao['rotulo']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php break; ?>
        <?php case 'numero': ?>
            <input type="number" inputmode="decimal" id="<?= e($id) ?>" name="<?= e($nome) ?>"
                   value="<?= $atual !== null ? e((string)$atual) : '' ?>"
                   <?= isset($campo['minimo']) ? 'min="' . e((string)$campo['minimo']) . '"' : '' ?>
                   <?= isset($campo['maximo']) ? 'max="' . e((string)$campo['maximo']) . '"' : '' ?>
                   <?= isset($campo['passo']) ? 'step="' . e((string)$campo['passo']) . '"' : 'step="any"' ?>
                   <?= $obrigatorio ? 'required' : '' ?>>
            <?php if (!empty($campo['unidade'])): ?><small class="texto-apoio"><?= e($campo['unidade']) ?></small><?php endif; ?>
            <?php break; ?>
        <?php case 'duracao_minutos': ?>
            <input type="number" inputmode="numeric" id="<?= e($id) ?>" name="<?= e($nome) ?>" min="0" max="1440"
                   value="<?= $atual !== null ? e((string)$atual) : '' ?>" <?= $obrigatorio ? 'required' : '' ?>>
            <small class="texto-apoio">minutos</small>
            <?php break; ?>
        <?php case 'escala': ?>
            <div class="grupo-opcoes grupo-escala" role="radiogroup" aria-label="<?= e($campo['rotulo']) ?>">
                <?php $maximo = (int)($campo['maximo'] ?? 5);
                $carinhas = ['😢', '🙁', '😐', '🙂', '😄']; ?>
                <?php for ($nivel = 1; $nivel <= $maximo; $nivel++): ?>
                    <label class="opcao-botao opcao-escala">
                        <input type="radio" name="<?= e($nome) ?>" value="<?= $nivel ?>"
                            <?= (int)$atual === $nivel ? 'checked' : '' ?> <?= $obrigatorio ? 'required' : '' ?>>
                        <span><?= e($carinhas[$nivel - 1] ?? (string)$nivel) ?></span>
                    </label>
                <?php endfor; ?>
            </div>
            <?php break; ?>
        <?php case 'texto_longo': ?>
            <textarea id="<?= e($id) ?>" name="<?= e($nome) ?>" rows="3" maxlength="2000"
                      <?= $obrigatorio ? 'required' : '' ?>><?= $atual !== null ? e((string)$atual) : '' ?></textarea>
            <?php break; ?>
        <?php case 'alimentos': ?>
            <?php
            // Catálogo filtrado pela idade da criança (criar/varios têm $crianca; editar tem o slug no registro)
            $criancaCampo = $crianca ?? (isset($registro['crianca_slug'])
                ? (new \App\Repositories\RepositorioCriancas())->buscarPorSlug((string)$registro['crianca_slug']) : null);
            $idadeMesesCampo = !empty($criancaCampo['data_nascimento'])
                ? \App\Services\ServicoCrescimento::idadeEmMeses((string)$criancaCampo['data_nascimento'], hoje()) : null;
            $selecionados = is_array($atual) ? array_map('strval', $atual) : [];
            $grupos = \App\Services\ServicoAlimentacao::porIdade($idadeMesesCampo);
            $faseAlimentar = \App\Services\ServicoAlimentacao::fase($idadeMesesCampo);
            ?>
            <div class="alimentos-campo" data-alimentos>
                <p class="texto-apoio alimentos-fase"><strong><?= e($faseAlimentar['titulo']) ?>.</strong>
                    <?= e($faseAlimentar['texto']) ?>
                    <a href="<?= e(url('alimentacao')) ?>" target="_blank" rel="noopener">Ver o guia completo →</a></p>
                <input type="search" class="alimentos-busca" placeholder="Buscar alimento…" aria-label="Buscar alimento" data-alimentos-busca>
                <?php foreach ($grupos as $chaveGrupo => $grupo): ?>
                    <?php if ($grupo['evitar'] || $grupo['agora'] === []) { continue; } ?>
                    <p class="grupo-folha"><?= e($grupo['icone']) ?> <?= e($grupo['rotulo']) ?></p>
                    <div class="grupo-opcoes grupo-alimentos">
                        <?php foreach ($grupo['agora'] as $item): ?>
                            <label class="opcao-botao opcao-alimento" data-nome="<?= e(mb_strtolower($item['nome'])) ?>"
                                   title="<?= e((string)($item['obs'] ?? '')) ?>">
                                <input type="checkbox" name="<?= e($nome) ?>[]" value="<?= e($item['slug']) ?>"
                                    <?= in_array($item['slug'], $selecionados, true) ? 'checked' : '' ?>>
                                <span><?= e($item['nome']) ?><?= !empty($item['alergenico']) ? ' <small>⚠️</small>' : '' ?><?= !empty($item['engasgo']) ? ' <small>✂️</small>' : '' ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <?php $aindaNao = []; foreach ($grupos as $grupo) { if (!$grupo['evitar']) { $aindaNao = array_merge($aindaNao, $grupo['depois']); } } ?>
                <?php if ($aindaNao !== []): ?>
                    <details class="alimentos-depois">
                        <summary class="texto-apoio">Ainda não indicados para a idade (<?= count($aindaNao) ?>)</summary>
                        <p class="texto-apoio" style="margin:.3rem 0 0">
                            <?php foreach ($aindaNao as $item): ?>
                                <span class="chip-depois"><?= e($item['nome']) ?> · a partir de <?= (int)$item['idade'] ?> m</span>
                            <?php endforeach; ?>
                        </p>
                    </details>
                <?php endif; ?>
                <p class="texto-apoio" style="font-size:.78rem; margin:.4rem 0 0">⚠️ alergênico comum (um por vez, observe reações) · ✂️ cortar/amassar — risco de engasgo. Quem libera cada alimento é o pediatra.</p>
            </div>
            <?php break; ?>
        <?php default: ?>
            <input type="text" id="<?= e($id) ?>" name="<?= e($nome) ?>" maxlength="255"
                   value="<?= $atual !== null ? e((string)$atual) : '' ?>" <?= $obrigatorio ? 'required' : '' ?>>
    <?php endswitch; ?>
<?php endforeach; ?>
