@props([
    'id',
    'action',
    'title' => '确认彻底删除？',
    'item' => '',
    'message' => null,
    'confirmText' => '确认彻底删除',
])

<x-admin::confirm-dialog
    :id="$id"
    :action="$action"
    method="DELETE"
    :title="$title"
    :message="$message ?? ($item ? '「<strong>' . e($item) . '</strong>」将被彻底移除，此操作<strong>无法撤销</strong>。' : '此操作将彻底移除该项数据，且<strong>无法撤销</strong>。')"
    :confirm-text="$confirmText"
    confirm-color="rose"
    icon="bi-trash"
>
    {{ $slot ?? '' }}
</x-admin::confirm-dialog>
