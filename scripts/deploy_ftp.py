#!/usr/bin/env python3
"""
Deploy do Diário do Bebê para a HostGator via FTPS.

Sobe a árvore do projeto (inclusive arquivos ocultos como .htaccess) para a
pasta remota, recriando a estrutura. Credenciais só por variáveis de
ambiente — nunca ficam gravadas em arquivo.

Variáveis de ambiente:
  DIARIOBEBE_FTP_HOST   host FTP (ex.: ftp.itthrive.com.br ou gatorXXXX.hostgator.com) [obrigatória]
  DIARIOBEBE_FTP_USER   usuário FTP                                                     [obrigatória]
  DIARIOBEBE_FTP_PASS   senha FTP                                                       [obrigatória]
  DIARIOBEBE_FTP_DIR    pasta remota (padrão: /public_html/diariobebe)
  DIARIOBEBE_FTP_PORT   porta (padrão: 21)
  DIARIOBEBE_FTP_PLAIN  "1" força FTP sem TLS (não recomendado)

Uso:
  python3 scripts/deploy_ftp.py [pasta_local]     # padrão: raiz do projeto

O .env NUNCA é enviado (crie-o direto no servidor, fora do webroot).
Também ficam de fora: .git, scripts/, docs/ e lixo de editor/SO.
"""
import ftplib
import os
import sys
from pathlib import Path

EXCLUIR_PASTAS = {".git", "scripts", "docs", "__pycache__", "node_modules"}
EXCLUIR_ARQUIVOS = {".env", ".DS_Store", "Thumbs.db"}


def env(nome, padrao=None, obrigatoria=False):
    valor = os.environ.get(nome, padrao)
    if obrigatoria and not valor:
        sys.exit(
            f"ERRO: variável de ambiente {nome} não definida.\n"
            "Defina DIARIOBEBE_FTP_HOST, DIARIOBEBE_FTP_USER e DIARIOBEBE_FTP_PASS "
            "antes de rodar (ver docs/deploy-hostgator.md)."
        )
    return valor


def conectar():
    host = env("DIARIOBEBE_FTP_HOST", obrigatoria=True)
    usuario = env("DIARIOBEBE_FTP_USER", obrigatoria=True)
    senha = env("DIARIOBEBE_FTP_PASS", obrigatoria=True)
    porta = int(env("DIARIOBEBE_FTP_PORT", "21"))
    sem_tls = env("DIARIOBEBE_FTP_PLAIN", "") == "1"

    if not sem_tls:
        try:
            ftp = ftplib.FTP_TLS()
            ftp.connect(host, porta, timeout=30)
            ftp.login(usuario, senha)
            ftp.prot_p()
            print(f"Conectado (FTPS) a {host}:{porta} como {usuario}")
            return ftp
        except Exception as e:
            print(f"FTPS falhou ({e}); tentando FTP simples...")

    ftp = ftplib.FTP()
    ftp.connect(host, porta, timeout=30)
    ftp.login(usuario, senha)
    print(f"Conectado (FTP) a {host}:{porta} como {usuario}")
    return ftp


def garantir_pasta_remota(ftp, pasta):
    partes = [p for p in pasta.split("/") if p]
    if pasta.startswith("/"):
        ftp.cwd("/")
    for parte in partes:
        try:
            ftp.cwd(parte)
        except ftplib.error_perm:
            ftp.mkd(parte)
            ftp.cwd(parte)


def deve_enviar(caminho_relativo: Path) -> bool:
    if any(parte in EXCLUIR_PASTAS for parte in caminho_relativo.parts):
        return False
    if caminho_relativo.name in EXCLUIR_ARQUIVOS:
        return False
    return True


def enviar_arvore(ftp, raiz_local, raiz_remota):
    raiz_local = Path(raiz_local)
    arquivos = sorted(
        p for p in raiz_local.rglob("*")
        if p.is_file() and deve_enviar(p.relative_to(raiz_local))
    )
    if not arquivos:
        sys.exit(f"ERRO: nenhum arquivo encontrado em {raiz_local}.")

    garantir_pasta_remota(ftp, raiz_remota)
    ftp.cwd("/")
    total = 0
    for arquivo in arquivos:
        relativo = arquivo.relative_to(raiz_local)
        pasta_relativa = "/".join(relativo.parts[:-1])
        pasta_destino = raiz_remota.rstrip("/")
        if pasta_relativa:
            pasta_destino = f"{pasta_destino}/{pasta_relativa}"
        garantir_pasta_remota(ftp, pasta_destino)
        with open(arquivo, "rb") as fh:
            ftp.storbinary(f"STOR {relativo.name}", fh)
        ftp.cwd("/")
        total += 1
        print(f"  ↑ {relativo}")
    return total


def main():
    local = sys.argv[1] if len(sys.argv) > 1 else str(Path(__file__).resolve().parent.parent)
    if not Path(local).is_dir():
        sys.exit(f"ERRO: pasta local '{local}' não encontrada.")
    remota = env("DIARIOBEBE_FTP_DIR", "/public_html/diariobebe")

    print(f"Enviando '{local}'  →  '{remota}'  (HostGator)")
    ftp = conectar()
    try:
        total = enviar_arvore(ftp, local, remota)
    finally:
        try:
            ftp.quit()
        except Exception:
            ftp.close()
    print(f"\nConcluído: {total} arquivo(s) enviados.")
    print("Próximos passos: criar/conferir o .env no servidor e rodar /install/migrate.php?token=...")


if __name__ == "__main__":
    main()
