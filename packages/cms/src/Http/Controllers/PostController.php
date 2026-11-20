<?php

namespace AdminFlow\CMS\Http\Controllers;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use AdminFlow\CMS\Exceptions\ConcurrencyConflictException;
use AdminFlow\CMS\Http\Requests\PostRequest;
use AdminFlow\CMS\Models\Post;
use AdminFlow\CMS\Services\MarkdownService;
use AdminFlow\CMS\Services\PostDataResolver;
use AdminFlow\CMS\Services\PostService;
use AdminFlow\Core\Http\Responses\HtmxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
        private readonly MarkdownService $markdownService,
        private readonly PostDataResolver $postDataResolver,
    ) {}

    /**
     * 内容管理列表（支持状态与类型筛选）
     */
    public function index(Request $request): View|Response|RedirectResponse
    {
        Gate::authorize('viewAny', Post::class);

        $validator = Validator::make(
            $request->query(),
            [
                'search' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'string', Rule::enum(PostStatus::class)],
                'type' => ['nullable', 'string', Rule::enum(PostType::class)],
            ],
            [
                'search.string' => '搜索参数格式不正确，必须为纯文本',
                'status.Illuminate\Validation\Rules\Enum' => '选择的状态不合法',
                'type.Illuminate\Validation\Rules\Enum' => '选择的类型不合法',
            ],
        );

        if ($validator->fails()) {
            if ($request->hasHeader('HX-Request') && !$request->hasHeader('HX-History-Restore-Request')) {
                $errorMsg = $validator->errors()->first() ?? '筛选参数不合法';
                $filterErrors = $validator->errors()->all();

                $posts = $this->postService->paginate([], perPage: 15);
                $statuses = PostStatus::cases();
                $types = PostType::cases();
                $filters = ['search' => null, 'status' => null, 'type' => null];

                $content = view('adminflow-cms::posts.partials.table', compact(
                    'posts',
                    'filters',
                    'statuses',
                    'types',
                    'filterErrors',
                ))->render();

                return response($content, 422)
                    ->header('HX-Push-Url', 'false')
                    ->header(
                        'HX-Trigger',
                        (string) json_encode([
                            'showToast' => [
                                'message' => $errorMsg,
                                'type' => 'error',
                            ],
                        ], JSON_THROW_ON_ERROR),
                    );
            }

            return redirect()->route('admin.cms.posts.index')->with('error', '筛选参数不合法，已重置为默认列表');
        }

        $validated = $validator->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'status' => $validated['status'] ?? null,
            'type' => $validated['type'] ?? null,
        ];

        $posts = $this->postService->paginate($filters, perPage: 15);
        $statuses = PostStatus::cases();
        $types = PostType::cases();

        if ($request->hasHeader('HX-Request') && !$request->hasHeader('HX-History-Restore-Request')) {
            return view('adminflow-cms::posts.partials.table', compact('posts', 'filters', 'statuses', 'types'));
        }

        $statistics = $this->postService->getStatistics();

        return view('adminflow-cms::posts.index', compact('posts', 'filters', 'statuses', 'types', 'statistics'));
    }

    /**
     * 新建内容页面
     */
    public function create(): View
    {
        Gate::authorize('create', Post::class);

        $post = new Post([
            'type' => PostType::POST,
            'status' => PostStatus::DRAFT,
            'published_at' => now(),
            'allow_comments' => true,
            'lock_version' => 1,
        ]);

        $statuses = PostStatus::cases();
        $types = PostType::cases();

        return view('adminflow-cms::posts.form', compact('post', 'statuses', 'types'));
    }

    /**
     * 提交新建内容
     */
    public function store(PostRequest $request): Response|RedirectResponse
    {
        Gate::authorize('create', Post::class);

        if ($request->input('status') === PostStatus::PUBLISHED->value) {
            Gate::authorize('publish', Post::class);
        }

        /** @var \AdminFlow\Core\Models\User $user */
        $user = Auth::user();

        try {
            $post = $this->postService->create($request->validated(), $user);
        } catch (ValidationException $e) {
            if ($request->header('HX-Request')) {
                return $this->validationErrorResponse($e);
            }

            throw $e;
        }

        if ($request->header('HX-Request')) {
            session()->flash('success', '内容创建成功，已进入草稿');

            return HtmxResponse::toast('内容创建成功，已进入草稿')->header('HX-Location', route(
                'admin.cms.posts.edit',
                $post,
            ));
        }

        return redirect()->route('admin.cms.posts.edit', $post)->with('success', '内容创建成功，已进入草稿');
    }

    /**
     * 编辑内容页面
     */
    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        $statuses = PostStatus::cases();
        $types = PostType::cases();

        return view('adminflow-cms::posts.form', compact('post', 'statuses', 'types'));
    }

    /**
     * 更新内容（带乐观锁并发冲突处理）
     */
    public function update(PostRequest $request, Post $post): Response|RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $post);

        if ($this->postDataResolver->requiresPublishAuthorization(
            $post,
            $request->input('status'),
            $request->input('published_at'),
        )) {
            Gate::authorize('publish', $post);
        }

        $expectedVersion = (int) $request->input('lock_version');

        try {
            /** @var \AdminFlow\Core\Models\User $user */
            $user = Auth::user();

            $updatedPost = $this->postService->update(
                post: $post,
                data: $request->validated(),
                expectedVersion: $expectedVersion,
                actor: $user,
            );

            if ($request->header('HX-Request')) {
                return HtmxResponse::toast('保存成功', 'success', [
                    'postSaved' => [
                        'version' => $updatedPost->lock_version,
                    ],
                ]);
            }

            return redirect()->route('admin.cms.posts.edit', $updatedPost)->with('success', '保存成功');
        } catch (ConcurrencyConflictException $e) {
            $conflictMsg = "内容已被他人修改（当前最新版本: v{$e->getCurrentVersion()}，您提交的版本: v{$e->getExpectedVersion()}），保存已被拦截，请刷新以防覆盖。";

            if ($request->header('HX-Request')) {
                return HtmxResponse::conflict(
                    message: $conflictMsg,
                    view: 'adminflow-cms::posts.partials.alert-conflict',
                    viewData: [
                        'message' => $conflictMsg,
                        'currentVersion' => $e->getCurrentVersion(),
                        'expectedVersion' => $e->getExpectedVersion(),
                    ],
                );
            }

            return back()->withInput()->with('error', $conflictMsg)->setStatusCode(Response::HTTP_CONFLICT);
        } catch (ValidationException $e) {
            if ($request->header('HX-Request')) {
                return $this->validationErrorResponse($e);
            }

            throw $e;
        }
    }

    /**
     * 生成 HTMX 局部校验错误响应（HTTP 422 局部卡片与全局 Toast）
     */
    private function validationErrorResponse(ValidationException $e): Response
    {
        $errors = collect($e->errors())->flatten()->all();
        $firstError = $errors[0] ?? '表单校验未通过，请检查错误提示';

        $view = view('adminflow-cms::posts.partials.alert-errors', [
            'errors' => $errors,
        ])->render();

        return response($view, Response::HTTP_UNPROCESSABLE_ENTITY)->header(
            'HX-Trigger',
            (string) json_encode([
                'showToast' => [
                    'message' => $firstError,
                    'type' => 'error',
                ],
            ], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * 变更发布状态（快速发布 / 撤回草稿）
     */
    public function togglePublish(Request $request, Post $post): Response|RedirectResponse
    {
        Gate::authorize('publish', $post);

        $request->validate([
            'lock_version' => ['required', 'integer'],
        ]);
        $expectedVersion = (int) $request->input('lock_version');

        $targetStatus = $post->status === PostStatus::PUBLISHED ? PostStatus::DRAFT : PostStatus::PUBLISHED;

        try {
            /** @var \AdminFlow\Core\Models\User $user */
            $user = Auth::user();

            $this->postService->transitionStatus($post, $targetStatus, $expectedVersion, $user);

            $msg = $targetStatus === PostStatus::PUBLISHED ? '文章已正式发布' : '文章已撤回为草稿';

            if ($request->header('HX-Request')) {
                return HtmxResponse::success($msg);
            }

            return back()->with('success', $msg);
        } catch (ConcurrencyConflictException) {
            $errorMsg = '并发冲突：内容状态已被他人修改，请刷新页面重试。';
            if ($request->header('HX-Request')) {
                return HtmxResponse::conflict($errorMsg);
            }

            return back()->with('error', $errorMsg)->setStatusCode(Response::HTTP_CONFLICT);
        }
    }

    /**
     * 删除内容
     */
    public function destroy(Request $request, Post $post): Response|RedirectResponse
    {
        Gate::authorize('delete', $post);

        $request->validate([
            'lock_version' => ['required', 'integer'],
        ]);
        $expectedVersion = (int) $request->input('lock_version');

        try {
            /** @var \AdminFlow\Core\Models\User $user */
            $user = Auth::user();

            $this->postService->delete($post, $expectedVersion, $user);

            if ($request->header('HX-Request')) {
                return HtmxResponse::success('内容已删除');
            }

            return redirect()->route('admin.cms.posts.index')->with('success', '内容已删除');
        } catch (ConcurrencyConflictException) {
            $conflictMsg = '并发冲突：该内容已被他人修改，删除已被拦截，请刷新页面。';
            if ($request->header('HX-Request')) {
                return HtmxResponse::conflict($conflictMsg);
            }

            return back()->with('error', $conflictMsg)->setStatusCode(Response::HTTP_CONFLICT);
        }
    }

    /**
     * 受保护的内容预览（草稿/已发布）
     */
    public function preview(Post $post): View
    {
        Gate::authorize('view', $post);

        return view('adminflow-cms::posts.preview', compact('post'));
    }

    /**
     * 编辑器实时 Markdown 权威服务端渲染端点
     */
    public function previewRender(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $markdown = (string) $request->input('body_markdown', '');
        $html = $this->markdownService->render($markdown);

        return response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
