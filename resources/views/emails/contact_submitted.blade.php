<h2>New Contact Message</h2>

<p><strong>Name:</strong> {{ $contact->name ?? 'N/A' }}</p>
<p><strong>Email:</strong> {{ $contact->email }}</p>
<p><strong>Subject:</strong> {{ $contact->subject ?? 'N/A' }}</p>

<hr>

<p>{{ $contact->message }}</p>
