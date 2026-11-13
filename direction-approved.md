# AdminFlow 后台视觉三方向选型审查（Gate 阶段）

- **项目**：AdminFlow 通用后台基座
- **阶段**：P1-03 后台布局、导航与仪表盘视觉设计
- **日期**：2026-11-13
- **技能依据**：[AGENTS.md](AGENTS.md) 规则及 `.agents/skills/huashu-design/SKILL.md`（三方向门约束）
- **选型聚合预览页面**：[resources/prototypes/index.html](resources/prototypes/index.html)

---

## 候选方向一：Craft Clean · 现代工匠极简 (Linear / Raycast 质感)

- **原型文件**：[resources/prototypes/direction-1-craft-clean.html](resources/prototypes/direction-1-craft-clean.html)
- **视觉气质**：深灰/纯白微反差、精细单像素边框、微冷浅灰底色（`bg-slate-50`）、高密度紧凑排印、Electric Indigo 强调色（`#4f46e5`）。
- **布局结构**：紧凑侧边栏、悬浮面包屑、全局快速搜索框（Command+K 心智）、4 列微型趋势指标卡、实时操作审计时间流、紧凑快捷运维胶囊。
- **适合场景**：追求极致现代感、工程师自用、轻快敏捷的高效后台。

---

## 候选方向二：Enterprise Slate · 经典企业级石板 (Vercel / Tailwind UI Pro)

- **原型文件**：[resources/prototypes/direction-2-enterprise-slate.html](resources/prototypes/direction-2-enterprise-slate.html)
- **视觉气质**：经典深冷石板色侧边栏（`bg-slate-900`）、圆润卡片圆角（`rounded-xl`）、专业弥散阴影、标准 Cobalt Blue（`#2563eb`）系统强调色。
- **布局结构**：宽幅清晰分组侧边栏、顶部统合状态与通知徽标、横幅欢迎渐变条、丰富的主副多维卡片与企业级标准审计数据表格。
- **适合场景**：面向企业客户、定制化开发接单交付、需要强烈稳重感与容纳复杂菜单扩展。

---

## 候选方向三：Nordic Minimal · 北欧暖调效率 (Notion / Craft CMS 风格)

- **原型文件**：[resources/prototypes/direction-3-nordic-minimal.html](resources/prototypes/direction-3-nordic-minimal.html)
- **视觉气质**：轻盈米白/微暖纸感底色（`#fbfbfa` / `Stone` 色系）、优雅 Georgia 衬线大标题、扁平透气排印、无厚重边框或阴影、自然森林绿与琥珀色点缀。
- **布局结构**：顶侧一体化无痕导航、扁平大字数据指标排印、创作者视角的内容写作待办流与底座状态摘要。
- **适合场景**：个人独立博客、文字创作者、极简主义者、专注于创作与低认知负担。

---

## 用户确认记录

- **最终选定方向**：**方向三：Nordic Minimal · 北欧暖调效率 (Notion / Craft CMS 风格)**
- **选定时间**：2026-11-13 16:21
- **用户确认原话**：“方案 3 吧,并且要支持夜间模式,同时登录页面也要统一风格”
- **特别要求**：
  1. 全链路支持深浅色模式切换（Dark Mode），提供无闪烁（Anti-FOUC）本地持久化记忆；
  2. 模式支持三态（浅色 / 跟随系统 / 深色），且默认就是跟随系统（System preference），操作系统变色秒级无刷新响应；
  3. 登录页面（`login.blade.php`）与后台主布局（`layouts/admin.blade.php`）及仪表盘（`dashboard.blade.php`）严格统一 Nordic Minimal 视觉规范与暗色主题；
  4. 严格遵循官方最新稳定版依赖策略，底座升级至 `PHP 8.5.12`、`laravel/framework v13.42.0` 与 `laravel/tinker v3.1.0`；
  5. 侧边栏全面采用 Preline UI 5.1.0 规范组件，原生支持桌面端 Mini 模式（一键切换为 72px 图标 Rail，支持 localStorage 持久化与 Anti-FOUC）及移动端抽屉（`hs-overlay`）；
  6. 彻底废弃客户端 CDN 运行时，接入官方 Vite + Tailwind CSS v4.3.5（`@tailwindcss/vite`）+ Preline UI v5.1.0 本地按需编译管线，CSS 产物体积从 ~3.5MB 压缩至 54KB（Gzip 仅 9.7KB）；
  7. 建立 `adminflow_testing` 独立测试库，实现自动化测试（RefreshDatabase）与开发环境数据物理隔离，永久保留测试账号 `crazyhl`。
