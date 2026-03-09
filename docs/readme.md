# r2-app-facilita

Projeto PHP focado em processamento de payloads de webhook `Reserve:confirm`, com:

- geracao de JSON normalizado a partir de dados de venda e simulacao
- validacao de divergencias relevantes entre JSON gerado e template de referencia
- DTOs para mapear payloads do webhook em objetos de dominio

## Repositorio

- remote `origin`: `ssh://git@gitlab.r2soft.app:3022/r2soft-dev/r2-app-facilita.git`
- branch atual: `master`

## Stack e ambiente

- PHP 8.4 (container `php:8.4-fpm` via Docker Compose)
- Sem framework definido neste repositorio (scripts PHP puros + DTOs)

Arquivo de infraestrutura:

- `docker-compose.yml`
  - service: `php`
  - working dir no container: `/var/www/html`
  - bind mount: raiz do projeto em `/var/www/html`
  - porta: `9000:9000`
  - env: `APP_ENV=local`

## Estrutura do projeto

```text
.
├── docker-compose.yml
├── files/
│   ├── Reserve_confirm_campos_nao_encontrados.json
│   ├── Reserve_confirm_campos_nao_encontrados.txt
│   ├── Reserve_confirm_gerado.json
│   ├── Reserve_confirm_recente.json
│   ├── simulation.json
│   └── venda_ganha.json
├── scripts/
│   ├── generate_reserve_confirm.php
│   └── validate_reserve_confirm.php
└── src/DTOs/
    ├── AddressDto.php
    ├── AllotmentDto.php
    ├── BusinessProposalDto.php
    ├── ClientDto.php
    ├── MonetaryCorrectionIndexDto.php
    ├── ParcelDto.php
    ├── PhoneDto.php
    ├── PropertyDto.php
    ├── RealtorDto.php
    └── SaleDto.php
```

## Fluxo principal

1. Ler dados-base de entrada (`venda_ganha.json` + `simulation.json`).
2. Limpar valores do template (`Reserve_confirm_recente.json`) mantendo a estrutura.
3. Aplicar mapeamentos para montar payload final de `Reserve:confirm`.
4. Salvar arquivo gerado (`Reserve_confirm_gerado.json`).
5. Validar campos obrigatorios e consistencia com o template.

## Scripts

### 1) Geracao do payload

Arquivo: `scripts/generate_reserve_confirm.php`

Entrada padrao:

- `files/Reserve_confirm_recente.json` (template estrutural)
- `files/venda_ganha.json` (dados de venda)
- `files/simulation.json` (dados de simulacao e parcelas)

Saida padrao:

- `files/Reserve_confirm_gerado.json`

Exemplo:

```bash
php scripts/generate_reserve_confirm.php \
  --template=files/Reserve_confirm_recente.json \
  --sale=files/venda_ganha.json \
  --simulation=files/simulation.json \
  --output=files/Reserve_confirm_gerado.json
```

Regras importantes implementadas:

- `event.origin` fixo em `Reserve:confirm`
- normalizacao de `event.occurred_at` para formato ISO com timezone
- montagem de `data.reserves_clients` com cliente e telefone normalizado
- montagem de `data.realtor_data` com corretor e telefone
- montagem de `data.business_proposal[0].parcel_configs` a partir de `simulation.installments`
- classificacao de parcelas por tipo (`entry/monthly/brokerage/...`) para `position`, `name`, centro de custo e metodo de pagamento

### 2) Validacao de divergencias

Arquivo: `scripts/validate_reserve_confirm.php`

Entrada padrao:

- `files/Reserve_confirm_gerado.json`
- `files/Reserve_confirm_recente.json`

Exemplo:

```bash
php scripts/validate_reserve_confirm.php \
  --generated=files/Reserve_confirm_gerado.json \
  --template=files/Reserve_confirm_recente.json
```

Saida:

- JSON com `status`, contadores (`critical`, `high`, `total`) e lista de `issues`.

Codigos de saida:

- `0`: sem divergencias criticas
- `2`: encontrou divergencias criticas
- `1`: erro de execucao (arquivo ausente, JSON invalido etc.)

## DTOs (`src/DTOs`)

### DTO agregador

- `SaleDto`: entrada principal `fromWebhook(array $payload)`
  - mapeia evento, dados da reserva, proposta, clientes, corretor, lote/imovel
  - utilitarios:
    - `getSaleValue()`
    - `getSaleName(int $nextSequence)` (com prefixo de loteamento quando existir)
    - `toArray()`

### DTOs de apoio

- `PropertyDto`: id, bloco, nome/unidade, preco, area, status
- `AllotmentDto`: dados do loteamento + indices de correcao monetaria
- `MonetaryCorrectionIndexDto`: configuracao de indice (codigo, nome, limites, flags)
- `BusinessProposalDto`: proposta comercial e lista de parcelas
- `ParcelDto`: configuracao de parcela (valor, vencimento, metodo, juros, centro de custo)
- `ClientDto`: cliente + endereco + telefones + spouse/responsible
- `RealtorDto`: corretor + telefones
- `PhoneDto`: telefone normalizado
- `AddressDto`: endereco completo (cidade/estado)

## Arquivos de dados em `files/`

- `venda_ganha.json`: payload de venda ganha (webhook origem)
- `simulation.json`: simulacao financeira (cliente, unidade, parcelas)
- `Reserve_confirm_recente.json`: payload de referencia usado como template
- `Reserve_confirm_gerado.json`: payload gerado pelo script
- `Reserve_confirm_campos_nao_encontrados.json|txt`: apoio para analise de campos divergentes/ausentes

## Como executar com Docker

Subir container:

```bash
docker compose up -d
```

Rodar scripts:

```bash
docker compose exec php php scripts/generate_reserve_confirm.php
docker compose exec php php scripts/validate_reserve_confirm.php
```

## Observacoes

- `.idea/` esta no `.gitignore`.
- Projeto versionado com commit inicial: `7360c5a`.
