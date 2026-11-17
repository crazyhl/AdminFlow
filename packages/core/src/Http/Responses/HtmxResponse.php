<?php

namespace AdminFlow\Core\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;

final class HtmxResponse
{
    /**
     * 生成 HTMX 操作成功响应（写入 Flash、通知客户端刷新并呼出 Toast）
     */
    public static function success(string $message): Response
    {
        return self::toast($message)->header('HX-Refresh', 'true');
    }

    /**
     * 生成仅呼出 Toast 的 HTMX 响应（不强制触发整页刷新）
     */
    public static function toast(string $message, string $type = 'success'): Response
    {
        session()->flash($type, $message);

        return response('')->header(
            'HX-Trigger',
            (string) json_encode([
                'showToast' => [
                    'message' => $message,
                    'type' => $type,
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * 安全过滤并回显表单标量输入，防止非法类型（如数组）存入旧输入导致 Blade htmlspecialchars 抛出 TypeError
     *
     * @param array<int, string> $allowedFields
     */
    public static function flashSafeInput(
        Request $request,
        array $allowedFields = ['name', 'email', 'role', 'is_active'],
    ): void {
        $safeInput = [];
        foreach ($allowedFields as $field) {
            if (!$request->has($field)) {
                continue;
            }

            $val = $request->input($field);

            if ($field === 'is_active') {
                $safeInput[$field] = (is_string($val) || is_numeric($val)) && in_array((string) $val, ['0', '1'], true)
                    ? (string) $val
                    : '0';
                continue;
            }

            if (is_string($val) || is_numeric($val)) {
                $safeInput[$field] = (string) $val;
            }
        }

        $request->session()->flash('_old_input', $safeInput);
    }

    /**
     * 生成 HTMX 表单校验失败响应（返回 HTTP 422 状态码、安全回显标量输入并呈现局部错误）
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $allowedFields
     */
    public static function validation(
        string $view,
        array $data,
        ValidationException $e,
        Request $request,
        array $allowedFields = ['name', 'email', 'role', 'is_active'],
    ): Response {
        self::flashSafeInput($request, $allowedFields);
        $errorBag = new ViewErrorBag()->put('default', $e->validator->errors());
        $data['errors'] = $errorBag;

        return response()->view($view, $data, 422);
    }

    /**
     * 生成 HTMX 业务逻辑拦截响应（返回 HTTP 422 状态码、安全回显标量输入并指定字段错误提示）
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $allowedFields
     */
    public static function error(
        string $view,
        array $data,
        string $field,
        string $message,
        Request $request,
        array $allowedFields = ['name', 'email', 'role', 'is_active'],
    ): Response {
        self::flashSafeInput($request, $allowedFields);
        $errorBag = new ViewErrorBag()->put('default', new MessageBag([$field => [$message]]));
        $data['errors'] = $errorBag;

        return response()->view($view, $data, 422);
    }
}
