<div align="center">

<img src="docs/banner.svg" alt="Goldsvet - Gaming Platform &amp; Lobby Engine Framework" width="100%">

**A modular, API-first framework for building online gaming platforms.**

[![Status](https://img.shields.io/badge/status-in%20development-7C3AED?style=flat-square)](#roadmap)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11%2B-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Redis](https://img.shields.io/badge/Redis-cache%20%2B%20queue-DC382D?style=flat-square&logo=redis&logoColor=white)](https://redis.io)
[![License](https://img.shields.io/badge/license-proprietary-F43F5E?style=flat-square)](#license)

</div>

---

## Overview

Goldsvet provides the backbone every gaming platform needs - lobby, accounts, wallet, and administration - as clean, swappable modules so operators and development teams can focus on the games, not the plumbing.

- **API-first** - every capability is exposed over a versioned REST API
- **Modular** - enable only the modules you need; no monolith lock-in
- **Provider-agnostic** - the wallet and lobby layers integrate with any game source
- **Operations-ready** - queues, caching, and reporting wired in from day one

## Feature Modules

| Module | What it does |
|--------|--------------|
| **Lobby Engine** | Configurable game lobby with categories, search, and featured placements |
| **Player Accounts** | Registration, authentication, sessions, KYC-ready profile structure |
| **Wallet & Transactions** | Provider-agnostic balance layer with full transaction ledger |
| **Admin Panel** | Content, player, and configuration management with role-based access |
| **Reporting** | Operational dashboards and exportable reports |
| **REST API** | Consistent, versioned endpoints across all modules |

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                       Clients                           │
│            Web (Vue 3)  ·  Mobile  ·  Partners          │
└──────────────────────────┬──────────────────────────────┘
                           │ REST API (versioned)
┌──────────────────────────▼──────────────────────────────┐
│                    API Gateway (Laravel)                │
│         Auth · Rate limiting · Request validation       │
├──────────────┬──────────────┬───────────────────────────┤
│    Lobby     │    Wallet    │      Admin & Reporting    │
│    Module    │    Module    │           Module          │
├──────────────┴──────────────┴───────────────────────────┤
│          MySQL 8 (state)      Redis (cache + queue)     │
└─────────────────────────────────────────────────────────┘
```

## Quick Start

> Documentation is being written as modules stabilize. The structure below reflects the target developer experience.

```bash
git clone https://github.com/ZeusbyteInc/goldsvet.git
cd goldsvet
cp .env.example .env

composer install
npm install && npm run build

php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Roadmap

- [x] Project architecture and module boundaries
- [ ] Wallet core and transaction ledger
- [ ] Lobby engine with category management
- [ ] Admin panel MVP
- [ ] Reporting dashboards
- [ ] Public API documentation
- [ ] Reference deployment guide

## License

Copyright © 2026 Zeusbyte Inc. All rights reserved.

This repository contains **original code developed independently**; it is not derived from any third-party product. Intended for legitimate, licensed use cases only.

---

<div align="center">

**Zeusbyte Inc.** — [github.com/ZeusbyteInc](https://github.com/ZeusbyteInc)

</div>
pe
