<?php

namespace AdminFlow\CMS\Http\Requests;

use AdminFlow\CMS\Enums\PostStatus;
use AdminFlow\CMS\Enums\PostType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PostRequest extends FormRequest
{
    /**
     * 判断用户是否有权限发起此请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 验证规则
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $post = $this->route('post');
        $postId = is_object($post) ? $post->id : $post;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cms_posts', 'slug')->ignore($postId),
            ],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body_markdown' => ['required', 'string'],
            'cover_image_url' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PostType::class)],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'is_pinned' => ['nullable', 'boolean'],
            'allow_comments' => ['nullable', 'boolean'],
            'lock_version' => $this->isMethod('POST') && !$postId ? ['nullable', 'integer'] : ['required', 'integer'],
        ];
    }

    /**
     * 自定义验证消息
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => '文章标题不能为空',
            'title.max' => '文章标题不能超过 255 个字符',
            'slug.unique' => '该固定链接已存在，请更换',
            'cover_image_url.max' => '封面图片链接不能超过 255 个字符',
            'body_markdown.required' => 'Markdown 正文不能为空',
            'lock_version.required' => '缺少版本号，无法进行并发安全校验',
            'type.required' => '内容类型必须指定',
            'status.required' => '发布状态必须指定',
        ];
    }

    /**
     * 处理验证失败：针对 HTMX 异步请求返回局部错误卡片及全局 Toast
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->header('HX-Request')) {
            $view = view('adminflow-cms::posts.partials.alert-errors', [
                'errors' => $validator->errors()->all(),
            ])->render();

            $firstError = $validator->errors()->first();

            throw new HttpResponseException(response($view, Response::HTTP_UNPROCESSABLE_ENTITY)->header(
                'HX-Trigger',
                (string) json_encode([
                    'showToast' => [
                        'message' => $firstError ?? '表单校验未通过，请检查错误提示',
                        'type' => 'error',
                    ],
                ], JSON_THROW_ON_ERROR),
            ));
        }

        parent::failedValidation($validator);
    }
}
