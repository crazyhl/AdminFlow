<div id="posts-table-card" class="space-y-4">
    @if(!empty($filterErrors))
        @include('adminflow-cms::posts.partials.alert-errors', ['errors' => $filterErrors])
    @endif

    <!-- 内容数据表格 -->
    <x-admin::table-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-100/60 dark:bg-stone-800/40 border-b border-stone-200/70 dark:border-stone-800 text-stone-500 dark:text-stone-400">
                    <tr>
                        <th class="py-3 px-4 font-medium">标题与固定链接</th>
                        <th class="py-3 px-4 font-medium">类型</th>
                        <th class="py-3 px-4 font-medium">发布状态</th>
                        <th class="py-3 px-4 font-medium">作者</th>
                        <th class="py-3 px-4 font-medium">发布时间</th>
                        <th class="py-3 px-4 font-medium">并发版本</th>
                        <th class="py-3 px-4 font-medium text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200/60 dark:divide-stone-800">
                    @forelse($posts as $post)
                        <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-800/20 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    @if($post->is_pinned)
                                        <span class="text-amber-600 dark:text-amber-400" title="置顶内容">
                                            <i class="bi bi-pin-angle-fill text-xs"></i>
                                        </span>
                                    @endif
                                    @can('update', $post)
                                        <a href="{{ route('admin.cms.posts.edit', $post) }}" class="font-medium text-stone-900 dark:text-stone-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                            {{ $post->title }}
                                        </a>
                                    @else
                                        <a href="{{ route('admin.cms.posts.preview', $post) }}" class="font-medium text-stone-900 dark:text-stone-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="查看预览">
                                            {{ $post->title }}
                                        </a>
                                    @endcan
                                </div>
                                <div class="text-[11px] text-stone-400 font-mono mt-0.5 flex items-center gap-1.5">
                                    <span>/{{ $post->type->value === 'page' ? 'p' : 'posts' }}/{{ $post->slug }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-stone-600 dark:text-stone-400">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono {{ $post->type === \AdminFlow\CMS\Enums\PostType::PAGE ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300' }}">
                                    {{ $post->type->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $post->status->badgeColor() }}">
                                    {{ $post->status->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-stone-600 dark:text-stone-400">
                                {{ $post->author?->name ?? '系统' }}
                            </td>
                            <td class="py-3.5 px-4 text-stone-500 font-mono text-[11px]">
                                {{ $post->published_at ? $post->published_at->format('Y-m-d H:i') : '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-stone-400 font-mono text-[11px]">
                                <span class="bg-stone-100 dark:bg-stone-800 px-1.5 py-0.5 rounded text-[10px]" title="乐观并发锁版本号">
                                    v{{ $post->lock_version }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- 受保护预览 (新标签页安全查看) -->
                                    <a href="{{ route('admin.cms.posts.preview', $post) }}" target="_blank" class="p-1 text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 transition-colors" title="受保护文章预览">
                                        <i class="bi bi-eye text-sm"></i>
                                    </a>

                                    @can('update', $post)
                                        <!-- 编辑 -->
                                        <a href="{{ route('admin.cms.posts.edit', $post) }}" class="p-1 text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 transition-colors" title="编辑文章">
                                            <i class="bi bi-pencil text-sm"></i>
                                        </a>
                                    @endcan

                                    @can('publish', $post)
                                        <!-- 快速变更发布状态 (HTMX 异步提交，失败自动显示 Toast 并保留表格) -->
                                        <form method="POST" action="{{ route('admin.cms.posts.toggle-publish', $post) }}" hx-post="{{ route('admin.cms.posts.toggle-publish', $post) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="lock_version" value="{{ $post->lock_version }}">
                                            <button type="submit" class="p-1 text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 transition-colors" title="{{ $post->status === \AdminFlow\CMS\Enums\PostStatus::PUBLISHED ? '撤回为草稿' : '正式发布' }}">
                                                <i class="bi {{ $post->status === \AdminFlow\CMS\Enums\PostStatus::PUBLISHED ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle' }} text-sm"></i>
                                            </button>
                                        </form>
                                    @endcan

                                    @can('delete', $post)
                                        <!-- 删除 (调起 Preline UI 二次确认弹窗) -->
                                        <button type="button" class="p-1 text-stone-400 hover:text-rose-600 transition-colors" data-hs-overlay="#hs-modal-confirm-delete-{{ $post->id }}" title="删除">
                                            <i class="bi bi-trash text-sm"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-stone-400 dark:text-stone-500">
                                <i class="bi bi-journal-x text-3xl block mb-2 opacity-50"></i>
                                暂无符合条件的内容，点击上方按钮撰写第一篇文章
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 分页条 (仅对分页链接启用 hx-boost，防止表格内编辑与跳转链接被意外劫持替换) -->
        @if($posts->hasPages())
            <div class="px-4 py-3 border-t border-stone-200/60 dark:border-stone-800" hx-boost="true" hx-target="#posts-table-card" hx-swap="outerHTML">
                {{ $posts->appends($filters)->links() }}
            </div>
        @endif
    </x-admin::table-card>

    <!-- 删除二次确认模态窗 -->
    @foreach($posts as $post)
        @can('delete', $post)
            <x-admin::delete-dialog
                id="hs-modal-confirm-delete-{{ $post->id }}"
                :action="route('admin.cms.posts.destroy', ['post' => $post, 'lock_version' => $post->lock_version])"
                title="确认彻底删除该内容？"
                :item="$post->title"
                confirmText="确认彻底删除"
            >
                <input type="hidden" name="lock_version" value="{{ $post->lock_version }}">
            </x-admin::delete-dialog>
        @endcan
    @endforeach
</div>
