<x-layouts.site title="Account" robots="noindex">
    <div class="container-x max-w-3xl space-y-6 py-10">
        <x-page-header eyebrow="Account" title="Your details" />
        <div class="card card-pad">@include('profile.partials.update-profile-information-form')</div>
        <div class="card card-pad">@include('profile.partials.update-password-form')</div>
        <div class="card card-pad">@include('profile.partials.delete-user-form')</div>
    </div>
</x-layouts.site>
