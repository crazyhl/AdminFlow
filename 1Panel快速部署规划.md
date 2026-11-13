# AdminFlow：1Panel 快速部署规划

整理日期：2026-10-20；更新日期：2026-11-03（同步小核心与单实例 CMS 阶段边界）。状态：方案规划，未实现、未连接服务器、未验证实际部署。

2026-11-03 范围同步说明：第一阶段已改为 v3“小核心 + 单实例 CMS + Hugo 内容迁移”。本文保留部署方式参考，尚未实施；采购、租户和无文件上传等旧阶段特定表述不作为新阶段验收。后续部署需按 CMS 实际功能适配媒体公开路径、备份文件、健康检查和初始化，具体见 [第一阶段开发范围](./第一阶段开发范围.md) 与 [项目展示与演示站部署规划](./项目展示与演示站部署规划.md)。本阶段已纳入本地隔离环境备份恢复验证，生产发布及服务器联调仍在后续阶段。

## 1. 范围与已有结论

本文补充交付方式，不增加《第一阶段开发范围.md》的功能、任务或验收要求。生产部署脚本、CI、服务器联调及生产恢复演练在第一阶段验收后单独排期；第一阶段已包含隔离测试环境的备份恢复验证。讨论不自动启动实施。

已读取会话“简化 Laravel 网站部署”：用户现有另一个网站使用 1Panel、volumes 挂载 Laravel 代码、Octane 启动 FrankenPHP，希望减少手动拉代码、重启及频繁镜像构建。借鉴其发布方式，不把该网站的 Octane 配置带入 AdminFlow。

AdminFlow 使用 Laravel + Blade + htmx + MySQL 技术体系，FrankenPHP 常规模式。首个部署目标为单台 Linux 服务器上的 1Panel，允许短维护窗口，不承诺零停机。

本地开发已明确使用 Laravel Sail（见第一阶段基线）。Sail 的 Compose、开发镜像、端口和开发工具属于本地环境，不直接部署到生产；本文的 Compose 管理生产 FrankenPHP。两者共享应用代码与锁定依赖，PHP/扩展及数据库兼容性在构建和发布时检查。

## 2. 部署决策

采用“固定版本运行环境镜像 + 完整应用发布包 + releases 目录切换”。日常代码更新不构建应用镜像；运行时或 PHP 扩展变化才制作新运行环境镜像。

- 1Panel：管理 OpenResty、域名、TLS、Compose 和现有 MySQL 实例及备份任务。
- AdminFlow Compose：管理 FrankenPHP 服务，不部署常驻 Node 服务。Node 如有需要，仅用于开发或 CI 中的静态资源构建。
- MySQL：采用部署时核实的官方最新稳定版，为 AdminFlow 创建独立数据库与专用账号。仅在现有 1Panel 实例版本和兼容性符合要求时复用，不因现有实例较旧而自行降低目标版本，也不自动升级其他网站共用的数据库；冲突先报告由用户决定。不同时在应用 Compose 中重复启动 MySQL。
- 后续独立服务器交付可以补充自带数据库的 Compose 配置，复用同一应用发布包；本次不实现这个变体。
- 当前阶段无 Redis、worker、scheduler、Flowable 运行任务，部署方案也不提前启用这些服务。

不以 AOT、单二进制、Kubernetes、自建发布平台或自动升级面板应用为目标。

版本策略以 AGENTS.md 为准：数据库、Redis（实际接入时）、运行环境、框架和依赖跟进官方最新稳定版；先查证、检查兼容并验证，再固定实际版本/摘要发布。固定镜像用于可复现部署，不表示永久停留旧版本。面板、架构或依赖阻止使用最新稳定版时交由用户判断，禁止静默回退或强制升级共享服务。

## 3. 同域入口与网络

逻辑拓扑：

```text
浏览器 HTTPS https://adminflow.example.com
  -> 1Panel OpenResty（域名/TLS）
    -> AdminFlow FrankenPHP / Caddy（内部 HTTP 入口与路由）
      -> 静态资源：Laravel public/ 下的公开文件
      -> 登录、后台页面与 HTML 片段请求：Laravel Web 路由
      -> 后端数据访问：MySQL
```

