# AdminFlow

<p align="center">
  <b>A Practical, Lightweight Admin Foundation & Plugin Framework</b><br>
  <i>Built with PHP, Laravel, Blade + htmx, Tailwind CSS, Preline UI, and FrankenPHP</i>
</p>

<p align="center">
  <a href="README.zh-CN.md"><b>简体中文</b></a> | <b>English</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-11.x%2B-FF2D20?style=flat-square&logo=laravel" alt="Laravel Version">
  <img src="https://img.shields.io/badge/UI-Tailwind%20%2B%20Preline-38B2AC?style=flat-square&logo=tailwind-css" alt="Tailwind UI">
  <img src="https://img.shields.io/badge/Interactivity-htmx-336699?style=flat-square" alt="htmx">
  <img src="https://img.shields.io/badge/Runtime-FrankenPHP-00ADD8?style=flat-square" alt="FrankenPHP">
  <img src="https://img.shields.io/badge/License-MIT-green.svg?style=flat-square" alt="License">
</p>

---

## ☕ Why AdminFlow?

With AI coding tools, building basic CRUD screens has never been faster. However, running AI-generated code in real-world production often reveals subtle but painful headaches:

* **Silent data overwrites**: Two users editing the same record concurrently can easily overwrite each other without version check warnings.
* **Overlooked permission checks**: Queries that fetch data purely by ID without verifying record ownership lead to horizontal privilege escalation (IDOR).
* **Bloated stacks**: Heavy backend runtimes eating 1–2 GB of idle RAM, coupled with complex frontend toolchains that are difficult to debug when builds fail.

AdminFlow is built on a straightforward premise: **combine years of hands-on full-stack engineering practices with AI acceleration to build an admin foundation that is clean, lightweight, and reliable by default.**

It doesn't try to solve every problem—it focuses on **low hosting costs, easy maintenance, and pragmatic code quality**.

---

## ✨ Key Features

### 1. Minimal Resource Footprint
* **Lightweight memory usage**: The base runtime consumes only tens of megabytes of RAM. It runs smoothly on budget cloud VPS instances (1 vCPU / 1GB RAM).
* **Single-container deployment**: Powered by modern FrankenPHP. No need for complex PHP-FPM + Nginx configurations; fast startups with native HTTP/3 support.

### 2. Back to Web Fundamentals
* **Blade + htmx**: Delivers the smooth, partial-page feel of an SPA using server-rendered HTML snippets, without resident Node.js processes in production or complex client-side state managers.
* **Preline UI components**: Bundled with Tailwind CSS to offer clean, ready-to-use modals, dropdowns, tables, drawers, and metric cards out of the box.

### 3. Practical Guardrails by Default
* **Concurrency version checks**: Built-in 409 conflict detection on save prevents accidental overwrites when multiple people edit content.
* **Server-side authorization**: All permissions are enforced on the server; UI visibility toggles are treated as conveniences, not security barriers.
* **Audit trail**: Key administrative actions (publishing, deleting, status changes) are recorded for traceability.

### 4. Small Core & Pluggable Architecture
* **Minimal Core**: The core package handles only authentication, user management, preset roles, dashboard scaffolding, and shared contracts.
* **Feature plugins**: Features live in independent local packages. The first official plugin is a CMS for Markdown writing, media storage, and comments. Additional modules (payment, custom business tools) plug in cleanly.
* **Single-instance isolation**: Built for dedicated per-client instances and independent databases, making backups, updates, and customer customizations safe and clean.

---

## 📁 Repository Structure

```text
adminflow/
├── packages/
│   ├── core/             # Core package: Auth, user CRUD, roles, dashboard, audit logs
│   ├── cms/              # Official CMS plugin: Posts, media library, comments, SEO
│   ├── payment/          # (Planned) Unified payment module: WeChat, Alipay, Stripe
│   └── course/           # (Planned) Content paywall & subscription plugin
├── resources/            # Global templates and frontend assets
├── docker-compose.yml    # Local development setup via Laravel Sail
└── composer.json         # Monorepo path repository config
```

---

## 📌 Roadmap

### Phase 1: Core Foundation & CMS Blog (In Progress)
- [x] Monorepo package structure & Sail development environment.
- [ ] Authentication, basic user CRUD, and preset role assignment UI.
- [ ] Admin dashboard layout with Preline UI metric cards.
- [ ] CMS plugin: Markdown editor, safe rendering, local media library, nested comments.
- [ ] Hugo blog data import and 301 URL redirect support.
- [ ] Backup/restore drill and FrankenPHP deployment verification.

### Phase 2: Tooling & Extensions
- [ ] **Visual Code Generator**: An intuitive Web UI to inspect MySQL tables, configure form inputs, and generate clean CRUD code blocks for fast custom project delivery.
- [ ] **AdminFlow Coding Skill**: Structured agent rules to ensure consistent architecture and code quality across AI prompts.
- [ ] **Payment Gateway**: Multi-driver payment support for single-item and content paywalls.

---

## 🚀 Quick Start

Local development is fully containerized with **Docker + Laravel Sail**. No local PHP, MySQL, or Node.js installations are required on your host machine.

```bash
# 1. Clone the repository
git clone https://github.com/crazyhl/AdminFlow.git
cd AdminFlow

# 2. Copy the environment configuration
cp .env.example .env

# 3. Bootstrap Composer dependencies via a temporary Docker container
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 4. Start Sail containers (PHP 8.4 + MySQL 8.4)
./vendor/bin/sail up -d

# 5. Generate app key and run migrations
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

* **Stop environment**: `./vendor/bin/sail stop` (preserves database data)
* **Resume environment**: `./vendor/bin/sail up -d`

---

## 🤝 Collaboration & Custom Services

AdminFlow is independently maintained by a senior full-stack developer. If you need:

1. **Custom Enterprise Dashboards**: Fast delivery of internal tools, CRM, and workflow platforms built on a clean, maintainable foundation.
2. **AI Prototype Hardening**: Refactoring rough AI-generated code into robust, production-ready systems.
3. **Budget-Friendly Private Deployments**: Running reliable business systems on cost-effective cloud infrastructure.

Feel free to connect:
* **GitHub**: [https://github.com/crazyhl/AdminFlow](https://github.com/crazyhl/AdminFlow)
* **Gitee**: [https://gitee.com/crazyhl/admin-flow](https://gitee.com/crazyhl/admin-flow)

---

## 📄 License

AdminFlow is open-sourced software licensed under the [MIT License](LICENSE).
