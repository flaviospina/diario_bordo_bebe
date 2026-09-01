<?php
/** @var string $codigo */
/** @var array $crianca */
/** @var ?string $idade */

$rotuloSexo = ['feminino' => 'Feminino', 'masculino' => 'Masculino'];
?>
<div class="cartao cartao-identidade">
    <span class="avatar-crianca avatar-ficha"><?= e(mb_strtoupper(mb_substr((string)$crianca['nome'], 0, 1))) ?></span>
    <div class="identidade-texto">
        <h2><?= e($crianca['nome']) ?></h2>
        <p class="texto-apoio">
            <?= $idade !== null ? e($idade) : 'idade não informada' ?>
            <?= isset($rotuloSexo[$crianca['sexo']]) ? ' · ' . e($rotuloSexo[$crianca['sexo']]) : '' ?>
            <?= $crianca['data_nascimento'] !== null ? ' · nasc. ' . e(data_br($crianca['data_nascimento'], 'd/m/Y')) : '' ?>
        </p>
    </div>
</div>

<div class="acoes-pagina">
    <a class="botao botao-contorno botao-pequeno" href="<?= e(url('consulta.ficha', ['codigo' => $codigo])) ?>">
        <?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?> Voltar à ficha</a>
</div>

<div class="cartao cartao-envio-pediatra" id="envio">
    <h3><?= icone_ui('estetoscopio', 18, '#3E6A64') ?> Registrar a consulta de hoje</h3>
    <p class="texto-apoio">Preencha o que houver — só o seu nome é obrigatório.
        Ao enviar, este link se encerra e a família recebe os dados para confirmar.</p>

    <form class="formulario" data-envio-consulta
          data-endpoint="<?= e(url('api.consulta.enviar', ['codigo' => $codigo])) ?>">
        <div class="linha-campos">
            <div>
                <label for="profissional_nome">Seu nome *</label>
                <input type="text" id="profissional_nome" name="profissional_nome" required maxlength="120"
                       placeholder="Dra. Ana Souza">
            </div>
            <div>
                <label for="medido_em">Data da consulta</label>
                <input type="date" id="medido_em" name="medido_em" value="<?= e(hoje()) ?>">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="conselho_sigla">Conselho</label>
                <input type="text" id="conselho_sigla" name="conselho_sigla" maxlength="10" placeholder="CRM">
            </div>
            <div>
                <label for="conselho_numero">Número</label>
                <input type="text" id="conselho_numero" name="conselho_numero" maxlength="20" placeholder="123456">
            </div>
            <div>
                <label for="conselho_uf">UF</label>
                <input type="text" id="conselho_uf" name="conselho_uf" maxlength="2" placeholder="SP">
            </div>
        </div>

        <div class="linha-campos">
            <div>
                <label for="peso_kg">Peso (kg)</label>
                <input type="number" id="peso_kg" name="peso_kg" min="0.3" max="60" step="0.001" placeholder="7,450">
            </div>
            <div>
                <label for="altura_cm">Altura (cm)</label>
                <input type="number" id="altura_cm" name="altura_cm" min="20" max="160" step="0.1" placeholder="68,5">
            </div>
            <div>
                <label for="pc_cm">Perím. cefálico (cm)</label>
                <input type="number" id="pc_cm" name="pc_cm" min="20" max="70" step="0.1" placeholder="43,0">
            </div>
        </div>

        <label for="vacinas">Vacinas aplicadas hoje <small>(uma por linha: imunizante — dose)</small></label>
        <textarea id="vacinas" name="vacinas" rows="2" placeholder="Pentavalente — 2ª dose&#10;VIP — 2ª dose"></textarea>

        <label for="motivo">Motivo da consulta</label>
        <input type="text" id="motivo" name="motivo" maxlength="200" placeholder="Puericultura de rotina">
        <label for="conduta">Conduta / orientações</label>
        <textarea id="conduta" name="conduta" rows="3"></textarea>
        <div class="linha-campos">
            <div>
                <label for="retorno_em">Retorno sugerido</label>
                <input type="date" id="retorno_em" name="retorno_em">
            </div>
        </div>

        <div class="alerta alerta-erro" data-erro-envio hidden></div>
        <button type="submit" class="botao botao-primario botao-largo">Enviar para a família</button>
    </form>

    <div class="cartao-envio-ok" data-envio-ok hidden>
        <span class="selo-categoria selo-envio-ok"><?= icone_ui('check', 24, '#2F6B4F', 2.4) ?></span>
        <h3>Enviado!</h3>
        <p class="texto-apoio">A família recebeu os dados e confirma no aplicativo.
            Este link foi encerrado — obrigado!</p>
    </div>
</div>

<script src="<?= e(asset('js/consulta.js')) ?>" defer></script>
