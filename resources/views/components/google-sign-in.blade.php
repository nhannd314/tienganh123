{{--
    "Continue with Google" button followed by a divider with `divider` as its label. It is a link to the OAuth redirect;
    resources/js/auth.js puts it in the busy state while the browser leaves for Google.
--}}
@props([
    'label' => 'Đăng nhập với Google',
    'divider' => 'hoặc đăng nhập bằng email',
])

<x-button variant="outline" size="lg" full-width :href="route('auth.google.redirect')" data-google-button>
    <img src="{{ asset('images/google.svg') }}" alt="" width="24" height="24" class="block size-6">
    {{ $label }}
</x-button>

<div role="separator" class="flex items-center gap-3 type-body-sm text-fg-muted">
    <span class="h-px flex-1 bg-line-subtle"></span>
    <span>{{ $divider }}</span>
    <span class="h-px flex-1 bg-line-subtle"></span>
</div>
