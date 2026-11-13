# AdminFlow

<p align="center">
  <b>面向现代 Web 的超轻量模块化通用后台与插件基座框架</b><br>
  <i>基于 PHP + Laravel + Blade + htmx + Tailwind CSS + Preline UI + FrankenPHP 构建</i>
</p>

<p align="center">
  <b>简体中文</b> | <a href="README.md"><b>English</b></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-777BB4?style=flat-square&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-11.x%20|%20Latest-FF2D20?style=flat-square&logo=laravel" alt="Laravel Version">
  <img src="https://img.shields.io/badge/UI-Tailwind%20%2B%20Preline-38B2AC?style=flat-square&logo=tailwind-css" alt="Tailwind UI">
  <img src="https://img.shields.io/badge/交互-htmx-336699?style=flat-square" alt="htmx">
  <img src="https://img.shields.io/badge/运行环境-FrankenPHP-00ADD8?style=flat-square" alt="FrankenPHP">
  <img src="https://img.shields.io/badge/开源协议-MIT-green.svg?style=flat-square" alt="License">
</p>

---

## 💡 为什么做 AdminFlow？

在 AI 编码普及的时代，几句 Prompt 就能拼出一个漂亮的 CRUD 页面。但**小团队与初级开发者纯靠 AI 生成的代码，往往埋下了致命的生产级缺陷**：

1. **并发静默覆盖**：没有版本锁与冲突校验，多人同时修改同一条数据时，后提交者直接覆盖前人数据。
2. **越权漏洞（IDOR）**：数据查询只按主键 ID，缺乏严格的用户/所有权上下文隔离。
3. **不可逆迁移与长事务**：数据库迁移不可回滚，事务中滥用耗时操作与文件大锁。
4. **前端膨胀与运维黑盒**：动辄几百兆的 Node 构建链、复杂的 SPA 状态同步，一旦出问题小白完全无法排查。

**AdminFlow 的诞生，就是用多年的企业级实战架构经验，为 AI 时代的开发者与中小企业打造一套“真正能抗高可用、极度轻量、零黑盒”的工业级后台底座。**

---

## ⚡ 核心特性与优势

### 🛡️ 1. 战胜纯 AI 遗留缺陷（实战防坑基因）
* **开箱即用并发控制**：默认内置乐观锁机制（版本号校验与 409 冲突拦截），杜绝任何并发丢数据。
* **严格服务端安全网关**：统一服务端授权入口与防越权上下文校验，彻底杜绝横向越权。
* **审计追踪与幂等机制**：规范记录关键操作流水与审计日志，数据迁移与批处理严格支持可重复执行与回退验证。

### 🚀 2. 毫秒级极速直出，告别 SPA 臃肿
* **Blade + htmx**：抛弃笨重臃肿的虚拟 DOM 与前端状态序列化，服务端毫秒级直出局部 HTML 片段（10~30ms 响应），享受桌面级丝滑。
* **Tailwind CSS + Preline UI**：原生集成高保真后台组件库（模态弹窗、侧边栏抽屉、下拉菜单、数据表格与指标卡），监听 `htmx:afterSwap` 自动重新初始化，零前端框架负担。
* **生产环境无常驻 Node**：构建产物直接随静态资源发布，生产环境完全零 Node.js 进程依赖。

### 💰 3. 极致降本：1核1G 轻松奔跑
* **超低资源占用**：对标动辄吃掉 1~2G 内存的 Java RuoYi 等框架，AdminFlow 内存消耗仅数十兆，在几百元一年的最低配云服务器上依然飞速运行。
* **FrankenPHP 现代原生**：基于 Go 驱动的 Caddy 现代运行时，原生支持 HTTP/3，具备超强并发与单容器极速部署能力。

### 🧩 4. 小核心 + 插件化单体架构（Monorepo）
* **高度解耦**：核心包（`packages/core`）只负责身份、权限、布局与公共约定；业务以独立插件（`packages/cms`、`packages/payment` 等）接入。
* **单实例私有化交付**：专为单客户独立实例、独立数据库打造，既便于独立备份与客户定制，又彻底规避多租户 SaaS 穿透风险。

---

## 📦 工程目录结构

```text
adminflow/
├── app/                  # 宿主应用与服务组装
├── packages/
│   ├── core/             # 通用核心包：身份鉴权、基础用户CRUD、角色、仪表盘、审计日志
│   ├── cms/              # 官方首个参考插件：Markdown内容、内置图床、嵌套评论树、SEO
│   ├── payment/          # （规划中）统一支付插件：集成微信支付、支付宝、Stripe
│   ├── course/           # （规划中）知识付费与课程专栏插件：文章付费解锁与学籍权限
│   └── shop/             # （规划中）轻量电商交易插件
├── resources/            # 全局视图与静态资源
├── docker-compose.yml    # Laravel Sail 本地开发容器配置
└── composer.json         # Monorepo Path Repository 配置
```

---

## 🗺️ 发展路线图

### 第一阶段（P1）：基座骨架 + 完整个人博客系统（当前进行中）
- [x] Monorepo 本地包组织与 Laravel Sail 运行环境规划。
- [ ] 管理员认证、基础用户管理 CRUD 与预设角色分配 UI。
- [ ] 后台 Dashboard 仪表盘与 Preline UI 统计卡片布局。
- [ ] 完整 CMS 业务插件：Markdown 写作、安全防 XSS 渲染、内置媒体图床、嵌套评论树与审核。
- [ ] 个人 Hugo 历史博客平滑导入与 301 映射保留。
- [ ] 本地隔离演练备份恢复与 FrankenPHP 部署验证。

### 第二阶段（P2）：定制化提效母机与生态演进
- [ ] **Web 界面式代码生成器**：对标 RuoYi 可视化生成器。读取数据库表结构，在线配置字段控件与校验，一键生成带并发锁与防越权规范的高质量 CRUD 插件代码（专用于承接客户定制化开发）。
- [ ] **AdminFlow 编码规范 Agent Skill**：沉淀统一架构与风格规则，赋能各类 AI Agent 保持 100% 统一的代码产出。
- [ ] **统一多驱动支付网关**：集成微信支付 API v3、支付宝与 Stripe 海外支付。

---

## 🛠️ 快速启动

*(详细启动文档将在 P1-01 任务完成后同步更新)*

```bash
# 克隆代码仓库
git clone https://gitee.com/crazyhl/admin-flow.git
cd admin-flow

# 通过 Laravel Sail 一键启动本地环境
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

---

## 🤝 商业合作与定制开发服务

AdminFlow 由资深一线架构师独立主理，专注于为中小企业与个人超级个体提供**高性价比、工业级稳定度**的软件交付：

1. **企业业务系统定制开发**：基于 AdminFlow 极速搭建内部 ERP、CRM、进销存、审批流与业务后台，工期缩短 50%，源码完全买断交付。
2. **AI 原型落地与架构重构**：帮你修复纯 AI 开发遗留的并发、性能、安全漏洞与数据库设计缺陷，救活烂尾工程。
3. **私有化低成本部署咨询**：以极低的服务器成本（1核2G甚至1核1G）实现生产级高可用部署。

💬 **项目仓库与社区**：
* **Gitee 仓库**：[https://gitee.com/crazyhl/admin-flow](https://gitee.com/crazyhl/admin-flow)
* **GitHub 仓库**：[https://github.com/crazyhl/AdminFlow](https://github.com/crazyhl/AdminFlow)

---

## 📄 开源协议

AdminFlow 核心代码遵循 [MIT 开源协议](LICENSE)。