认证使用 Laravel Web 路由及会话，例如 POST /login；实际路由在实现时确定并记录。普通表单携带 CSRF token，htmx 写请求统一携带有效 token；本方案不依赖 Sanctum 的 SPA 初始化流程。Laravel 对外根目录只允许 public/，不能暴露项目根目录、.env、vendor、releases 或日志。CMS 图床仅映射明确的公开媒体目录或受控读取路由，不能整体暴露 storage；草稿预览和后台文件不走公开映射。

网络实施约定：

1. 优先使用用户自定义 Docker 入口网络，使 OpenResty 与应用入口容器互通，通过唯一网络别名（如 adminflow-gateway）代理。
2. FrankenPHP 按实际 1Panel 数据库网络连接 MySQL，网络名称和实例容器名必须现场确认，不猜测默认值。
3. 只有入口加入 OpenResty 可访问的网络，不给 MySQL 分配公网端口；应用入口不占用宿主机 80/443。
4. OpenResty 在容器中时，127.0.0.1 指向 OpenResty 自身，不能直接照搬宿主机回环端口代理。若现场限制要求宿主机端口代理，另行确定可达地址、绑定方式和防火墙；不静默改成全网监听。
5. 网络连接必须写入持久的 Compose/面板配置，验证 OpenResty 更新或容器重建后仍可访问，而非只临时 docker network connect。

由 OpenResty 终止 TLS，内部 FrankenPHP 使用 HTTP，不重复申请证书。传递正确的原始 Host、协议及客户端信息；后端只信任受控代理来源。Cookie Secure、会话域、CSRF 和同源设置在 HTTPS 下联调。

浏览器使用同源页面与片段地址。管理页面和 htmx 请求均经过适用的 Laravel 会话、CSRF 与授权中间件；私有后台、草稿和预览 HTML 不进入共享缓存，公开内容遵守发布状态规则。如同一 URL 根据 HX-Request 返回不同内容，须处理 Vary 或禁用相应缓存，避免页面与片段混用。

## 4. 版本目录和发布包

规划中的服务器目录（非本地当前目录）：

```text
/opt/adminflow/
  deploy/                  # compose、Caddy 配置、发布脚本、运行环境记录
  releases/
    <release-id>/
      app/                 # Laravel 代码、Blade 模板与匹配的 vendor
        public/            # 公开静态资源；如采用 Vite，包含 public/build 产物
      manifest.json        # 提交 ID、校验值、运行时版本、迁移信息
  current -> releases/<release-id>
  shared/
    backend.env            # 服务器凭证和稳定 APP_KEY
    storage/               # 持久化的 Laravel 运行数据
  backups/                 # 本地备份暂存，另需异机副本
```

容器挂载整个 /opt/adminflow 到相同绝对路径，让 current、releases 和 shared 在容器内均可达。不能只 bind mount 宿主机 current 软链接：Docker 可能固定其旧目标，切换后容器仍使用旧目录。

代码、Blade 模板、vendor 和静态资源属于具体版本；.env、APP_KEY、用户文件和需要持久化的数据属于环境。bootstrap/cache 随版本管理；Laravel storage 中的会话等需要跨版本保留。发布时只给必要缓存/存储路径写权限，不能为省事整体 chmod 777。

服务器不运行开发服务器或 Vite HMR，也不部署 Nuxt/Node 应用服务。后台样式优先采用 Tailwind CSS 技术栈，具体 UI 组件库、后台模板及交互扩展框架待定。Tailwind CSS 及所选组件如需构建，在开发或 CI 环境完成，将静态产物与 Laravel 一并发布；本次不指定具体扩展产品或新增生产服务。

发布包在与目标 Linux 架构及运行时兼容的构建环境准备。如有静态资源构建，按 lockfile 在受控环境完成，不将 node_modules 作为生产运行依赖发布；PHP 依赖必须按目标 PHP/扩展校验，遵守 lockfile，生产禁用 dev 依赖。真实 .env 不进入发布包，Laravel 配置缓存在目标环境生成。

