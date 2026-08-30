<?php

use App\Core\Csrf;

/** @var string $tipo */
/** @var string $versao */
/** @var bool $jaAceitou */
?>
<h2>Termo de consentimento — versão <?= e($versao) ?></h2>

<div class="cartao termo-lgpd">
    <?php if ($tipo === 'cuidador'): ?>
        <p>Este termo explica, em linguagem direta, quais dados <strong>sobre você</strong> o
            Diário do Bebê registra enquanto você usa o sistema como cuidador(a):</p>
        <ul>
            <li><strong>Identificação:</strong> nome, e-mail e, se você informar, telefone de WhatsApp.</li>
            <li><strong>Horários de atividade:</strong> data e hora de cada registro que você criar ou
                editar, e os horários de entrada e saída do turno.</li>
            <li><strong>Registros de acesso:</strong> data, hora, endereço IP e navegador utilizados
                ao entrar e usar o sistema (exigência de segurança e auditoria).</li>
            <li><strong>Conteúdo dos registros:</strong> o que você escrever ou fotografar fica
                associado ao seu nome, visível para os responsáveis pela criança.</li>
        </ul>
        <p>O sistema <strong>não coleta geolocalização</strong>, não usa rastreadores de terceiros,
            não exibe anúncios e não envia seus dados para serviços de análise externos.</p>
        <p>Você pode solicitar aos responsáveis pela família a exportação ou a exclusão dos seus
            dados pessoais, conforme a Lei Geral de Proteção de Dados (Lei 13.709/2018).</p>
    <?php elseif ($tipo === 'leitor'): ?>
        <p>Você recebeu acesso de <strong>leitura</strong> ao diário desta família. Este termo registra
            o seu consentimento para o tratamento dos seguintes dados:</p>
        <ul>
            <li><strong>Identificação:</strong> nome e e-mail da sua conta.</li>
            <li><strong>Registros de acesso:</strong> data, hora, endereço IP e navegador
                (exigência de segurança e auditoria).</li>
        </ul>
        <p>O conteúdo que você visualiza é confidencial da família. O sistema não usa rastreadores
            de terceiros, não exibe anúncios e não envia dados para análise externa.</p>
    <?php else: ?>
        <p>Este termo registra o seu consentimento, como responsável, para o tratamento de dados
            pela plataforma Diário do Bebê:</p>
        <ul>
            <li><strong>Dados da criança:</strong> nome, nascimento e informações de saúde e rotina
                que a família decidir registrar — usados exclusivamente para o funcionamento do
                diário e visíveis apenas aos usuários autorizados da família.</li>
            <li><strong>Seus dados:</strong> nome, e-mail, telefone (se informado) e registros de
                acesso (data, hora, IP e navegador), por segurança e auditoria.</li>
            <li><strong>Fotos:</strong> armazenadas fora da área pública e servidas somente a
                usuários autenticados da família.</li>
        </ul>
        <p>O sistema não usa rastreadores de terceiros, não exibe anúncios e não envia dados para
            serviços de análise externos. A família pode solicitar a exportação ou a exclusão de
            todos os seus dados (Lei 13.709/2018 — LGPD).</p>
    <?php endif; ?>
</div>

<?php if ($jaAceitou): ?>
    <p class="texto-apoio">Você já aceitou esta versão do termo.</p>
    <a class="botao botao-primario" href="<?= e(url('home')) ?>">Continuar</a>
<?php else: ?>
    <form method="post" action="<?= e(url('lgpd.termo.aceitar', ['tipo' => $tipo])) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <label class="caixa-selecao">
            <input type="checkbox" name="aceito" value="sim" required>
            <span>Li e aceito o termo de consentimento acima.</span>
        </label>
        <button type="submit" class="botao botao-primario botao-largo">Aceitar e continuar</button>
    </form>
<?php endif; ?>
