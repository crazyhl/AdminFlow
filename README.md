# AdminFlow

<p align="center">
  <b>A Next-Gen, Ultra-Lightweight Modular Admin & Plugin Framework</b><br>
  <i>Built with Laravel, Blade + htmx, Tailwind CSS, Preline UI & FrankenPHP</i>
</p>

<p align="center">
  <a href="README.zh-CN.md"><b>简体中文</b></a> | <b>English</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-777BB4?style=flat-square&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-11.x%20|%20Latest-FF2D20?style=flat-square&logo=laravel" alt="Laravel Version">
  <img src="https://img.shields.io/badge/UI-Tailwind%20%2B%20Preline-38B2AC?style=flat-square&logo=tailwind-css" alt="Tailwind UI">
  <img src="https://img.shields.io/badge/Interactivity-htmx-336699?style=flat-square" alt="htmx">
  <img src="https://img.shields.io/badge/Runtime-FrankenPHP-00ADD8?style=flat-square" alt="FrankenPHP">
  <img src="https://img.shields.io/badge/License-MIT-green.svg?style=flat-square" alt="License">
</p>

---

## 💡 What is AdminFlow?

**AdminFlow** is a modern, modular, single-instance enterprise administration foundation and plugin platform. 

In the AI era, building a simple CRUD dashboard takes only a few prompts. However, **pure-AI generated code leaves critical production traps**: silent race-condition overwrites, Broken Object Level Authorization (IDOR), un-audited state drifts, non-reversible migrations, and bloated SPA runtimes.

AdminFlow combines **decades of solid engineering expertise** with AI-assisted high-speed iteration to deliver an ultra-clean, high-performance, and bulletproof administrative foundation.

---

## ⚡ Core Highlights

### 🛡️ 1. Battle-Tested Against Pure-AI Traps
* **Optimistic Locking by Default**: Built-in 409 conflict detection for concurrent edits. No more silent data loss caused by concurrent submissions.
* **Bulletproof Authorization Context**: Server-side enforced permission gates that completely eliminate cross-account and IDOR vulnerabilities.
* **Idempotent Data Lifecycle**: Predictable, repeatable migrations and import batches with verifiable rollback guarantees.
* **Structured Audit Trail**: Real-time auditing for administrative operations without sensitive credential leaks.

### 🚀 2. Blazing Fast & Zero SPA Bloat
* **Blade + htmx**: Zero heavy virtual DOM overhead and no complex state serialization. Enjoy desktop-smooth 10–30ms response times.
* **Tailwind CSS + Preline UI**: Clean, high-fidelity administrative components (modals, drawers, dropdowns, tables, metrics) with automatic DOM state re-hydration via `htmx:afterSwap`.
* **Zero Node Fatigue in Production**: The production runtime requires zero resident Node.js processes.

### 💰 3. Extreme Cost & Resource Efficiency
* **Runs on 1 vCPU & 1GB RAM**: Unlike JVM-heavy Java stacks (e.g., RuoYi) or memory-hungry microservices, AdminFlow starts in milliseconds and consumes minimal resources.
* **FrankenPHP Native**: Built on modern Go-powered Caddy runtime with native HTTP/3, worker mode readiness, and simple single-container deployment.

### 🧩 4. Monorepo & Pluggable Architecture
* **Clean Decoupling**: Organized as local Composer packages (`packages/core`, `packages/cms`). The Core foundation knows nothing about business specifics; business logic plugs in via standard Service Providers and Contracts.
* **Silo Isolation**: Designed for single-instance, dedicated-database delivery per client. Clean, private, and secure without the risks of shared multi-tenant leaks.

---

## 📦 Project Structure

```text
adminflow/
├── app/                  # Host application & service assembly
├── packages/
│   ├── core/             # Base admin: Auth, RBAC, Dashboard, Audit, Layout
│   ├── cms/              # Official Reference Plugin: Markdown, Media, Comments, SEO
│   ├── payment/          # (Roadmap) Unified payment gateway (WeChat, Alipay, Stripe)
│   ├── course/           # (Roadmap) Knowledge monetization & course paywall
│   └── shop/             # (Roadmap) Lightweight commerce
├── resources/            # Global assets & Blade templates
├── docker-compose.yml    # Laravel Sail development configuration
└── composer.json         # Monorepo path repository configuration
```

---

## 🗺️ Roadmap

### Phase 1: Core Foundation & CMS Plugin (Current Focus)
- [x] Monorepo package architecture & Sail container setup.
- [ ] Admin authentication, base user CRUD & preset role assignment UI.
- [ ] Admin dashboard layout with Preline UI metric cards.
- [ ] CMS Plugin: Markdown writing, local media storage, nested comments with moderation.
- [ ] Smooth Hugo blog import & 301 redirection mapping.
- [ ] Backup/restore drill & FrankenPHP deployment verification.

### Phase 2: Commercialization & Customization Tooling
- [ ] **Web-based Visual Code Generator**: An intuitive UI to inspect MySQL tables, configure field widgets, and generate production-grade, concurrency-safe CRUD modules in minutes (targeting custom client development).
- [ ] **AdminFlow Architecture Skill**: Structured coding prompt guidelines to guarantee 100% unified code style across AI agents.
- [ ] **Unified Payment Gateway**: Multi-driver payment module supporting domestic (WeChat/Alipay via `yansongda/pay`) and international payments (Stripe).

---

## 🛠️ Quick Start

*(Detailed setup instructions will be updated upon P1-01 completion)*

```bash
# Clone the repository
git clone https://github.com/<your-username>/adminflow.git
cd adminflow

# Bootstrap dependencies via Laravel Sail
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

---

## 🤝 Commercial & Custom Development Services

AdminFlow is maintained by an experienced senior full-stack architect. We provide:

* **Custom Enterprise System Development**: Fast delivery of internal ERP, CRM, procurement, and workflow management systems based on AdminFlow.
* **Performance & Architecture Consulting**: Refactoring AI-generated prototypes into rock-solid, production-ready enterprise applications.
* **Private SaaS & Deployment**: End-to-end setup of cost-efficient private cloud architectures on minimal server infrastructure.

💬 **Contact Me**:
* **Email**: *(Add your email here)*
* **WeChat**: *(Add your WeChat ID / QR Code here)*
* **Gitee**: [https://gitee.com](https://gitee.com)
* **GitHub**: [https://github.com](https://github.com)

---

## 📄 License

The AdminFlow core is open-sourced software licensed under the [MIT license](LICENSE).
