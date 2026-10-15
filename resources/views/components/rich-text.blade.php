{{-- Rendered rich editor content (a post or a fixed page): headings, lists, quotes, links and images styled to the design system. --}}
<div {{ $attributes->class(['flex flex-col gap-4 type-body-lg text-pretty text-fg-body [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-primary-border [&_blockquote]:pl-4 [&_blockquote]:text-fg-muted [&_h2]:pt-2 [&_h2]:type-h2 [&_h2]:text-fg-strong [&_h3]:type-h3 [&_h3]:text-fg-strong [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-md [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:text-fg-strong [&_ul]:list-disc [&_ul]:pl-5']) }}>
    {{ $slot }}
</div>
