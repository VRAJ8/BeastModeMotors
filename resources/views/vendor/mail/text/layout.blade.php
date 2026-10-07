{!! strip_tags($header ?? '') !!}

{!! strip_tags(md_plain($slot)) !!}
@isset($subcopy)

{!! strip_tags(md_plain($subcopy)) !!}
@endisset

{!! strip_tags($footer ?? '') !!}