## 5. 首次安装：少量一次性操作

1. 确认 1Panel 版本、CPU 架构、域名、OpenResty 与 MySQL 实例、网络和备份位置。
2. 导入一次 Compose，使用已固定标签/摘要的运行环境镜像，配置持久目录与内部网络。
3. 在 1Panel 新建数据库和专用账号，写入服务器端环境配置。生成 APP_KEY 仅在首次初始化进行，以后保留。
4. 上传完整发布包，验证校验值、运行环境和所需扩展，准备 storage 链接与权限。
5. 显式执行一次数据库 migration；初始化正式管理员使用专用初始化流程，不运行演示租户和测试账号 Seeder。
6. 启动应用，配置 1Panel 反向代理网站和证书，完成 HTTPS 登录与实际业务验证。
7. 配置数据库和持久数据备份并保留环境恢复资料。1Panel 网站备份不能自动等同于应用、数据库与外部挂载目录都已备份。

后续实现需要分别提供“演示初始化”和“正式初始化”，但这不是给第一阶段新增用户功能。

## 6. 日常更新：一个入口完成发布

最终操作目标为“选择已验证的 release-id，执行一次部署”；再由 CI 通过受控 SSH 调用同一入口，实现推送指定发布分支/标签后部署。采用哪个 Git 托管平台，在实施时根据现有仓库决定，不新建平台。

计划中的发布入口暂记为 deploy <release-id>，尚未实现，不是当前可执行命令。

发布流程：

1. 获取部署锁，确认版本、校验值和运行环境；拒绝并行部署。
2. 在新 release 目录解包，链接环境文件与持久目录，检查 vendor、Blade 模板、静态资源和必要权限。
3. 在新版本目录生成该环境的 Laravel 缓存；不随意清空整个共享业务缓存。
4. 如涉及数据库迁移，先完成可用备份，再进入入口层短维护窗口、停止新业务写入并等待在途请求结束；执行经审查的 migration。不要将 migration 放入每个应用容器的无条件启动命令。
5. 无迁移的发布也使用短维护窗口切换，减少在途请求跨版本执行。入口层维护覆盖所有业务页面和片段请求；静态资源采用带内容摘要的文件名或等效版本策略，并按需要保留旧资源，避免已打开页面加载失败。
6. 保存上个版本指针，以同文件系统的原子重命名更新 current。切换指针不等于所有运行进程已加载新版本。
7. 常规模式下受控重启 FrankenPHP，使 OPcache、realpath 与服务状态确定。无需构建运行环境镜像，也不重启数据库或整套 1Panel。
8. 通过内部入口检查 Laravel 启动、数据库连接、Blade 页面及静态资源，再解除维护；从公开 HTTPS 地址验证代理、Cookie、CSRF 与 htmx 交互，记录发布结果。
9. 清理超出保留策略的旧版本，但不删除当前、上个可回退版本和 shared 数据。

首个方案允许短维护和重启，不能称为零停机或完整原子发布。开发期热更新、生产期自动检测文件变化不代替正式发布流程。

## 7. 哪些变更需要构建或重启

| 变更 | 应用产物处理 | 运行环境镜像 | 生效方式 |
| --- | --- | --- | --- |
| PHP 业务逻辑、路由、校验、Blade 模板 | 新版本应用包，未变更的静态资源可复用已验证产物 | 不构建 | 缓存处理，切换并受控重启 PHP |
| composer.lock | 用相同目标环境安装新 vendor 并验证 | 无新扩展/系统库则不构建 | 随后端版本发布 |
| 样式、JavaScript、htmx 等静态依赖 | 如采用构建工具，重新构建静态资源并随应用发布 | PHP 运行环境不变则不构建 | 随版本切换，首版统一重启 FrankenPHP |
| PHP 版本、扩展、系统库 | 验证全套应用与依赖 | 构建固定新版本 | 更新相关服务，备份并验收 |
| 构建用 Node 版本（如采用） | 更新构建环境并验证静态资源产物 | 不变更 PHP 运行环境镜像 | 发布验证后的资源，无 Node 服务需要重启 |
| 数据库结构 | 新 migration，审查兼容性 | 不构建 | 备份、维护、执行迁移 |
| 环境配置 | 更新服务器配置；重建相关 Laravel 缓存 | 不构建 | env_file 传给容器的变量变化需重建受影响容器，单纯 restart 不会刷新容器环境 |
| 字典、流程等数据库配置 | 后续模块按自身版本/缓存规则发布数据 | 不构建 | 后续模块另行定义，本阶段不实现 |

