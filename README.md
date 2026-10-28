# Saúde Select — Funil BuscarPlanos

Funil público de cotação de planos de saúde. O visitante informa:
1. quem será o titular;
2. como quer contratar (CPF, PME ou adesão);
3. quantas pessoas entram no plano.

Depois escolhe entre os planos disponíveis na sua região e recebe pelo WhatsApp um PDF com preços e rede credenciada. A equipe comercial recebe o lead por e-mail e por WhatsApp.

O sistema **não tem cadastro próprio de planos**. Os dados vêm do [SimuladorOnline](https://app.simuladoronline.com), acessado com a conta do corretor. Sem essas credenciais o funil abre, mas não lista planos.

## Sumário

1. [Stack](#1-stack)
2. [Como funciona](#2-como-funciona)
3. [Rotas e contratos](#3-rotas-e-contratos)
4. [Requisitos](#4-requisitos)
5. [Rodando localmente](#5-rodando-localmente)
6. [Variáveis de ambiente](#6-variáveis-de-ambiente)
7. [Deploy em produção](#7-deploy-em-produção)
8. [Configurando as integrações](#8-configurando-as-integrações)
9. [Valores fixos no código](#9-valores-fixos-no-código)
10. [Manutenção](#10-manutenção)
11. [Solução de problemas](#11-solução-de-problemas)
12. [Testes e lint](#12-testes-e-lint)
13. [Estrutura do projeto](#13-estrutura-do-projeto)
14. [Limitações conhecidas](#14-limitações-conhecidas)
15. [Especificações](#15-especificações)

---

## 1. Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP **8.4+**, Laravel 12 |
| Funil (frontend) | Blade + JavaScript puro em uma única página. Tailwind, Font Awesome e Google Fonts vêm de CDN. PWA (`public/manifest.json`, `public/sw.js`) |
| Área autenticada (herdada do starter kit) | Livewire 4, Flux, Fortify (login, cadastro, 2FA) |
| Build de assets | Vite 7, Tailwind CSS 4 (só a área autenticada usa) |
| PDF | DomPDF (`barryvdh/laravel-dompdf`) |
| Banco | SQLite por padrão. Guarda **apenas** usuários, sessões, cache e filas; não há dados de negócio |
| E-mail | Mailer do Laravel (SMTP em produção, `log` em desenvolvimento) |
| WhatsApp | [Uazapi](https://uazapi.com) via HTTP |
| Testes e lint | Pest 4, Laravel Pint |

---

## 2. Como funciona

### Visão geral

```
 NAVEGADOR  (resources/views/index.blade.php: página única, estado em JS)
 |
 |  Ao abrir:  localização por IP (ipapi.co) --> estado --> id de região
 |             (opcional) GPS via Nominatim  |  (opcional) busca manual de cidade
 |
 |  Passo 1  Nome do titular
 |  Passo 2  Perfil: CPF | PME | adesão (+ profissão)
 |  Passo 3  Vidas por faixa etária (0-18 ... 59+)
 |  Passo 4  POST /api/planos/buscar  ----------+  lista e seleção de planos
 |           POST /proposta/gerar  -------------+  gera simulação e PDFs
 |  Passo 5  POST /proposta/enviar  ------------+  envia por WhatsApp e e-mail
 |  Final    tela de sucesso                    |
 |                                              |
 |  (cada passo é um partial HTML carregado via GET /step/{n})
 v                                              v
 LARAVEL
 +--------------------+     +-----------------------------------+
 | StepController     |---->| SimuladorOnlineService            |---> app.simuladoronline.com
 | PropostaController |     |  login (token CSRF + cookie)      |
 | SendProposalCtrl   |     |  busca e filtro de planos         |
 +--------------------+     |  simulação -> HTML sistema/cliente|
          |                 +-----------------------------------+
          +--> PdfService (DomPDF) -> storage/app/private/temp_propostas/
          +--> sessão "simulacao_atual" (HTMLs + caminhos dos PDFs)
          +--> Mail (ProposalSystemMail) + WhatsAppService (Uazapi)
```

### Passo a passo

| Etapa | O que o visitante faz | O que o sistema faz |
|---|---|---|
| Abertura | Nada | Estima cidade e estado pelo IP (`ipapi.co`). O estado vira o id de região do SimuladorOnline (`REGION_MAP`; se falhar, **Rio de Janeiro**, região 2). O visitante pode refinar pelo GPS ou buscando a cidade. |
| 1 — Nome | Digita o nome do titular | Guarda o nome, que vai para a simulação e para a mensagem do WhatsApp. |
| 2 — Perfil | Escolhe CPF, PME ou adesão | No perfil **adesão**, exige uma profissão de uma lista fixa de cerca de 200 itens, com os ids do SimuladorOnline. Se nenhuma servir, oferece seguir por CPF. No perfil PME, exibe um aviso. |
| 3 — Vidas | Informa quantas pessoas há em cada faixa etária | Aplica as regras de aceitação (abaixo). |
| 4 — Planos | Seleciona um ou mais planos | Chama a busca de planos. Ordena pelo número contido no nome do plano e mostra de 5 em 5. Ao confirmar, gera a proposta e mostra um aviso se algum plano cobre só pronto-socorro. |
| 5 — WhatsApp | Informa o celular com DDD | Valida (mínimo de 10 dígitos) e dispara o envio. |
| Final | — | Tela de sucesso, com botão para recomendar pelo WhatsApp e o texto de privacidade (LGPD). |

**Regras do passo 3:**
- é preciso informar pelo menos uma vida;
- se houver **só menores (0-18)** e o perfil for PME ou adesão, o funil avisa e muda o perfil para CPF;
- **PME com 1 vida** oferece adicionar um dependente ou seguir por CPF.

### Busca de planos

O perfil define a tabela consultada no SimuladorOnline:

| Perfil | Tabela |
|---|---|
| PME | 3 |
| Adesão | 4 (com a profissão) |
| CPF com 2 ou mais vidas | 1 (familiar) |
| CPF com 1 vida | 2 (individual) |

Sobre o resultado, o sistema:
- descarta planos ambulatoriais (AMB);
- descarta planos com idade mínima (`30+`, `40+`, `50+`, `59+`, "sênior") quando ninguém informado alcança essa idade;
- mantém **um plano por operadora para cada combinação de acomodação (enfermaria/apartamento) e coparticipação (sem/parcial/total)**.

### Geração da proposta

1. Faz login no SimuladorOnline e gera a simulação oficial dos planos escolhidos.
2. Recorta duas versões do HTML:
   - **sistema:** a simulação completa, para a equipe;
   - **cliente:** só logotipo, data, primeira tabela de preços e "Rede Credenciada" de cada operadora.

   As imagens são baixadas e embutidas.
3. Gera os dois PDFs **já nesse momento**, para o envio do passo 5 não estourar o tempo limite, e salva em `storage/app/private/temp_propostas/`.
4. Marca como "só pronto-socorro" os planos cuja rede credenciada não tem nenhum prestador marcado com `H` (internação) nem `NE` (não especificado).
5. Guarda tudo na **sessão** do visitante (`simulacao_atual`).

### Envio da proposta

Nesta ordem:
1. **E-mail** para o endereço administrativo, com telefone e localização no corpo e o PDF do sistema anexado.
2. **WhatsApp do cliente:** o PDF do cliente.
3. **WhatsApp do cliente:** a mensagem de acompanhamento, com a cidade da cotação.
4. **WhatsApp da equipe:** o alerta "NOVO LEAD CAPTURADO" (nome, localização, perfil, vidas e telefone).
5. **WhatsApp da equipe:** o PDF do sistema.
6. Apaga os dois PDFs temporários.

A localização aparece como "Cidade - Estado" (ou só o valor conhecido). Sem nenhum dos dois, as mensagens mostram "Não informada". Os PDFs trazem a mesma localização numa linha "Cidade: …" no topo, omitida quando não há localização.

O telefone é normalizado antes de enviar: mantém só os dígitos e acrescenta `55` quando tem menos de 12 dígitos.

---

## 3. Rotas e contratos

### Funil

| Método | Rota | Uso |
|---|---|---|
| GET | `/` | Página do funil |
| GET | `/step/{step}` | HTML do passo 1 a 5 (404 fora do intervalo) |
| GET | `/step-final` | HTML da tela de sucesso |
| POST | `/api/planos/buscar` | Busca de planos |
| POST | `/proposta/gerar` | Gera simulação, HTMLs e PDFs e guarda na sessão |
| POST | `/proposta/enviar` | Envia a proposta |
| GET | `/proposta` | Página com a proposta do cliente (depuração) |
| GET | `/proposta/cliente` | HTML da versão do cliente (depuração) |
| GET | `/proposta/sistema` | HTML da versão completa (depuração) |
| GET | `/up` | Health check do Laravel (200 se a aplicação sobe) |

As rotas `POST` exigem o token CSRF. O funil lê o token da meta tag `csrf-token` e o envia no cabeçalho `X-CSRF-TOKEN`. Sem ele, a resposta é **419**.

### Fora do funil

| Rota | Situação |
|---|---|
| `GET /api/hospitais/buscar?q=&regiao=` | Busca de hospitais no SimuladorOnline. Não é usada pelo funil atual. |
| `GET /admin` | Página `welcome` padrão do starter kit. |
| `/login`, `/register`, `/dashboard`, `/settings/*` | Área autenticada do starter kit. **O cadastro está aberto** (veja [Limitações](#14-limitações-conhecidas)). |

### `POST /api/planos/buscar`

```json
{
  "profile": "cpf",
  "lives": { "29-33": 2, "0-18": 1 },
  "regiao": 1,
  "nome": "Ana Silva",
  "profession_id": null
}
```

| Campo | Regra |
|---|---|
| `profile` | Obrigatório: `cpf`, `pme` ou `adesao` |
| `lives` | Obrigatório. Chaves: `0-18`, `19-23`, `24-28`, `29-33`, `34-38`, `39-43`, `44-48`, `49-53`, `54-58`, `59+` |
| `regiao` | Opcional. Padrão: 2 (RJ) |
| `profession_id` | Opcional. Só é usado no perfil adesão |

Resposta 200:

```json
{
  "success": true,
  "total": 1,
  "planos": [
    {
      "id": 408270, "operadora": "AMIL", "operadora_logo": "https://…",
      "operadora_descricao": "…", "nome": "Amil S380", "acomodacao": "Apartamento",
      "acomodacao_sigla": "A", "vigencia": "…"
    }
  ]
}
```

Erros: **422** (validação) ou **500** com `{"success": false, "error": "…"}`. Com `APP_DEBUG=true`, a resposta 500 também traz o `trace`.

### `POST /proposta/gerar`

```json
{ "planIds": [408270, 420159], "lives": { "29-33": 2 }, "profile": "cpf", "nome": "Ana Silva", "profession_id": null, "city": "Niterói", "state_name": "Rio de Janeiro" }
```

`city` e `state_name` são opcionais: a cidade e o estado selecionados no funil. Ficam na sessão e aparecem como "Niterói - Rio de Janeiro" nos PDFs, no e-mail e nas mensagens de WhatsApp.

Resposta 200:

```json
{
  "success": true,
  "client_html": "<div class=\"simulacao printable\">…",
  "plans_without_internacao": [{ "name": "…", "reason": "no_elective" }]
}
```

### `POST /proposta/enviar`

```json
{ "phone": "21988887777" }
```

| Status | Corpo |
|---|---|
| 200 | `{"success": true, "message": "Proposta enviada com sucesso!"}` |
| 404 | `{"success": false, "message": "Nenhuma simulação encontrada."}` |
| 400 | `"Telefone não informado."` ou `"PDFs não encontrados. Por favor, refaça a simulação."` |
| 500 | `{"success": false, "error": "…"}`, por exemplo falha no SMTP |

---

## 4. Requisitos

### Servidor (produção)

| Item | Versão / detalhe |
|---|---|
| PHP | **8.4 ou superior**. O `composer.lock` atual tem pacotes Symfony que exigem 8.4; em 8.2 ou 8.3 o `composer install` falha. |
| Extensões PHP | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_sqlite` (ou `pdo_mysql`), `session`, `tokenizer`, `xml` e **`gd`** (o DomPDF usa para as imagens do PDF) |
| `php.ini` | `memory_limit` ≥ **256M** (DomPDF); `max_execution_time` ≥ **120** (a geração da proposta faz várias chamadas ao SimuladorOnline e dois PDFs) |
| Servidor web | Nginx + PHP-FPM ou Apache com `mod_rewrite` |
| Composer | 2.x |
| HTTPS | **Obrigatório.** GPS, service worker (PWA) e cookies seguros só funcionam em HTTPS. |
| Saída de rede (HTTPS) | `app.simuladoronline.com`, o host da sua instância Uazapi, o servidor SMTP e os hosts das imagens das operadoras (baixadas para o PDF) |
| Node.js | **Não é necessário no servidor** se você fizer o build dos assets antes e enviar `public/build/` |

Para conferir as extensões no servidor:

```bash
php -v
php -m
composer check-platform-reqs --no-dev
```

### Máquina de desenvolvimento

PHP 8.4+ com as mesmas extensões, Composer 2 e Node.js 20+ com npm.

---

## 5. Rodando localmente

```bash
git clone https://github.com/MarcellyGuimaraes/saude-select.git
cd saude-select
composer run setup
```

O `setup` faz, em sequência:
1. `composer install`;
2. copia `.env.example` para `.env`;
3. `php artisan key:generate`;
4. `php artisan migrate --force` (cria `database/database.sqlite` se não existir);
5. `npm install`;
6. `npm run build`.

Depois, preencha o `.env`:

```dotenv
SIMULADOR_ONLINE_USERNAME=seu-usuario
SIMULADOR_ONLINE_PASSWORD=sua-senha
MAIL_MAILER=log
```

E suba o ambiente:

```bash
composer run dev
```

O comando sobe em paralelo o `php artisan serve` (porta 8000), o `php artisan queue:listen` e o `npm run dev`. Acesse **http://localhost:8000**.

- **E-mail:** com `MAIL_MAILER=log`, o e-mail (com o PDF) é gravado em `storage/logs/laravel.log`.
- **WhatsApp:** sem `UAZAPI_TOKEN`, nada é enviado e o log registra `WhatsAppService: Uazapi keys not configured.`
- **GPS:** em `http://localhost` ele funciona, porque o navegador trata localhost como seguro.

> **Use sempre `composer install`, nunca `composer update`**, até a correção do item 6 das [Limitações](#14-limitações-conhecidas). Um `update` remove o DomPDF.

<details>
<summary>Passo a passo manual (sem o script <code>setup</code>)</summary>

```bash
composer install
cp .env.example .env            # PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate             # aceite criar o database.sqlite
npm install
npm run build                   # ou npm run dev, em outro terminal
php artisan serve
```

</details>

---

## 6. Variáveis de ambiente

Referência completa no [`.env.example`](.env.example). A tabela mostra o valor de desenvolvimento e o recomendado para produção.

### Aplicação

| Variável | Desenvolvimento | Produção | Observação |
|---|---|---|---|
| `APP_NAME` | `Laravel` | `BuscarPlanos` | Usado como nome do remetente de e-mail |
| `APP_ENV` | `local` | `production` | Em `production`, comandos destrutivos de banco (`migrate:fresh` etc.) ficam bloqueados e a senha da área autenticada passa a ser forte |
| `APP_KEY` | gerada | **gerada no servidor** | `php artisan key:generate`. Não reutilize a do desenvolvimento. Trocar invalida as sessões |
| `APP_DEBUG` | `true` | **`false`** | Com `true`, os erros expõem trace, caminhos e respostas brutas do SimuladorOnline |
| `APP_URL` | `http://localhost` | `https://seudominio.com.br` | Base para URLs geradas pelo Laravel |
| `APP_LOCALE` | `en` | `pt_BR` (opcional) | Afeta as mensagens de validação (hoje em inglês) |

### Banco, sessão, cache e fila

| Variável | Valor | Observação |
|---|---|---|
| `DB_CONNECTION` | `sqlite` | Suficiente para o volume do funil. Para MySQL, use `mysql` e preencha `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| `SESSION_DRIVER` | `database` | **A simulação fica na sessão.** As migrations precisam ter rodado. A sessão guarda o HTML da proposta, então não use `cookie` |
| `SESSION_LIFETIME` | `120` | Minutos até a sessão expirar. Uma sessão expirada no passo 5 dá "Nenhuma simulação encontrada." |
| `SESSION_SECURE_COOKIE` | `true` em produção | Não está no `.env.example`; acrescente ao usar HTTPS |
| `CACHE_STORE` | `database` | — |
| `QUEUE_CONNECTION` | `database` | Nenhum job usa fila hoje: e-mail e WhatsApp são síncronos. Não é preciso worker em produção |

### Logs

| Variável | Produção | Observação |
|---|---|---|
| `LOG_STACK` | `daily` | Em vez de `single`, para os logs girarem por dia |
| `LOG_LEVEL` | `info` | Com `debug` o log cresce rápido. `info` mantém os registros de envio do WhatsApp |

### E-mail

| Variável | Produção | Observação |
|---|---|---|
| `MAIL_MAILER` | `smtp` | Em desenvolvimento, `log` |
| `MAIL_SCHEME` | `smtps` (porta 465) ou vazio (porta 587, STARTTLS) | — |
| `MAIL_HOST` / `MAIL_PORT` | Do seu provedor | Exemplo: `smtp.hostinger.com` / `465`; `smtp.gmail.com` / `587` |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Conta de envio | No Gmail, use **senha de app** |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | `nao-responda@seudominio.com.br` / `"${APP_NAME}"` | O remetente precisa ser aceito pelo SMTP |

> O **destinatário** do e-mail não é configurável: está fixo no código. Veja [Valores fixos](#9-valores-fixos-no-código).

### SimuladorOnline

| Variável | Obrigatória | Padrão / efeito |
|---|---|---|
| `SIMULADOR_ONLINE_USERNAME` / `SIMULADOR_ONLINE_PASSWORD` | **Sim** | Sem elas: "Credenciais não configuradas no .env" na busca e na proposta |
| `SIMULADOR_ONLINE_BASE_URL` | Não | `https://app.simuladoronline.com` |
| `SIMULADOR_ONLINE_LOGIN_PATH` | Não | `/login/7789`. É o caminho da página de login **da sua conta/corretora**; confira no navegador |
| `SIMULADOR_ONLINE_CORRETOR_EMAIL` | Não | Vai no campo "e-mail do corretor" da simulação |
| `SIMULADOR_ONLINE_VERIFY_SSL` | Não | `false`. Pode ser `true` se o servidor tiver os certificados CA atualizados |

### WhatsApp (Uazapi)

| Variável | Obrigatória | Efeito |
|---|---|---|
| `UAZAPI_BASE_URL` | Sim, em produção | URL da sua instância, sem barra final. Exemplo: `https://suaconta.uazapi.com` |
| `UAZAPI_TOKEN` | Sim, em produção | Token da instância. Sem ele, nada é enviado e o log só registra um aviso |
| `UAZAPI_INSTANCE` | Não | **Não é lida pelo código hoje** |

### Exemplo de `.env` de produção

```dotenv
APP_NAME=BuscarPlanos
APP_ENV=production
APP_KEY=                      # php artisan key:generate
APP_DEBUG=false
APP_URL=https://seudominio.com.br
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=465
MAIL_USERNAME=nao-responda@seudominio.com.br
MAIL_PASSWORD=********
MAIL_FROM_ADDRESS=nao-responda@seudominio.com.br
MAIL_FROM_NAME="${APP_NAME}"

SIMULADOR_ONLINE_BASE_URL=https://app.simuladoronline.com
SIMULADOR_ONLINE_LOGIN_PATH=/login/7789
SIMULADOR_ONLINE_USERNAME=********
SIMULADOR_ONLINE_PASSWORD=********
SIMULADOR_ONLINE_CORRETOR_EMAIL=corretor@seudominio.com.br
SIMULADOR_ONLINE_VERIFY_SSL=false

UAZAPI_BASE_URL=https://suaconta.uazapi.com
UAZAPI_TOKEN=********
```

---

## 7. Deploy em produção

### 7.1 Checklist de servidor novo

- [ ] PHP 8.4+ com as extensões da [seção 4](#4-requisitos), `memory_limit` ≥ 256M e `max_execution_time` ≥ 120
- [ ] Composer 2 instalado
- [ ] Domínio apontando para o servidor e **certificado HTTPS** emitido
- [ ] O document root aponta para `public/`, ou o `.htaccess` da raiz está ativo (hospedagem compartilhada)
- [ ] Código no servidor (`git clone` ou upload) e `composer install --no-dev --optimize-autoloader`
- [ ] `public/build/` presente: rode `npm ci && npm run build` no servidor ou envie a pasta já gerada
- [ ] `.env` de produção criado ([modelo](#exemplo-de-env-de-produção)) e `php artisan key:generate`
- [ ] `php artisan migrate --force`
- [ ] Permissão de escrita em `storage/`, `bootstrap/cache/` e `database/` (por causa do SQLite)
- [ ] `php artisan optimize`
- [ ] Integrações configuradas e testadas ([seção 8](#8-configurando-as-integrações))
- [ ] Valores fixos revistos ([seção 9](#9-valores-fixos-no-código))
- [ ] Cron de limpeza dos PDFs ([seção 10](#10-manutenção))
- [ ] Teste de ponta a ponta: simular, receber o PDF no WhatsApp, receber o e-mail
- [ ] `https://seudominio.com.br/up` responde 200

### 7.2 Opção A — VPS (Ubuntu 24.04, Nginx + PHP-FPM)

**Pacotes:**

```bash
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y nginx git unzip sqlite3 \
  php8.4-fpm php8.4-cli php8.4-curl php8.4-xml php8.4-mbstring php8.4-gd \
  php8.4-sqlite3 php8.4-zip php8.4-bcmath php8.4-intl
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# (opcional) Node, se for fazer o build no servidor
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
```

**Código:**

```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone https://github.com/MarcellyGuimaraes/saude-select.git
sudo chown -R $USER:www-data saude-select && cd saude-select

composer install --no-dev --optimize-autoloader
cp .env.example .env && nano .env        # preencha conforme a seção 6
php artisan key:generate
php artisan migrate --force
npm ci && npm run build                  # ou envie public/build pronto
php artisan optimize

sudo chown -R $USER:www-data storage bootstrap/cache database
sudo chmod -R ug+rwX storage bootstrap/cache database
```

**Ajustes no PHP-FPM**, em `/etc/php/8.4/fpm/conf.d/99-saude-select.ini`:

```ini
memory_limit = 256M
max_execution_time = 120
upload_max_filesize = 8M
post_max_size = 8M
```

```bash
sudo systemctl restart php8.4-fpm
```

**Nginx**, em `/etc/nginx/sites-available/saude-select`:

```nginx
server {
    listen 80;
    server_name seudominio.com.br www.seudominio.com.br;
    root /var/www/saude-select/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    charset utf-8;
    client_max_body_size 8M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;   # a geração da proposta pode passar de 60 s
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/saude-select /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
# HTTPS
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d seudominio.com.br -d www.seudominio.com.br
```

### 7.3 Opção B — Hospedagem compartilhada (Apache, como Hostinger ou cPanel)

O repositório já tem um **`.htaccess` na raiz** que redireciona tudo para `public/`. Com isso, o projeto funciona mesmo quando o domínio aponta para a pasta do projeto, e não para `public/`:

```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

1. **Versão do PHP:** no painel, selecione **PHP 8.4** e ative as extensões `gd`, `pdo_sqlite`, `mbstring`, `xml`, `curl`, `fileinfo` e `intl`. Ajuste `memory_limit` (256M) e `max_execution_time` (120).
2. **Código:** pelo SSH (ou pelo Git do painel), clone o repositório na pasta do domínio (por exemplo, `public_html`). Sem SSH, envie o projeto por FTP **incluindo `vendor/`** (gerado localmente com `composer install --no-dev`).
3. **Assets:** a hospedagem compartilhada geralmente não tem Node. Rode `npm run build` na sua máquina e envie a pasta `public/build/`.
4. **Configuração:** crie o `.env` (seção 6) e rode pelo SSH:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan optimize
   ```

   Sem SSH, gere a `APP_KEY` localmente (`php artisan key:generate --show`) e cole no `.env`. Envie também o `database/database.sqlite` já migrado.
5. **HTTPS:** ative o SSL gratuito no painel e force HTTPS.
6. **Cron:** cadastre a limpeza dos PDFs pelo painel ([seção 10](#10-manutenção)).

> Se der para apontar o document root direto para `public/`, prefira essa opção: assim nada fora de `public/` fica exposto, mesmo que o `.htaccess` da raiz seja removido por engano.

### 7.4 Atualizando (deploy de nova versão)

```bash
cd /var/www/saude-select
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build              # ou envie public/build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

**Rollback:** `git checkout <commit-anterior>` e repita os passos a partir do `composer install`. Não houve migrations novas além das do starter kit; se surgirem, use `php artisan migrate:rollback --step=1` antes.

> Depois de alterar o `.env` em produção, rode `php artisan optimize:clear && php artisan optimize`. Com a configuração em cache, o Laravel **não relê o `.env`**.

---

## 8. Configurando as integrações

### 8.1 SimuladorOnline

1. Use uma conta de corretor ativa. Entre no SimuladorOnline pelo navegador e anote o caminho da página de login (por exemplo `/login/7789`). Esse caminho vai em `SIMULADOR_ONLINE_LOGIN_PATH`.
2. Preencha usuário e senha no `.env`.
3. Teste a busca, na raiz do projeto:

   ```bash
   php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
   var_dump(count(app(App\Services\SimuladorOnlineService::class)->searchPlans(["profile" => "cpf", "lives" => ["29-33" => 1]])));'
   ```

   O comando deve imprimir `int(N)` com N > 0. Se imprimir a exceção `Credenciais não configuradas no .env`, faltam usuário e senha (ou a configuração está em cache).

   > Os testes desta seção usam `php -r` em vez de `php artisan tinker` porque o tinker pede para "confiar no projeto" (`Unrecognized project`) na primeira execução e fica parado esperando resposta em scripts. No PowerShell do Windows, rode os comandos pelo Git Bash ou troque as aspas.

**Como a integração funciona:** não há API oficial. O sistema imita o navegador.
- **Login:** extrai o token CSRF `login[_token]` da página de login e envia o formulário para `/login_check`.
- **Busca:** busca `/simulacao/nova` para pegar o `simulacao[_token]`, envia o formulário para `/simulacao/planos` e lê as linhas `tr.sim-op-planos`. Se a resposta não tiver planos, faz login de novo e repete uma vez.
- **Proposta:** envia o formulário para `/simulacao/nova` e recorta os blocos `div.simulacao.printable`, `div.operadora` e o bloco cujo `h4` é "Rede Credenciada".

> **Risco:** se o SimuladorOnline mudar o HTML (nomes dos tokens, classes ou o título "Rede Credenciada"), a busca ou a proposta param de funcionar sem nenhum erro de compilação. Sintomas na [seção 11](#11-solução-de-problemas).

### 8.2 Uazapi (WhatsApp)

1. Crie uma instância no painel da Uazapi e **conecte o número de WhatsApp da empresa** lendo o QR code.
2. Copie a **URL do servidor** da instância para `UAZAPI_BASE_URL` (sem barra final) e o **token da instância** para `UAZAPI_TOKEN`.
3. O sistema chama:
   - `POST {BASE_URL}/send/media`, com `number`, `type: document`, `file` (PDF em base64 com o prefixo `data:application/pdf;base64,`), `docName` e `text`;
   - `POST {BASE_URL}/send/text`, com `number` e `text`.

   Ambos usam o cabeçalho `token: {UAZAPI_TOKEN}`.
4. Teste:

   ```bash
   php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
   var_dump(app(App\Services\WhatsApp\WhatsAppService::class)->sendText("21999998888", "Teste"));'
   ```

   Troque o número pelo seu. O resultado esperado é `["success"] => bool(true)`.
   - `"error" => "Configuration missing"`: falta `UAZAPI_TOKEN` (ou `UAZAPI_BASE_URL`).
   - Outros erros: o log registra `WhatsAppService: Failed to send…` com o status e o corpo da resposta.
5. **Não esqueça** de trocar o número da equipe que recebe os leads ([seção 9](#9-valores-fixos-no-código)); hoje ele é um placeholder.

> Instâncias gratuitas ou de teste da Uazapi costumam expirar ou desconectar. Se os envios pararem, confira primeiro se a instância continua conectada.

### 8.3 E-mail (SMTP)

Preencha os `MAIL_*` (seção 6) e teste:

```bash
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Mail::raw("teste", fn ($m) => $m->to("voce@exemplo.com")->subject("Teste SMTP")); echo "ok\n";'
```

O comando imprime `ok` se o SMTP aceitou a mensagem. Com `MAIL_MAILER=log`, a mensagem aparece em `storage/logs/laravel.log`.

Uma falha no SMTP **interrompe o envio**: o e-mail é a primeira entrega, e o visitante vê o erro. Por isso, teste antes de publicar.

### 8.4 Serviços usados pelo navegador

Estes não precisam de configuração. Precisam apenas estar acessíveis para o visitante:

| Serviço | Uso |
|---|---|
| `ipapi.co` | Localização por IP. Plano gratuito com limite diário por IP do visitante |
| `nominatim.openstreetmap.org` | GPS → cidade e busca manual de cidade |
| `cdn.tailwindcss.com`, `cdnjs.cloudflare.com`, `fonts.googleapis.com` | Estilos, ícones e fontes do funil |

---

## 9. Valores fixos no código

Estes valores **não estão no `.env`**. Revise-os ao montar um servidor novo, trocar de domínio ou trocar a equipe:

| O quê | Valor atual | Onde |
|---|---|---|
| Destinatário do e-mail de lead | endereço pessoal fixo | `app/Http/Controllers/SendProposalController.php:45` |
| WhatsApp da equipe (alerta de lead e PDF do sistema) | `5521999999999` (**placeholder**) | `app/Http/Controllers/SendProposalController.php:90` |
| Textos da mensagem ao cliente ("Dossiê BuscarPlanos…") | — | `app/Http/Controllers/SendProposalController.php:55-60` (cliente) e `:80-88` (alerta da equipe) |
| Assunto do e-mail | `Nova Simulação - BuscarPlanos` | `app/Mail/ProposalSystemMail.php:26` |
| Região usada na geração da proposta | `2` (RJ), fixa | `app/Services/SimuladorOnlineService.php:501` |
| Região padrão (busca e funil) | `2` (RJ) | `app/Services/SimuladorOnlineService.php:13`, `resources/views/index.blade.php:267` |
| Mapa estado → região | `REGION_MAP` | `resources/views/index.blade.php`, logo após o objeto `state` |
| Lista de profissões (adesão) com ids | `VALID_PROFESSIONS` | `resources/views/index.blade.php` |
| Cidade exibida no passo 5 quando não há localização | `Niterói` | `resources/views/index.blade.php:877` |
| Link "Recomendar no WhatsApp" | `https://buscarplanos.com.br` | `resources/views/steps/step-final.blade.php:29` |
| Redes sociais (SEO) | `facebook.com/buscarplanos`, `instagram.com/buscarplanos` | `resources/views/index.blade.php:65-66` |
| Nome e textos de SEO, PWA | `BuscarPlanos` | `resources/views/index.blade.php` (cabeçalho), `public/manifest.json` |
| Logos | `public/logo-site.png`, `public/logo-app.png`, `public/favicon.svg`, `public/apple-touch-icon.png` | `public/` |
| Fuso horário | `UTC` | `config/app.php:68` |

---

## 10. Manutenção

### Logs

- Arquivo: `storage/logs/laravel.log`, ou `laravel-AAAA-MM-DD.log` com `LOG_STACK=daily`.
- Acompanhar ao vivo: `tail -f storage/logs/laravel.log`, ou `php artisan pail` (só com as dependências de desenvolvimento).
- Mensagens úteis para busca: `WhatsAppService:`, `Erro ao enviar proposta:`, `Credenciais não configuradas`, `Erro ao buscar planos`.

### PDFs temporários

Os PDFs só são apagados quando o envio termina. Quem abandona o funil no passo 5 deixa dois arquivos em `storage/app/private/temp_propostas/`. Agende uma limpeza:

```cron
# crontab -e   (remove PDFs com mais de 1 dia, todo dia às 3h)
0 3 * * * find /var/www/saude-select/storage/app/private/temp_propostas -name '*.pdf' -mtime +0 -delete
```

Na hospedagem compartilhada, cadastre o mesmo comando no "Cron Jobs" do painel, ajustando o caminho.

### Sessões

Com `SESSION_DRIVER=database`, o Laravel limpa as sessões expiradas automaticamente. Não é preciso cron.

### Backup

Faça backup de:
- `.env`;
- `database/database.sqlite`, se a área autenticada estiver em uso.

Não há dados de negócio no banco: simulações e propostas não são guardadas.

### Atualizar dependências

Só depois de corrigir o `composer.json` (item 6 das limitações). Até lá, use apenas `composer install`.

---

## 11. Solução de problemas

| Sintoma | Causa provável | Correção |
|---|---|---|
| Erro 500 em tudo, log com `No application encryption key has been specified` | `APP_KEY` vazia | `php artisan key:generate` e depois `php artisan optimize` |
| Erro 500, log com `Permission denied` em `storage/` ou `database/` | Permissões | `chown -R <usuario>:www-data storage bootstrap/cache database` e `chmod -R ug+rwX` nas mesmas pastas |
| `Vite manifest not found at: …/public/build/manifest.json` | Assets não compilados | `npm run build` ou envie `public/build/` |
| Ação no funil falha com status **419** | Sessão expirada ou token CSRF inválido | Recarregar a página. Se for constante, confira `SESSION_DRIVER`, as migrations e `SESSION_DOMAIN` |
| "Erro ao buscar planos: Credenciais não configuradas no .env" | Usuário ou senha do SimuladorOnline ausentes, ou configuração em cache antiga | Preencher o `.env` e rodar `php artisan optimize:clear && php artisan optimize` |
| Passo 4 sem planos, ou "Erro ao buscar planos: Status …" | Senha trocada, conta bloqueada, `LOGIN_PATH` errado ou mudança no HTML do SimuladorOnline | Testar o login manualmente no site e rodar o teste da seção 8.1 |
| "Erro ao gerar simulação" ou tela de carregamento eterna no passo 4 | Tempo limite estourado (PHP ou Nginx) | `max_execution_time = 120` e `fastcgi_read_timeout 120` |
| Erro de memória na geração da proposta (`Allowed memory size…`) | DomPDF com proposta grande | `memory_limit = 256M` (ou 512M) |
| PDF sem logotipos | O servidor não acessa os hosts das imagens ou falta a extensão `gd` | Liberar a saída HTTPS e instalar `php8.4-gd` |
| Envio retorna "Proposta enviada com sucesso!", mas nada chega no WhatsApp | Token ou URL da Uazapi errados, ou instância desconectada. **O sistema não acusa a falha ao visitante** | Procurar `WhatsAppService:` no log e reconectar a instância |
| "Nenhuma simulação encontrada." no passo 5 | Sessão expirada entre os passos 4 e 5 | Aumentar `SESSION_LIFETIME` ou refazer a simulação |
| "PDFs não encontrados. Por favor, refaça a simulação." | O visitante tentou enviar de novo (os PDFs já foram apagados) ou a limpeza rodou antes | Refazer a simulação |
| Erro 500 ao enviar, log com `Connection could not be established` / `Expected response code` | SMTP mal configurado | Revisar `MAIL_*` e testar (seção 8.3) |
| Cidade sempre "Rio de Janeiro" | `ipapi.co` bloqueado ou no limite diário | É o comportamento de fallback. O visitante pode usar GPS ou a busca de cidade |
| "Usar GPS" não faz nada | Site sem HTTPS | Ativar HTTPS |
| `.env` alterado sem efeito | Configuração em cache | `php artisan optimize:clear && php artisan optimize` |

---

## 12. Testes e lint

```bash
composer run test   # limpa o cache de config, roda o Pint em modo verificação e depois o Pest
php artisan test    # só os testes
composer run lint   # corrige o estilo com o Pint
```

Os testes usam SQLite em memória e `MAIL_MAILER=array` (definidos em `phpunit.xml`), então não enviam e-mail nem tocam o banco local. Os testes de API do funil (`tests/Feature/StepControllerApiTest.php`) usam um mock do `SimuladorOnlineService`. As telas da área autenticada dependem de `public/build/manifest.json`, então rode `npm run build` antes.

**Situação atual** (veja as [Limitações](#14-limitações-conhecidas)):
- `php artisan test`: 39 testes passam e 1 falha (`buscar hospitais returns success…`).
- `composer run test`: para já na etapa do Pint, porque 21 arquivos não seguem o estilo configurado.

---

## 13. Estrutura do projeto

```
.htaccess                       redireciona para public/ (hospedagem compartilhada)
app/
  Http/Controllers/
    StepController.php          passos do funil e APIs de busca
    PropostaController.php      geração e visualização da proposta
    SendProposalController.php  envio (e-mail + WhatsApp)
  Http/Requests/                validação das buscas
  Mail/ProposalSystemMail.php   e-mail com o PDF do sistema
  Services/
    SimuladorOnlineService.php  integração e scraping do SimuladorOnline
    PdfService.php              geração dos PDFs
    WhatsApp/WhatsAppService.php
config/services.php             chaves do SimuladorOnline e da Uazapi
resources/views/
  index.blade.php               página e toda a lógica do funil (JS)
  steps/step-*.blade.php        conteúdo de cada passo
  proposta/                     página da proposta e template do PDF
  emails/proposal_system.blade.php
public/                         document root, logos, manifest.json e sw.js (PWA)
storage/app/private/temp_propostas/   PDFs temporários
openspec/                       especificações e changes (OpenSpec)
```

---

## 14. Limitações conhecidas

Comportamentos atuais que **não** fazem parte das especificações e devem ser corrigidos em changes próprias:

1. **Região fixa na proposta.** A busca de planos respeita a região do visitante, mas a geração da simulação sempre envia a região 2 (RJ). Local: `app/Services/SimuladorOnlineService.php:501`.
2. **Telefone da equipe é um placeholder.** O alerta de lead e o PDF do sistema vão para `5521999999999`. Local: `app/Http/Controllers/SendProposalController.php:90`.
3. **E-mail administrativo fixo no código.** O destinatário não vem do `.env`. Local: `app/Http/Controllers/SendProposalController.php:45`.
4. **"Sucesso" mesmo com falha no WhatsApp.** As falhas da Uazapi só vão para o log. Locais: `app/Http/Controllers/SendProposalController.php` e `app/Services/WhatsApp/WhatsAppService.php`.
5. **PDFs temporários acumulam** quando o visitante abandona o passo 5 (contorno: cron da [seção 10](#10-manutenção)). Local: `app/Http/Controllers/PropostaController.php`.
6. **DomPDF fora do `composer.json`.** O pacote `barryvdh/laravel-dompdf` está só no `composer.lock`. Um `composer update` o remove e quebra o PDF. Local: `composer.json`.
7. **Busca de hospitais desconectada.** `GET /api/hospitais/buscar` existe, mas o funil não a usa. Locais: `app/Http/Controllers/StepController.php` e `routes/web.php`.
8. **Tailwind duplicado.** O funil usa o Tailwind via CDN (`cdn.tailwindcss.com`, que não é recomendado em produção); o Vite compila outro Tailwind só para a área autenticada. Local: `resources/views/index.blade.php`.
9. **Erros mascarados nas páginas da proposta.**
   - `GET /proposta/cliente` e `GET /proposta/sistema` sem simulação respondem **500**, e não 404: o `catch (\Throwable)` engole o `abort(404)`.
   - `GET /proposta` sem simulação mostra uma página de erro em vez de redirecionar.

   Local: `app/Http/Controllers/PropostaController.php`.
10. **Teste de hospitais quebrado.** O mock espera só `'santa'`, mas o controller também passa a região. Locais: `tests/Feature/StepControllerApiTest.php` e `app/Http/Controllers/StepController.php`.
11. **Lint pendente.** O `pint --test` aponta 21 arquivos fora do estilo, e o `composer run test` para antes dos testes. `composer run lint` corrige.
12. **Cadastro aberto na área autenticada.** `/register` está habilitado (`Features::registration()` em `config/fortify.php`), então qualquer pessoa pode criar conta. A área não dá acesso a dados do funil, mas convém desabilitar o cadastro se não for usado.
13. **Mensagens de validação em inglês** (`APP_LOCALE=en`). O funil valida no navegador, então isso só aparece em chamadas diretas à API.

---

## 15. Especificações

O comportamento esperado do sistema está em [`openspec/specs/`](openspec/specs/) (antes do archive, em [`openspec/changes/documentar-projeto/specs/`](openspec/changes/documentar-projeto/specs/)):

- [`funil-cotacao`](openspec/specs/funil-cotacao/spec.md): passos, localização, perfis e regras de vidas
- [`busca-planos`](openspec/specs/busca-planos/spec.md): consulta, tipo de tabela e filtragem
- [`geracao-proposta`](openspec/specs/geracao-proposta/spec.md): versões sistema e cliente, PDFs e aviso de pronto-socorro
- [`envio-proposta`](openspec/specs/envio-proposta/spec.md): entregas, telefone e limpeza

As mudanças seguem o fluxo do [OpenSpec](https://github.com/Fission-AI/OpenSpec):
1. `/opsx:propose` cria a change (proposta, specs, design e tarefas);
2. `/opsx:apply` implementa;
3. `/opsx:archive` incorpora as specs em `openspec/specs/`.
