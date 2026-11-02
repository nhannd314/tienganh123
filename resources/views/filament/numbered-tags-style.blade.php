{{-- Numbers the tags of a tags input given the `fi-numbered-tags` class ("a. apple", "b. banana"), following their order when they are reordered. --}}
<style>
    .fi-numbered-tags .fi-fo-tags-input-tags-ctn {
        counter-reset: numbered-tag;
    }

    .fi-numbered-tags .fi-fo-tags-input-tags-ctn .fi-badge-label::before {
        counter-increment: numbered-tag;
        content: counter(numbered-tag, lower-alpha) ". ";
        font-weight: 600;
    }
</style>