后续若明确启用 Octane，再将其重载命令纳入发布流程；当前不执行 octane:reload。后续有队列时必须同步处理 worker 的旧代码，不预先创建空 worker 服务。

## 8. 失败处理、回滚与备份

- 切换前失败：保留当前版本，删除或标记未完成新版本，不触碰业务数据。
- 切换后健康检查失败：在入口保持维护，确认数据库仍兼容旧代码时，切回旧指针并重启相关进程，再复查。
- 代码回滚不等于数据库回滚；不自动执行 migrate:rollback。优先采用先增加、兼容运行、后清理的迁移方式。破坏性迁移单独审批和安排维护；无法安全回退时保持维护并报告，不能盲目把旧代码切回来。
- 默认保留最近 3 个成功版本；迁移前保留数据库备份，关键发布前同时确认持久文件备份。
- 数据库备份采用合适的一致性备份方式，不能在数据库运行中仅复制数据目录；至少保留一份异机副本。
- 保留并保护稳定 APP_KEY、数据库连接、环境配置与恢复说明；恢复这些资料与应用数据必须配套。
- 外部 HTTPS 验证不得只检测 HTTP 200：首页成功不代表数据库、管理员登录、CSRF、草稿隔离、图床和评论正常。

## 9. 后续部署实施任务，不并入 P1

| 任务 | 交付 | 完成标准 |
| --- | --- | --- |
| DEP-01 | 固定运行环境、1Panel Compose/代理模板和安装说明 | 干净环境可启动；重建后网络仍通；HTTPS Cookie 登录正确 |
| DEP-02 | 兼容目标平台的发布包、清单及发布脚本 | PHP/Blade 小改动不构建镜像，静态资源按需构建；失败不会发布半成品 |
| DEP-03 | 版本回退、数据库和持久数据备份/恢复说明 | 实际演练一次应用回退与测试环境恢复；清楚标记不兼容迁移边界 |
| DEP-04 | CI 通过 SSH 调用发布入口 | 指定分支/标签发布，检查失败不切换版本；有锁、有结果记录 |

先实现单服务器、单域名、一套受控发布包。此处不加入 1Panel 应用商店上架、Web 发布后台、多机调度、蓝绿发布或客户自动升级。

## 10. 实施时再确认的信息

当前未读取服务器，因此以下未知：1Panel V1/V2、安装路径、实际 Docker 网络名称、MySQL 可用版本、服务器架构、应用域名、Git 托管平台及 CI 可达性。本文优先依据 1Panel V2 文档；实施前按实机确认。不会把示例域名或网络别名当成真实配置。

## 参考资料

- 1Panel Compose 创建与管理：https://1panel.pro/docs/v2/user_manual/containers/composes/
- 1Panel 网站创建：https://1panel.pro/docs/v2/user_manual/websites/website_create/
- 1Panel 网络：https://1panel.pro/docs/v2/user_manual/containers/network/
- Laravel 部署、缓存、目录权限、健康检查：https://laravel.com/docs/deployment
- Laravel CSRF：https://laravel.com/docs/13.x/csrf
- Laravel Blade：https://laravel.com/docs/13.x/blade
- Laravel 静态资源构建：https://laravel.com/docs/13.x/vite
- htmx 请求、响应与安全：https://htmx.org/docs/
- FrankenPHP 生产部署：https://frankenphp.dev/docs/production/
- Deployer Laravel recipe（参考 releases/shared/回滚组织方式，不作为强制新增依赖）：https://deployer.org/docs/8.x/recipe/laravel
