<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# wallet-monitor

## Comandos Artisan

### População de Dados Históricos do Bitcoin

O sistema utiliza a **API do Binance** para importação de dados históricos do Bitcoin, que oferece suporte completo a range de datas e dados OHLC precisos.

#### 🚀 **API Binance**

A API do Binance é a solução recomendada pois oferece:
- ✅ **Suporte completo a range de datas**
- ✅ **Dados OHLC precisos**
- ✅ **Rate limits generosos**
- ✅ **Múltiplos intervalos disponíveis**
- ✅ **Gratuita e confiável**

##### Uso Básico
```bash
# Testar conectividade
php artisan bitcoin:populate-binance --test

# Importar últimos 30 dias (padrão)
php artisan bitcoin:populate-binance

# Importar período específico
php artisan bitcoin:populate-binance --start-date=2020-01-01 --end-date=2023-12-31

# Importar com diferentes símbolos
php artisan bitcoin:populate-binance --symbol=BTCUSDT --currency=usd
php artisan bitcoin:populate-binance --symbol=BTCEUR --currency=eur
php artisan bitcoin:populate-binance --symbol=BTCBRL --currency=brl
```

##### Opções Disponíveis
- `--start-date=YYYY-MM-DD` - Data inicial
- `--end-date=YYYY-MM-DD` - Data final
- `--symbol=BTCUSDT` - Símbolo (BTCUSDT, BTCEUR, BTCBRL, etc.)
- `--interval=1d` - Intervalo (1d, 1h, 4h, etc.)
- `--currency=usd` - Moeda (usd, eur, brl, etc.)
- `--batch-size=30` - Tamanho do lote em dias
- `--delay=1` - Delay entre requisições em segundos
- `--force` - Forçar execução sem confirmação
- `--test` - Testar conectividade apenas

##### Exemplos de Uso
```bash
# Importar dados de 2020 até hoje
php artisan bitcoin:populate-binance --start-date=2020-01-01

# Importar dados de 2015 até 2020 com lotes menores
php artisan bitcoin:populate-binance --start-date=2015-01-01 --end-date=2020-12-31 --batch-size=30 --delay=2

# Importar dados completos desde 2010
php artisan bitcoin:populate-binance --start-date=2010-07-17 --batch-size=30 --delay=1 --force

# Importar dados em diferentes intervalos
php artisan bitcoin:populate-binance --interval=1h --start-date=2024-01-01 --end-date=2024-01-02
php artisan bitcoin:populate-binance --interval=4h --start-date=2024-01-01 --end-date=2024-01-07
```

#### Características

- ✅ **Processamento por Etapas**: Divide grandes períodos em lotes menores
- ✅ **Controle de Rate Limit**: Delay configurável entre requisições
- ✅ **Verificação de Duplicatas**: Pula registros já existentes
- ✅ **Tratamento de Erros**: Continua processando mesmo com falhas pontuais
- ✅ **Feedback Detalhado**: Mostra progresso e estatísticas
- ✅ **Suporte a Range**: Permite especificar período exato
- ✅ **Dados OHLC**: Importa Open, High, Low, Close diários

#### Dados Importados

O comando importa os seguintes dados para cada dia:
- **Open**: Preço de abertura
- **High**: Preço mais alto do dia
- **Low**: Preço mais baixo do dia
- **Close**: Preço de fechamento
- **Price**: Preço de fechamento (mesmo valor do Close)

#### Vantagens da API Binance

| Característica | Binance |
|---|---|
| **Range de Datas** | ✅ Completo |
| **Rate Limits** | ✅ Generosos |
| **Dados OHLC** | ✅ Precisos |
| **Intervalos** | ✅ Múltiplos |
| **Gratuidade** | ✅ Total |
| **Confiabilidade** | ✅ Alta |

#### Notas Importantes

1. **Rate Limits**: A API Binance tem limites generosos, mas ainda use `--delay` para evitar bloqueios
2. **Dados Históricos**: A API fornece dados históricos confiáveis desde 2017
3. **Duplicatas**: O comando verifica e pula registros já existentes automaticamente
4. **Símbolos**: A Binance oferece mais opções de pares de trading (BTCUSDT, BTCEUR, BTCBRL, etc.)
5. **Intervalos**: Suporte a múltiplos intervalos (1m, 5m, 15m, 1h, 4h, 1d, etc.)
