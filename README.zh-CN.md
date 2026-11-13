# AdminFlow

<p align="center">
  <b>一个为独立开发者与中小项目打造的轻量级后台基座与插件框架</b><br>
  <i>基于 PHP / Laravel / Blade + htmx / Tailwind CSS / Preline UI / FrankenPHP</i>
</p>

<p align="center">
  <b>简体中文</b> | <a href="README.md"><b>English</b></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-11.x%2B-FF2D20?style=flat-square&logo=laravel" alt="Laravel Version">
  <img src="https://img.shields.io/badge/UI-Tailwind%20%2B%20Preline-38B2AC?style=flat-square&logo=tailwind-css" alt="Tailwind UI">
  <img src="https://img.shields.io/badge/%E4%BA%A4%E4%BA%92-htmx-336699?style=flat-square" alt="htmx">
  <img src="https://img.shields.io/badge/%E8%BF%90%E8%A1%8C%E7%8E%AF%E5%A2%83-FrankenPHP-00ADD8?style=flat-square" alt="FrankenPHP">
  <img src="https://img.shields.io/badge/%E8%AE%B8%E5%8F%AF%E8%AF%81-MIT-green.svg?style=flat-square" alt="License">
</p>

---

## ☕ 为什么做这个项目？

最近两年 AI 写代码普及，给几句提示词就能很快搓出一套增删改查后台。但实际在生产环境跑过一段时间后，经常会发现不少隐藏的问题：

* **并发容易丢数据**：两个人同时打开一个页面编辑，后提交的直接把前一个人的修改覆盖了，没有版本锁提示。
* **权限容易漏检查**：查数据往往只看参数里的 ID，一旦漏写属主校验，就容易产生越权漏洞。
* **系统越做越重**：有的框架跑起来动辄占用 1~2 GB 内存；前端引入了复杂的构建工具链和状态管理，一旦报错排查成本很高。

做 AdminFlow 的初衷很简单：**结合多年实战里摸爬滚打出来的工程经验，用 AI 提速开发，沉淀出一套真正干净、省资源、少踩坑的后台底座。**

它不追求面面俱到，只追求**好维护、部署省心、服务器成本低**。

---

## ✨ 核心特点

### 1. 轻快省资源，入门级服务器也能跑
* **内存占用低**：基础运行时仅占用几十 MB 内存，百元一年的最低配云服务器（1核1G / 1核2G）就能轻松跑起来。
* **单容器快速部署**：基于现代化的 FrankenPHP，不需要繁琐的 PHP-FPM + Nginx 多层配置，单镜像极速启动，原生支持 HTTP/3。

### 2. 回归经典 Web，告别前端构建折腾
* **Blade + htmx**：采用服务端渲染配合局部刷新，拥有类似单页应用（SPA）的无刷新丝滑体验，但不需要在生产环境跑常驻的 Node 服务，更没有复杂的前端状态同步问题。
* **Preline UI 组件库**：搭配 Tailwind CSS，自带现成的弹窗、抽屉、表格、下拉等常用后台组件，开箱即用。

### 3. 把稳妥的防坑设计做成默认规范
* **默认支持并发检查**：编辑保存时自动校验版本号，遇到冲突返回 409 提醒，避免不小心覆盖别人刚改完的内容。
* **服务端统一权限校验**：所有操作在服务端做完整的权限与归属判定，不依赖前端按钮的显隐来保障安全。
* **清晰的操作审计**：对核心发布、修改、审核等操作保留必要的日志流水，出了问题有据可查。

### 4. 小核心 + 插件化组织
* **小核心（Core）**：只负责基础的登录、用户管理、预设角色、仪表盘与统一规范。
* **业务全靠插件**：首个官方参考插件是 CMS（内容发布系统），支持 Markdown 写作、内置图床、嵌套评论与旧博客平滑导入。后续按需以插件方式扩展其他业务模块。
* **单实例交付**：每个客户或自用站点拥有独立的数据库与环境配置，数据清晰独立，便于单独备份和迁移。

---

## 📁 目录结构

```text
adminflow/
├── app/                  # 宿主应用骨架与服务装配
├── packages/
│   ├── core/             # 核心底座：登录鉴权、用户管理、角色预设、仪表盘、审计日志
│   ├── cms/              # 官方 CMS 插件：文章与页面、图床、评论审核、SEO
│   ├── payment/          # （规划）统一支付插件：微信支付、支付宝、Stripe
│   └── course/           # （规划）内容付费与专栏插件
├── resources/            # 全局模板与静态资源
├── docker-compose.yml    # 本地开发容器配置（基于 Laravel Sail）
└── composer.json         # 本地包管理配置
```

---

## 📌 当前进展与后续规划

### 第一阶段（P1）：核心底座 + 自用 CMS 博客（进行中）
- [x] 多包架构组织与 Sail 开发环境规约。
- [ ] 管理员认证、基础用户管理与预设角色分配界面。
- [ ] 后台 Dashboard 首页与统计卡片。
- [ ] 完整 CMS 插件：Markdown 写作与安全渲染、本地媒体库、评论嵌套与审核。
- [ ] 个人 Hugo 历史博客数据导入与 301 链接映射。
- [ ] 备份与恢复演练、FrankenPHP 部署适配。

### 第二阶段（P2）：效率工具与业务扩展
- [ ] **可视化代码生成器**：提供界面化的表结构读取与配置，一键生成符合规范的代码骨架，提升定制业务开发效率。
- [ ] **代码规范 Agent Skill**：沉淀统一的架构与编码规约，方便后续使用 AI 扩展新功能时保持代码风格一致。
- [ ] **多驱动支付支持**：集成主流支付网关，支持单篇付费与内容解锁。

---

## 🚀 快速开始

本项目本地开发统一基于 **Docker + Laravel Sail**，宿主机无需安装任何 PHP、MySQL 或 Node.js 环境。

```bash
# 1. 克隆代码仓库
git clone https://gitee.com/crazyhl/admin-flow.git
cd admin-flow

# 2. 准备配置文件
cp .env.example .env

# 3. 首次引导依赖（无需宿主机 PHP，通过临时 Docker 容器安装 Composer 依赖）
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 4. 启动本地容器（PHP 8.4 + MySQL 8.4）
./vendor/bin/sail up -d

# 5. 生成密钥并运行数据库迁移
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

* **日常停止服务**：`./vendor/bin/sail stop`（保留数据库持久化数据）
* **日常重新启动**：`./vendor/bin/sail up -d`

---

## 🤝 交流与定制合作

AdminFlow 由个人独立开发者维护。如果你有以下需求，欢迎交流合作：

1. **中小企业业务系统定制**：基于这套轻量底座快速搭建内部管理台、工单系统或运营工具，交付周期短，代码干净。
2. **AI 原型工程化改造**：把 AI 临时拼出来的原型代码梳理为可长期维护、安全稳固的生产级系统。
3. **私有化低成本部署**：在有限的云服务器预算下，跑出稳定高效的业务系统。

* **Gitee 仓库**：[https://gitee.com/crazyhl/admin-flow](https://gitee.com/crazyhl/admin-flow)
* **GitHub 仓库**：[https://github.com/crazyhl/AdminFlow](https://github.com/crazyhl/AdminFlow)

---

## 📄 开源协议

本项目采用 [MIT 许可证](LICENSE) 开源。
