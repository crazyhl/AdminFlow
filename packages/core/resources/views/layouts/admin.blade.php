<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '控制台') · AdminFlow</title>

    <!-- Vite 构建产物 (Tailwind CSS v4 + Preline UI 本地按需编译) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- 图标库 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- 主题与侧边栏防闪烁加载控制 -->
    @include('adminflow-core::layouts.partials.theme-script')
</head>
<body class="h-full flex bg-[#fbfbfa] dark:bg-[#191919] text-stone-800 dark:text-stone-200 antialiased selection:bg-stone-200 dark:selection:bg-stone-700 transition-colors duration-150">

    <!-- 统一侧边栏 -->
    @include('adminflow-core::layouts.partials.sidebar')

    <!-- 主工作区 -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- 顶栏导航 -->
        @include('adminflow-core::layouts.partials.header')

        <!-- 全局 Toast 提示 -->
        @include('adminflow-core::layouts.partials.toast')

        <!-- 页面主体容器 (全宽响应式展开) -->
        <main class="@yield('main_class', 'p-6 lg:p-8 w-full space-y-8')">
            @yield('content')
        </main>
    </div>
</body>
</html>
