<?php

namespace AdminFlow\Core\Services;

use Illuminate\Support\Facades\Route;

class NavigationRegistry
{
    /**
     * @var array<string, array{
     *     key: string,
     *     title: string,
     *     order: int,
     *     items: array<int, array{
     *         title: string,
     *         route: string,
     *         active: string,
     *         icon: string,
     *         permission: string|callable|null,
     *         badge: string|callable|null,
     *         order: int,
     *     }>
     * }>
     */
    protected array $sections = [];

    /**
     * 注册导航分组
     */
    public function registerSection(string $key, string $title, int $order = 100): void
    {
        if (!isset($this->sections[$key])) {
            $this->sections[$key] = [
                'key' => $key,
                'title' => $title,
                'order' => $order,
                'items' => [],
            ];

            return;
        }

        $this->sections[$key]['title'] = $title;
        $this->sections[$key]['order'] = $order;
    }

    /**
     * 向指定分组注册菜单项
     *
     * @param array{
     *     title: string,
     *     route: string,
     *     active?: string,
     *     icon?: string,
     *     permission?: string|callable|null,
     *     badge?: string|callable|null,
     *     order?: int,
     * } $item
     */
    public function registerItem(string $sectionKey, array $item): void
    {
        if (!isset($this->sections[$sectionKey])) {
            $this->registerSection($sectionKey, $sectionKey);
        }

        $this->sections[$sectionKey]['items'][] = [
            'title' => $item['title'],
            'route' => $item['route'],
            'active' => $item['active'] ?? $item['route'],
            'icon' => $item['icon'] ?? 'bi-circle',
            'permission' => $item['permission'] ?? null,
            'badge' => $item['badge'] ?? null,
            'order' => $item['order'] ?? 10,
        ];
    }

    /**
     * 获取按顺序排序并经过权限过滤的所有有效区块与菜单项
     *
     * @return array<int, array{
     *     key: string,
     *     title: string,
     *     items: array<int, array{
     *         title: string,
     *         url: string,
     *         isActive: bool,
     *         icon: string,
     *         badge: string|null,
     *     }>
     * }>
     */
    public function getVisibleSections(): array
    {
        $user = auth()->user();
        $sections = array_values($this->sections);

        usort($sections, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);

        $result = [];
        foreach ($sections as $section) {
            $items = $section['items'];
            usort($items, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);

            $visibleItems = [];
            foreach ($items as $item) {
                if (!$this->canAccess($item['permission'], $user)) {
                    continue;
                }

                if (!Route::has($item['route'])) {
                    continue;
                }

                $badge = is_callable($item['badge']) ? $item['badge']() : $item['badge'];

                $visibleItems[] = [
                    'title' => $item['title'],
                    'url' => route($item['route']),
                    'isActive' => request()->routeIs($item['active']),
                    'icon' => $item['icon'],
                    'badge' => is_string($badge) || is_numeric($badge) ? (string) $badge : null,
                ];
            }

            if (!empty($visibleItems)) {
                $result[] = [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'items' => $visibleItems,
                ];
            }
        }

        return $result;
    }

    /**
     * 权限断言辅助判断（默认拒绝未登录访客与无法判定权限的用户）
     */
    protected function canAccess(mixed $permission, mixed $user): bool
    {
        if ($permission === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if (is_callable($permission)) {
            return (bool) $permission($user);
        }

        if (is_string($permission) && method_exists($user, 'hasPermission')) {
            return (bool) $user->hasPermission($permission);
        }

        return false;
    }
}
