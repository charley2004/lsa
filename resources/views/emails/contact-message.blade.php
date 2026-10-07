@component('mail::message')
# New contact message

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})
**Subject:** {{ ucfirst($contactMessage->subject) }}

{{ $contactMessage->message }}

@component('mail::button', ['url' => url('/admin/contact-messages')])
View in admin
@endcomponent

Reply directly to this email to respond to {{ $contactMessage->name }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
